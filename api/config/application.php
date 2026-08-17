<?php

declare(strict_types=1);

$projectRootPath = \dirname(__DIR__);

return [
    'environment' => \getenv('LOTDG_ENVIRONMENT') ?: 'production',

    'debug' => \getenv('LOTDG_DEBUG') === '1',

    'path' => [
        'root' => $projectRootPath,
        'database_file' => $projectRootPath . '/database/storage/lotdg.sqlite',
        'migration_directory' => $projectRootPath . '/database/migration',
        'seed_directory' => $projectRootPath . '/database/seed',
    ],

    'session' => [
        'idle_timeout_second' => 900,
        'cookie_name' => 'lotdg_session',
    ],

    'localization' => [
        'supported_locale_code' => ['en', 'ko', 'ja', 'zh', 'ru'],
        'fallback_locale_code' => 'en',
    ],
];
