<?php

declare(strict_types=1);

namespace Lotdg\Domain\Catalog;

use PDO;

final class CreatureEditorService
{
    public const int MINIMUM_LEVEL = 1;

    public const int MAXIMUM_LEVEL = 16;

    /** @var array<int, array{health: int, attack: int, defense: int, experience: int, gold: int}> */
    private const LEVEL_STAT = [
        1 => ['health' => 10, 'attack' => 1, 'defense' => 1, 'experience' => 14, 'gold' => 36],
        2 => ['health' => 21, 'attack' => 3, 'defense' => 3, 'experience' => 24, 'gold' => 97],
        3 => ['health' => 32, 'attack' => 5, 'defense' => 4, 'experience' => 34, 'gold' => 148],
        4 => ['health' => 43, 'attack' => 7, 'defense' => 6, 'experience' => 45, 'gold' => 162],
        5 => ['health' => 53, 'attack' => 9, 'defense' => 7, 'experience' => 55, 'gold' => 198],
        6 => ['health' => 64, 'attack' => 11, 'defense' => 8, 'experience' => 66, 'gold' => 234],
        7 => ['health' => 74, 'attack' => 13, 'defense' => 10, 'experience' => 77, 'gold' => 268],
        8 => ['health' => 84, 'attack' => 15, 'defense' => 11, 'experience' => 89, 'gold' => 302],
        9 => ['health' => 94, 'attack' => 17, 'defense' => 13, 'experience' => 101, 'gold' => 336],
        10 => ['health' => 105, 'attack' => 19, 'defense' => 14, 'experience' => 114, 'gold' => 369],
        11 => ['health' => 115, 'attack' => 21, 'defense' => 15, 'experience' => 127, 'gold' => 402],
        12 => ['health' => 125, 'attack' => 23, 'defense' => 17, 'experience' => 141, 'gold' => 435],
        13 => ['health' => 135, 'attack' => 25, 'defense' => 18, 'experience' => 156, 'gold' => 467],
        14 => ['health' => 145, 'attack' => 27, 'defense' => 20, 'experience' => 172, 'gold' => 499],
        15 => ['health' => 155, 'attack' => 29, 'defense' => 21, 'experience' => 189, 'gold' => 531],
        16 => ['health' => 166, 'attack' => 31, 'defense' => 22, 'experience' => 207, 'gold' => 563],
    ];

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function listAll(): array
    {
        $statement = $this->connection->query(
            'SELECT creature_id, creature_name, creature_level, weapon_name,
                    defeat_message, location_code, created_by_name
               FROM creature
              ORDER BY creature_level ASC, creature_name ASC',
        );

        return [
            'minimum_level' => self::MINIMUM_LEVEL,
            'maximum_level' => self::MAXIMUM_LEVEL,
            'level_stat_map' => self::LEVEL_STAT,
            'creature_list' => \array_map(
                static fn (array $row): array => [
                    'creature_id' => (int) $row['creature_id'],
                    'creature_name' => (string) $row['creature_name'],
                    'creature_level' => (int) $row['creature_level'],
                    'weapon_name' => (string) $row['weapon_name'],
                    'defeat_message' => (string) $row['defeat_message'],
                    'location_code' => (int) $row['location_code'],
                    'created_by_name' => (string) ($row['created_by_name'] ?? ''),
                    'is_editable' => (int) $row['creature_level'] >= self::MINIMUM_LEVEL
                        && (int) $row['creature_level'] <= self::MAXIMUM_LEVEL,
                ],
                $statement === false ? [] : $statement->fetchAll(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function save(
        int $creatureId,
        string $creatureName,
        string $weaponName,
        string $defeatMessage,
        int $creatureLevel,
        int $locationCode,
        string $editorLoginName,
    ): array {
        $stat = self::LEVEL_STAT[$creatureLevel] ?? null;

        if ($stat === null) {
            return ['saved' => false, 'message_key' => 'creature.error.invalid-level'];
        }

        if (\trim($creatureName) === '') {
            return ['saved' => false, 'message_key' => 'creature.error.empty-name'];
        }

        $parameterMap = [
            'creature_name' => \trim($creatureName),
            'weapon_name' => \trim($weaponName),
            'defeat_message' => \trim($defeatMessage),
            'creature_level' => $creatureLevel,
            'health' => $stat['health'],
            'attack_point' => $stat['attack'],
            'defense_point' => $stat['defense'],
            'experience_reward' => $stat['experience'],
            'gold_reward' => $stat['gold'],
            'location_code' => $locationCode === 1 ? 1 : 0,
            'created_by_name' => $editorLoginName,
        ];

        if ($creatureId > 0) {
            $statement = $this->connection->prepare(
                'UPDATE creature
                    SET creature_name     = :creature_name,
                        weapon_name       = :weapon_name,
                        defeat_message    = :defeat_message,
                        creature_level    = :creature_level,
                        health            = :health,
                        attack_point      = :attack_point,
                        defense_point     = :defense_point,
                        experience_reward = :experience_reward,
                        gold_reward       = :gold_reward,
                        location_code     = :location_code,
                        created_by_name   = :created_by_name
                  WHERE creature_id = :creature_id',
            );
            $statement->execute($parameterMap + ['creature_id' => $creatureId]);

            if ($statement->rowCount() === 0) {
                return ['saved' => false, 'message_key' => 'creature.error.not-found'];
            }

            return ['saved' => true, 'creature_id' => $creatureId] + $stat;
        }

        $this->connection
            ->prepare(
                'INSERT INTO creature
                     (creature_name, weapon_name, defeat_message, creature_level,
                      health, attack_point, defense_point, experience_reward,
                      gold_reward, location_code, created_by_name)
                 VALUES
                     (:creature_name, :weapon_name, :defeat_message, :creature_level,
                      :health, :attack_point, :defense_point, :experience_reward,
                      :gold_reward, :location_code, :created_by_name)',
            )
            ->execute($parameterMap);

        return [
            'saved' => true,
            'creature_id' => (int) $this->connection->lastInsertId(),
        ] + $stat;
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(int $creatureId): array
    {
        $statement = $this->connection->prepare(
            'DELETE FROM creature
              WHERE creature_id = :creature_id
                AND creature_level BETWEEN :minimum_level AND :maximum_level',
        );
        $statement->execute([
            'creature_id' => $creatureId,
            'minimum_level' => self::MINIMUM_LEVEL,
            'maximum_level' => self::MAXIMUM_LEVEL,
        ]);

        return ['removed' => $statement->rowCount() > 0];
    }
}
