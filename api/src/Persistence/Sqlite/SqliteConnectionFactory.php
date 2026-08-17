<?php

declare(strict_types=1);

namespace Lotdg\Persistence\Sqlite;

use PDO;
use PDOException;
use RuntimeException;

final class SqliteConnectionFactory
{
    private const array CONNECTION_PRAGMA = [
        'foreign_keys' => 'ON',
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'busy_timeout' => '5000',
    ];

    public function __construct(
        private readonly string $databaseFilePath,
    ) {
    }

    /**
     * @throws RuntimeException 
     */
    public function create(): PDO
    {
        $directoryPath = \dirname($this->databaseFilePath);

        if (!\is_dir($directoryPath)) {
            throw new RuntimeException(
                \sprintf('데이터베이스 디렉터리가 존재하지 않습니다: %s', $directoryPath),
            );
        }

        try {
            $connection = new PDO(
                \sprintf('sqlite:%s', $this->databaseFilePath),
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ],
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                \sprintf('SQLite 연결에 실패했습니다: %s', $this->databaseFilePath),
                0,
                $exception,
            );
        }

        foreach (self::CONNECTION_PRAGMA as $pragmaName => $pragmaValue) {
            $connection->exec(\sprintf('PRAGMA %s = %s', $pragmaName, $pragmaValue));
        }

        return $connection;
    }
}
