<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use PDO;

final class SpecialEventState
{
    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function load(int $characterId, string $eventCode): array
    {
        $statement = $this->connection->prepare(
            'SELECT special_include_name, special_misc_json
               FROM character_session_state
              WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false || (string) $row['special_include_name'] !== $eventCode) {
            return [];
        }

        $decoded = \json_decode((string) $row['special_misc_json'], true);

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $state
     */
    public function store(int $characterId, string $eventCode, array $state): void
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
                'special_include_name' => $eventCode,
                'special_misc_json' => $encoded === false ? '{}' : $encoded,
                'character_id' => $characterId,
            ]);
    }

    public function clear(int $characterId): void
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
}
