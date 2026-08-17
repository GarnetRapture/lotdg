<?php

declare(strict_types=1);

namespace Lotdg\Persistence\Repository;

use PDO;

final class CreatureRepository
{
    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRandomByLevel(int $creatureLevel, int $locationCode): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT creature_id, creature_name, creature_level, weapon_name,
                    victory_message, defeat_message, gold_reward, experience_reward,
                    health, attack_point, defense_point, location_code
               FROM creature
              WHERE creature_level = :creature_level
                AND location_code  = :location_code
              ORDER BY RANDOM()
              LIMIT 1',
        );
        $statement->execute([
            'creature_level' => $creatureLevel,
            'location_code' => $locationCode,
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findNearestByLevel(int $creatureLevel, int $locationCode): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT creature_id, creature_name, creature_level, weapon_name,
                    victory_message, defeat_message, gold_reward, experience_reward,
                    health, attack_point, defense_point, location_code
               FROM creature
              WHERE location_code = :location_code
              ORDER BY ABS(creature_level - :creature_level) ASC, RANDOM() ASC
              LIMIT 1',
        );
        $statement->execute([
            'creature_level' => $creatureLevel,
            'location_code' => $locationCode,
        ]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findTrainingMasterByLevel(int $masterLevel, int $dragonKillCount = 0): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT master_id, master_name, master_level, weapon_name,
                    victory_message, defeat_message, health, attack_point, defense_point
               FROM training_master
              WHERE master_level = :master_level
              ORDER BY master_id ASC',
        );
        $statement->execute(['master_level' => $masterLevel]);

        $rowList = $statement->fetchAll();

        if ($rowList === []) {
            return null;
        }

        return $rowList[\max(0, $dragonKillCount) % \count($rowList)];
    }
}
