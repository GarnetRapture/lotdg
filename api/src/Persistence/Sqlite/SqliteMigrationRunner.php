<?php

declare(strict_types=1);

namespace Lotdg\Persistence\Sqlite;

use PDO;
use RuntimeException;

final class SqliteMigrationRunner
{
    private const string MIGRATION_FILE_PATTERN = '/^\d{4}_[a-z0-9_]+\.sql$/';

    private const string HISTORY_TABLE_NAME = 'schema_migration';

    public function __construct(
        private readonly PDO $connection,
        private readonly string $migrationDirectoryPath,
    ) {
    }

    /**
     * @return list<string> 
     *
     * @throws RuntimeException 
     */
    public function migrate(): array
    {
        $appliedNameList = [];

        foreach ($this->collectMigrationFileNameList() as $migrationFileName) {
            if ($this->isAlreadyApplied($migrationFileName)) {
                continue;
            }

            $this->applyMigrationFile($migrationFileName);
            $this->recordApplication($migrationFileName);

            $appliedNameList[] = $migrationFileName;
        }

        return $appliedNameList;
    }

    /**
     * @return list<string>
     */
    private function collectMigrationFileNameList(): array
    {
        if (!\is_dir($this->migrationDirectoryPath)) {
            throw new RuntimeException(
                \sprintf('마이그레이션 디렉터리가 없습니다: %s', $this->migrationDirectoryPath),
            );
        }

        $entryList = \scandir($this->migrationDirectoryPath);

        if ($entryList === false) {
            throw new RuntimeException(
                \sprintf('마이그레이션 디렉터리를 읽을 수 없습니다: %s', $this->migrationDirectoryPath),
            );
        }

        $migrationFileNameList = \array_values(
            \array_filter(
                $entryList,
                static fn (string $entryName): bool
                    => \preg_match(self::MIGRATION_FILE_PATTERN, $entryName) === 1,
            ),
        );

        \sort($migrationFileNameList, \SORT_STRING);

        return $migrationFileNameList;
    }

    private function applyMigrationFile(string $migrationFileName): void
    {
        $migrationFilePath = $this->migrationDirectoryPath . \DIRECTORY_SEPARATOR . $migrationFileName;
        $migrationSql = \file_get_contents($migrationFilePath);

        if ($migrationSql === false) {
            throw new RuntimeException(
                \sprintf('마이그레이션 파일을 읽을 수 없습니다: %s', $migrationFilePath),
            );
        }

        $this->connection->exec($migrationSql);
    }

    private function isAlreadyApplied(string $migrationFileName): bool
    {
        if (!$this->hasHistoryTable()) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT 1 FROM ' . self::HISTORY_TABLE_NAME . ' WHERE migration_name = :migration_name',
        );
        $statement->execute(['migration_name' => $migrationFileName]);

        return $statement->fetchColumn() !== false;
    }

    private function recordApplication(string $migrationFileName): void
    {
        if (!$this->hasHistoryTable()) {
            return;
        }

        $statement = $this->connection->prepare(
            'INSERT OR IGNORE INTO ' . self::HISTORY_TABLE_NAME
            . ' (migration_name) VALUES (:migration_name)',
        );
        $statement->execute(['migration_name' => $migrationFileName]);
    }

    private function hasHistoryTable(): bool
    {
        $statement = $this->connection->prepare(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :table_name",
        );
        $statement->execute(['table_name' => self::HISTORY_TABLE_NAME]);

        return $statement->fetchColumn() !== false;
    }
}
