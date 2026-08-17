<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Support\LocalizedException;
use PDO;

final class InstantRewardEvent
{
    public const string EVENT_FIND_GEM = 'findgem';

    public const string EVENT_FIND_GOLD = 'findgold';

    public const string EVENT_OLD_MAN_PRETTY = 'oldmanpretty';

    public const string EVENT_OLD_MAN_UGLY = 'oldmanugly';

    private const int GOLD_MINIMUM_PER_LEVEL = 10;

    private const int GOLD_MAXIMUM_PER_LEVEL = 50;

    /** @var list<string> */
    public const EVENT_CODE_LIST = [
        self::EVENT_FIND_GEM,
        self::EVENT_FIND_GOLD,
        self::EVENT_OLD_MAN_PRETTY,
        self::EVENT_OLD_MAN_UGLY,
    ];

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function trigger(int $characterId, string $eventCode): array
    {
        return match ($eventCode) {
            self::EVENT_FIND_GEM => $this->findGem($characterId),
            self::EVENT_FIND_GOLD => $this->findGold($characterId),
            self::EVENT_OLD_MAN_PRETTY => $this->prettyStick($characterId),
            self::EVENT_OLD_MAN_UGLY => $this->uglyStick($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-special-event'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function findGem(int $characterId): array
    {
        $this->connection
            ->prepare('UPDATE character_wealth SET gem = gem + 1 WHERE character_id = :character_id')
            ->execute(['character_id' => $characterId]);

        return [
            'event_code' => self::EVENT_FIND_GEM,
            'outcome' => 'gem-found',
            'gem_gained' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function findGold(int $characterId): array
    {
        $level = $this->fetchLevel($characterId);
        $goldGained = \random_int(
            $level * self::GOLD_MINIMUM_PER_LEVEL,
            $level * self::GOLD_MAXIMUM_PER_LEVEL,
        );

        $this->connection
            ->prepare('UPDATE character_wealth SET gold = gold + :gold WHERE character_id = :character_id')
            ->execute(['gold' => $goldGained, 'character_id' => $characterId]);

        return [
            'event_code' => self::EVENT_FIND_GOLD,
            'outcome' => 'gold-found',
            'gold_gained' => $goldGained,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prettyStick(int $characterId): array
    {
        $this->connection
            ->prepare(
                'UPDATE character_social SET charm = charm + 1 WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return [
            'event_code' => self::EVENT_OLD_MAN_PRETTY,
            'outcome' => 'charm-gained',
            'charm_gained' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function uglyStick(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT charm FROM character_social WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $charm = $statement->fetchColumn();

        if ($charm === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        if ((int) $charm <= 0) {
            return [
                'event_code' => self::EVENT_OLD_MAN_UGLY,
                'outcome' => 'stick-broke',
                'charm_lost' => 0,
                'stick_broke' => true,
            ];
        }

        $this->connection
            ->prepare(
                'UPDATE character_social
                    SET charm = MAX(0, charm - 1)
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return [
            'event_code' => self::EVENT_OLD_MAN_UGLY,
            'outcome' => 'charm-lost',
            'charm_lost' => 1,
            'stick_broke' => false,
        ];
    }

    private function fetchLevel(int $characterId): int
    {
        $statement = $this->connection->prepare(
            'SELECT level FROM game_character WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $level = $statement->fetchColumn();

        if ($level === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return \max(1, (int) $level);
    }
}
