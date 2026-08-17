<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Domain\Social\NewsService;
use Lotdg\Support\LocalizedException;
use PDO;

final class NecromancerEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'necromancer';

    public const string OUTCOME_DEATH = 'death';

    public const string OUTCOME_DRAINED = 'drained';

    public const string OUTCOME_GEM_REQUEST = 'gem-request';

    public const string OUTCOME_SCARED_OFF = 'scared-off';

    public const string OUTCOME_FAVOR = 'favor';

    public const string OUTCOME_GEM_STOLEN = 'gem-stolen';

    private const float DEATH_EXPERIENCE_RATE = 0.85;

    private const int FAVOR_MINIMUM = 5;

    private const int FAVOR_MAXIMUM = 35;

    public function __construct(
        private readonly PDO $connection,
        private readonly SpecialEventState $eventState,
        private readonly NewsService $newsService,
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
        $this->eventState->store($characterId, self::EVENT_CODE, ['stage' => 'awaiting-choice']);

        return ['stage' => 'awaiting-choice'];
    }

    /**
     * @return array<string, mixed>
     */
    public function leave(int $characterId): array
    {
        $this->eventState->clear($characterId);

        return ['stage' => 'left'];
    }

    /**
     * @return array<string, mixed>
     */
    public function approach(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-choice') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $row = $this->fetchCharacterRow($characterId);
        $this->adjustTurn($characterId, -1);

        $roll = \random_int(1, 15);

        if ($roll === 1) {
            return $this->applyDeath($characterId, $row);
        }

        if ($roll <= 3) {
            return $this->applyDrain($characterId, $row);
        }

        if ($roll <= 7) {
            $this->eventState->store($characterId, self::EVENT_CODE, ['stage' => 'awaiting-gem']);

            return [
                'stage' => 'awaiting-gem',
                'outcome' => self::OUTCOME_GEM_REQUEST,
                'gem' => (int) $row['gem'],
            ];
        }

        $this->eventState->clear($characterId);

        return ['stage' => 'resolved', 'outcome' => self::OUTCOME_SCARED_OFF];
    }

    /**
     * @return array<string, mixed>
     */
    public function keepGem(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-gem') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $this->eventState->clear($characterId);

        return ['stage' => 'resolved', 'outcome' => 'gem-kept'];
    }

    /**
     * @return array<string, mixed>
     */
    public function giveGem(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-gem') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $row = $this->fetchCharacterRow($characterId);
        $this->eventState->clear($characterId);

        if ((int) $row['gem'] < 1) {
            return ['stage' => 'resolved', 'outcome' => 'no-gem'];
        }

        $this->connection
            ->prepare('UPDATE character_wealth SET gem = gem - 1 WHERE character_id = :character_id')
            ->execute(['character_id' => $characterId]);

        if (\random_int(0, 5) === 5) {
            return ['stage' => 'resolved', 'outcome' => self::OUTCOME_GEM_STOLEN, 'gem_lost' => 1];
        }

        $favorGained = \random_int(self::FAVOR_MINIMUM, self::FAVOR_MAXIMUM);

        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET death_power = death_power + :favor
                  WHERE character_id = :character_id',
            )
            ->execute(['favor' => $favorGained, 'character_id' => $characterId]);

        return [
            'stage' => 'resolved',
            'outcome' => self::OUTCOME_FAVOR,
            'gem_lost' => 1,
            'favor_gained' => $favorGained,
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function applyDeath(int $characterId, array $row): array
    {
        $this->eventState->clear($characterId);

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_vital
                        SET is_alive       = 0,
                            hit_point      = 0,
                            killed_in_area = :killed_in_area
                      WHERE character_id = :character_id',
                )
                ->execute(['killed_in_area' => 'forest', 'character_id' => $characterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_progression
                        SET experience = :experience
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'experience' => (int) \round((int) $row['experience'] * self::DEATH_EXPERIENCE_RATE),
                    'character_id' => $characterId,
                ]);

            $this->connection
                ->prepare('UPDATE character_wealth SET gold = 0 WHERE character_id = :character_id')
                ->execute(['character_id' => $characterId]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        $this->newsService->publish(
            \sprintf('`2%s`0 special.necromancer.news.stripped', (string) $row['display_name']),
            (int) $row['account_id'],
        );

        return ['stage' => 'resolved', 'outcome' => self::OUTCOME_DEATH];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function applyDrain(int $characterId, array $row): array
    {
        $this->eventState->clear($characterId);

        $maxHitPointLost = (int) $row['max_hit_point'] > 1 ? 1 : 0;

        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET max_hit_point = MAX(1, max_hit_point - :max_hit_point_lost),
                        hit_point     = 1
                  WHERE character_id = :character_id',
            )
            ->execute([
                'max_hit_point_lost' => $maxHitPointLost,
                'character_id' => $characterId,
            ]);

        $this->newsService->publish(
            \sprintf('`2%s`0 special.necromancer.news.diminished', (string) $row['display_name']),
            (int) $row['account_id'],
        );

        return [
            'stage' => 'resolved',
            'outcome' => self::OUTCOME_DRAINED,
            'max_hit_point_lost' => $maxHitPointLost,
            'hit_point' => 1,
        ];
    }

    private function adjustTurn(int $characterId, int $delta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn = MAX(0, forest_turn + :delta)
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
            'SELECT game_character.account_id,
                    game_character.display_name,
                    character_vital.max_hit_point,
                    character_progression.experience,
                    character_wealth.gem
               FROM game_character
               JOIN character_vital       ON character_vital.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
               JOIN character_wealth      ON character_wealth.character_id = game_character.character_id
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
