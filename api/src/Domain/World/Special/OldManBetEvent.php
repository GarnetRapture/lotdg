<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Support\LocalizedException;
use PDO;

final class OldManBetEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'oldmanbet';

    public const int MINIMUM_NUMBER = 1;

    public const int MAXIMUM_NUMBER = 100;

    public const int MAXIMUM_TRY = 6;

    private const int WIN_MULTIPLIER = 3;

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    public function eventCode(): string
    {
        return self::EVENT_CODE;
    }

    /**
     * @return array<string, mixed>
     */
    public function start(int $characterId): array
    {
        $row = $this->fetchCharacterRow($characterId);

        if ((int) $row['gold'] <= 0) {
            $this->clearState($characterId);

            return ['stage' => 'no-gold'];
        }

        $this->storeState($characterId, [
            'stage' => 'awaiting-bet',
            'secret_number' => \random_int(self::MINIMUM_NUMBER, self::MAXIMUM_NUMBER),
            'bet' => 0,
            'try_count' => 0,
        ]);

        return [
            'stage' => 'awaiting-bet',
            'gold' => (int) $row['gold'],
            'minimum_number' => self::MINIMUM_NUMBER,
            'maximum_number' => self::MAXIMUM_NUMBER,
            'maximum_try' => self::MAXIMUM_TRY,
            'win_multiplier' => self::WIN_MULTIPLIER,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function decline(int $characterId): array
    {
        $this->clearState($characterId);

        return ['stage' => 'declined'];
    }

    /**
     * @return array<string, mixed>
     */
    public function placeBet(int $characterId, int $betAmount): array
    {
        $state = $this->loadState($characterId);

        if (($state['stage'] ?? '') !== 'awaiting-bet') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $row = $this->fetchCharacterRow($characterId);
        $bet = \abs($betAmount);

        if ($bet <= 0) {
            return [
                'stage' => 'awaiting-bet',
                'message_key' => 'special.old-man-bet.error.zero-bet',
            ];
        }

        if ($bet > (int) $row['gold']) {
            $this->clearState($characterId);

            return [
                'stage' => 'bet-too-large',
                'gold' => (int) $row['gold'],
                'bet' => $bet,
            ];
        }

        $state['stage'] = 'awaiting-guess';
        $state['bet'] = $bet;
        $state['try_count'] = 0;
        $this->storeState($characterId, $state);

        return [
            'stage' => 'awaiting-guess',
            'bet' => $bet,
            'try_count' => 0,
            'remaining_try' => self::MAXIMUM_TRY,
        ];
    }

    /**
     * 레거시 TODO.txt 의 "허용 범위 밖 숫자는 시도 횟수를 소모하지 않는다" 를 반영한다.
     *
     * @return array<string, mixed>
     */
    public function guess(int $characterId, int $guessNumber): array
    {
        $state = $this->loadState($characterId);

        if (($state['stage'] ?? '') !== 'awaiting-guess') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $bet = (int) ($state['bet'] ?? 0);
        $secretNumber = (int) ($state['secret_number'] ?? 0);
        $tryCount = (int) ($state['try_count'] ?? 0);

        if ($guessNumber < self::MINIMUM_NUMBER || $guessNumber > self::MAXIMUM_NUMBER) {
            return [
                'stage' => 'awaiting-guess',
                'message_key' => 'special.old-man-bet.error.out-of-range',
                'bet' => $bet,
                'try_count' => $tryCount,
                'remaining_try' => self::MAXIMUM_TRY - $tryCount,
                'minimum_number' => self::MINIMUM_NUMBER,
                'maximum_number' => self::MAXIMUM_NUMBER,
            ];
        }

        ++$tryCount;

        if ($guessNumber === $secretNumber) {
            $this->adjustGold($characterId, $bet * self::WIN_MULTIPLIER);
            $this->clearState($characterId);

            return [
                'stage' => 'won',
                'bet' => $bet,
                'reward' => $bet * self::WIN_MULTIPLIER,
                'secret_number' => $secretNumber,
                'try_count' => $tryCount,
            ];
        }

        if ($tryCount >= self::MAXIMUM_TRY) {
            $this->adjustGold($characterId, -$bet);
            $this->clearState($characterId);

            return [
                'stage' => 'lost',
                'bet' => $bet,
                'secret_number' => $secretNumber,
                'try_count' => $tryCount,
            ];
        }

        $state['try_count'] = $tryCount;
        $this->storeState($characterId, $state);

        return [
            'stage' => 'awaiting-guess',
            'bet' => $bet,
            'try_count' => $tryCount,
            'remaining_try' => self::MAXIMUM_TRY - $tryCount,
            'guess_number' => $guessNumber,
            'hint' => $guessNumber > $secretNumber ? 'lower' : 'higher',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadState(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT special_include_name, special_misc_json
               FROM character_session_state
              WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false || (string) $row['special_include_name'] !== self::EVENT_CODE) {
            return [];
        }

        $decoded = \json_decode((string) $row['special_misc_json'], true);

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function storeState(int $characterId, array $state): void
    {
        $encoded = \json_encode($state, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        $this->connection
            ->prepare(
                'UPDATE character_session_state
                    SET special_include_name = :special_include_name,
                        special_misc_json    = :special_misc_json
                  WHERE character_id = :character_id',
            )
            ->execute([
                'special_include_name' => self::EVENT_CODE,
                'special_misc_json' => $encoded === false ? '{}' : $encoded,
                'character_id' => $characterId,
            ]);
    }

    private function clearState(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_session_state
                    SET special_include_name = \'\',
                        special_misc_json    = \'{}\'
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);
    }

    private function adjustGold(int $characterId, int $delta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gold = MAX(0, gold + :delta)
                  WHERE character_id = :character_id',
            )
            ->execute(['delta' => $delta, 'character_id' => $characterId]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCharacterRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT character_wealth.gold,
                    character_equipment.weapon_name
               FROM game_character
               JOIN character_wealth    ON character_wealth.character_id = game_character.character_id
               JOIN character_equipment ON character_equipment.character_id = game_character.character_id
              WHERE game_character.character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return $row;
    }
}
