<?php

declare(strict_types=1);

use Lotdg\Kernel\HttpKernel;

$autoloadFilePath = \dirname(__DIR__) . '/vendor/autoload.php';

if (!\is_file($autoloadFilePath)) {
    \http_response_code(500);
    \header('Content-Type: application/json; charset=utf-8');
    echo \json_encode(
        ['error' => 'composer install 이 필요합니다.'],
        \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES,
    );

    return;
}

require $autoloadFilePath;

/** @var array<string, mixed> $configuration */
$configuration = require \dirname(__DIR__) . '/config/application.php';

$kernel = new HttpKernel($configuration);
$kernel->handle(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/',
);
