<?php

declare(strict_types=1);

use Lotdg\Persistence\Sqlite\SqliteConnectionFactory;
use Lotdg\Persistence\Sqlite\SqliteMigrationRunner;

if (\PHP_SAPI !== 'cli') {
    \http_response_code(403);
    echo '이 스크립트는 CLI 에서만 실행할 수 있습니다.';

    exit(1);
}

$projectRootPath = \dirname(__DIR__);
$autoloadFilePath = $projectRootPath . '/vendor/autoload.php';

if (!\is_file($autoloadFilePath)) {
    \fwrite(\STDERR, "composer install 이 필요합니다.\n");

    exit(1);
}

require $autoloadFilePath;

/** @var array{path: array{database_file: string, migration_directory: string}} $configuration */
$configuration = require $projectRootPath . '/config/application.php';

$databaseFilePath = $configuration['path']['database_file'];
$databaseDirectoryPath = \dirname($databaseFilePath);

if (!\is_dir($databaseDirectoryPath) && !\mkdir($databaseDirectoryPath, 0o775, true)) {
    \fwrite(\STDERR, \sprintf("데이터베이스 디렉터리를 만들 수 없습니다: %s\n", $databaseDirectoryPath));

    exit(1);
}

$connection = (new SqliteConnectionFactory($databaseFilePath))->create();
$runner = new SqliteMigrationRunner($connection, $configuration['path']['migration_directory']);

$appliedNameList = $runner->migrate();

if ($appliedNameList === []) {
    echo "적용할 마이그레이션이 없습니다.\n";

    exit(0);
}

foreach ($appliedNameList as $appliedName) {
    echo \sprintf("적용: %s\n", $appliedName);
}

echo \sprintf("총 %d개 마이그레이션을 적용했습니다.\n", \count($appliedNameList));
