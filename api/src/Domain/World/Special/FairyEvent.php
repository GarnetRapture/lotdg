<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Support\LocalizedException;
use PDO;

final class FairyEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'fairy1';

    public const string OUTCOME_TURN = 'turn';

    public const string OUTCOME_GEM = 'gem';

    public const string OUTCOME_MAX_HIT_POINT = 'max-hit-point';

    public const string OUTCOME_SPECIALTY = 'specialty';

    private const int GEM_REWARD = 2;

    public function __construct(
        private readonly PDO $connection,
        private readonly SpecialEventState $eventState,
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
    public function give(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-choice') {
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

        return match (\random_int(1, 7)) {
            1 => $this->grantTurn($characterId),
            2, 3 => $this->grantGem($characterId),
            4, 5 => $this->grantMaxHitPoint($characterId),
            default => $this->grantSpecialty($characterId),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function refuse(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-choice') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $this->eventState->clear($characterId);

        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn = MAX(0, forest_turn - 1)
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return ['stage' => 'resolved', 'outcome' => 'refused', 'turn_lost' => 1];
    }

    /**
     * @return array<string, mixed>
     */
    private function grantTurn(int $characterId): array
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn = forest_turn + 1
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return ['stage' => 'resolved', 'outcome' => self::OUTCOME_TURN, 'turn_gained' => 1];
    }

    /**
     * @return array<string, mixed>
     */
    private function grantGem(int $characterId): array
    {
        $this->connection
            ->prepare(
                'UPDATE character_wealth SET gem = gem + :gem WHERE character_id = :character_id',
            )
            ->execute(['gem' => self::GEM_REWARD, 'character_id' => $characterId]);

        return [
            'stage' => 'resolved',
            'outcome' => self::OUTCOME_GEM,
            'gem_gained' => self::GEM_REWARD,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function grantMaxHitPoint(int $characterId): array
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET max_hit_point = max_hit_point + 1,
                        hit_point     = hit_point + 1
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return [
            'stage' => 'resolved',
            'outcome' => self::OUTCOME_MAX_HIT_POINT,
            'max_hit_point_gained' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function grantSpecialty(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT specialty_code FROM character_specialty WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $specialtyCode = (int) $statement->fetchColumn();

        $column = match ($specialtyCode) {
            1 => 'dark_arts_rank',
            2 => 'mystical_power_rank',
            3 => 'thievery_rank',
            default => null,
        };

        if ($column === null) {
            return ['stage' => 'resolved', 'outcome' => self::OUTCOME_SPECIALTY, 'point_gained' => 0];
        }

        $this->connection
            ->prepare(
                \sprintf(
                    'UPDATE character_specialty SET %s = %s + 1 WHERE character_id = :character_id',
                    $column,
                    $column,
                ),
            )
            ->execute(['character_id' => $characterId]);

        return [
            'stage' => 'resolved',
            'outcome' => self::OUTCOME_SPECIALTY,
            'specialty_code' => $specialtyCode,
            'point_gained' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCharacterRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT character_wealth.gem
               FROM game_character
               JOIN character_wealth ON character_wealth.character_id = game_character.character_id
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
