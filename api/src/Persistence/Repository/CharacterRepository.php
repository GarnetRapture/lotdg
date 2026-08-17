<?php

declare(strict_types=1);

namespace Lotdg\Persistence\Repository;

use PDO;

final class CharacterRepository
{
    private const array SUBSIDIARY_TABLE_NAME_LIST = [
        'character_vital',
        'character_combat_stat',
        'character_progression',
        'character_specialty',
        'character_equipment',
        'character_wealth',
        'character_daily_allowance',
        'character_social',
        'character_session_state',
    ];

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    public function createForAccount(
        int $accountId,
        string $displayName,
        int $sexCode,
        int $raceCode,
        string $rankTitle,
        int $startingGold,
        int $forestTurn,
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO game_character
                 (account_id, display_name, sex_code, race_code, rank_title)
             VALUES
                 (:account_id, :display_name, :sex_code, :race_code, :rank_title)',
        );
        $statement->execute([
            'account_id' => $accountId,
            'display_name' => $displayName,
            'sex_code' => $sexCode,
            'race_code' => $raceCode,
            'rank_title' => $rankTitle,
        ]);

        $characterId = (int) $this->connection->lastInsertId();

        foreach (self::SUBSIDIARY_TABLE_NAME_LIST as $tableName) {
            $this->connection
                ->prepare(\sprintf('INSERT INTO %s (character_id) VALUES (:character_id)', $tableName))
                ->execute(['character_id' => $characterId]);
        }

        $this->connection
            ->prepare('UPDATE character_wealth SET gold = :gold WHERE character_id = :character_id')
            ->execute(['gold' => $startingGold, 'character_id' => $characterId]);

        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn = :forest_turn
                  WHERE character_id = :character_id',
            )
            ->execute(['forest_turn' => $forestTurn, 'character_id' => $characterId]);

        return $characterId;
    }

    public function findIdByAccountId(int $accountId): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT character_id FROM game_character WHERE account_id = :account_id',
        );
        $statement->execute(['account_id' => $accountId]);

        $characterId = $statement->fetchColumn();

        return $characterId === false ? null : (int) $characterId;
    }

    public function existsDisplayName(string $displayName): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM game_character WHERE display_name = :display_name',
        );
        $statement->execute(['display_name' => $displayName]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    public function findBundle(int $characterId): ?array
    {
        $characterRow = $this->fetchSingleRow(
            'SELECT character_id, account_id, display_name, sex_code, race_code, level,
                    rank_title, custom_title, location_code, restore_page_uri
               FROM game_character
              WHERE character_id = :character_id',
            $characterId,
        );

        if ($characterRow === null) {
            return null;
        }

        return [
            'character' => $characterRow,
            'vital' => $this->fetchSingleRow(
                'SELECT * FROM character_vital WHERE character_id = :character_id',
                $characterId,
            ) ?? [],
            'combat_stat' => $this->decodeJsonField(
                $this->fetchSingleRow(
                    'SELECT * FROM character_combat_stat WHERE character_id = :character_id',
                    $characterId,
                ) ?? [],
                ['buff_list_json', 'buff_backup_json', 'current_enemy_json'],
            ),
            'progression' => $this->decodeJsonField(
                $this->fetchSingleRow(
                    'SELECT * FROM character_progression WHERE character_id = :character_id',
                    $characterId,
                ) ?? [],
                ['dragon_point_json'],
            ),
            'specialty' => $this->fetchSingleRow(
                'SELECT * FROM character_specialty WHERE character_id = :character_id',
                $characterId,
            ) ?? [],
            'equipment' => $this->fetchSingleRow(
                'SELECT * FROM character_equipment WHERE character_id = :character_id',
                $characterId,
            ) ?? [],
            'wealth' => $this->fetchSingleRow(
                'SELECT * FROM character_wealth WHERE character_id = :character_id',
                $characterId,
            ) ?? [],
            'daily_allowance' => $this->fetchSingleRow(
                'SELECT * FROM character_daily_allowance WHERE character_id = :character_id',
                $characterId,
            ) ?? [],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchSingleRow(string $sql, int $characterId): ?array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string>         $jsonFieldNameList
     *
     * @return array<string, mixed>
     */
    private function decodeJsonField(array $row, array $jsonFieldNameList): array
    {
        foreach ($jsonFieldNameList as $fieldName) {
            if (!\is_string($row[$fieldName] ?? null)) {
                continue;
            }

            $decoded = \json_decode($row[$fieldName], true);
            $row[$fieldName] = \is_array($decoded) ? $decoded : [];
        }

        return $row;
    }
}
