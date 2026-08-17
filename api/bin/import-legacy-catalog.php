<?php

declare(strict_types=1);

use Lotdg\Persistence\Repository\LegacyCatalogImporter;
use Lotdg\Persistence\Sqlite\SqliteConnectionFactory;

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

/** @var array{path: array{database_file: string}} $configuration */
$configuration = require $projectRootPath . '/config/application.php';

$legacySqlFilePath = $argv[1] ?? \dirname($projectRootPath) . '/reference/logd-0.9.7-create.sql';

if (!\is_file($legacySqlFilePath)) {
    \fwrite(\STDERR, \sprintf("레거시 SQL 파일이 없습니다: %s\n", $legacySqlFilePath));

    exit(1);
}

$connection = (new SqliteConnectionFactory($configuration['path']['database_file']))->create();
$countMap = (new LegacyCatalogImporter($connection))->import($legacySqlFilePath);

foreach ($countMap as $tableName => $rowCount) {
    echo \sprintf("%s: %d건\n", $tableName, $rowCount);
}
