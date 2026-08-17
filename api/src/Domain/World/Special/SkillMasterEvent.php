<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Support\LocalizedException;
use PDO;

final class SkillMasterEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'skillmaster';

    /** @var array<int, string> */
    private const RANK_COLUMN_BY_SPECIALTY = [
        1 => 'dark_arts_rank',
        2 => 'mystical_power_rank',
        3 => 'thievery_rank',
    ];

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
        $row = $this->fetchCharacterRow($characterId);
        $specialtyCode = (int) $row['specialty_code'];

        if (!isset(self::RANK_COLUMN_BY_SPECIALTY[$specialtyCode])) {
            $this->eventState->clear($characterId);

            return ['stage' => 'resolved', 'outcome' => 'no-specialty'];
        }

        $this->eventState->store($characterId, self::EVENT_CODE, ['stage' => 'awaiting-choice']);

        return [
            'stage' => 'awaiting-choice',
            'specialty_code' => $specialtyCode,
            'gem' => (int) $row['gem'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function refuse(int $characterId): array
    {
        $this->eventState->clear($characterId);

        return ['stage' => 'resolved', 'outcome' => 'refused'];
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
        $specialtyCode = (int) $row['specialty_code'];
        $rankColumn = self::RANK_COLUMN_BY_SPECIALTY[$specialtyCode] ?? null;

        $this->eventState->clear($characterId);

        if ($rankColumn === null) {
            return ['stage' => 'resolved', 'outcome' => 'no-specialty'];
        }

        if ((int) $row['gem'] < 1) {
            return ['stage' => 'resolved', 'outcome' => 'no-gem'];
        }

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare('UPDATE character_wealth SET gem = gem - 1 WHERE character_id = :character_id')
                ->execute(['character_id' => $characterId]);

            $this->connection
                ->prepare(
                    \sprintf(
                        'UPDATE character_specialty SET %s = %s + 1 WHERE character_id = :character_id',
                        $rankColumn,
                        $rankColumn,
                    ),
                )
                ->execute(['character_id' => $characterId]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'stage' => 'resolved',
            'outcome' => 'rank-gained',
            'specialty_code' => $specialtyCode,
            'gem_spent' => 1,
            'rank_gained' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCharacterRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT character_specialty.specialty_code,
                    character_wealth.gem
               FROM game_character
               JOIN character_specialty ON character_specialty.character_id = game_character.character_id
               JOIN character_wealth    ON character_wealth.character_id = game_character.character_id
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
