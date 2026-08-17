<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use PDO;

final class OldManTownEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'oldmantown';

    public const string OUTCOME_CHARM = 'charm';

    public const string OUTCOME_GEM = 'gem';

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
    public function decline(int $characterId): array
    {
        $this->eventState->clear($characterId);

        return ['stage' => 'declined'];
    }

    /**
     * @return array<string, mixed>
     */
    public function escort(int $characterId): array
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

        if (\random_int(0, 1) === 0) {
            $this->connection
                ->prepare(
                    'UPDATE character_social SET charm = charm + 1 WHERE character_id = :character_id',
                )
                ->execute(['character_id' => $characterId]);

            return [
                'stage' => 'resolved',
                'outcome' => self::OUTCOME_CHARM,
                'charm_gained' => 1,
                'turn_lost' => 1,
            ];
        }

        $this->connection
            ->prepare('UPDATE character_wealth SET gem = gem + 1 WHERE character_id = :character_id')
            ->execute(['character_id' => $characterId]);

        return [
            'stage' => 'resolved',
            'outcome' => self::OUTCOME_GEM,
            'gem_gained' => 1,
            'turn_lost' => 1,
        ];
    }
}
