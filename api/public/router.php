<?php

declare(strict_types=1);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$queryPosition = \strpos($requestUri, '?');
$requestPath = $queryPosition === false ? $requestUri : \substr($requestUri, 0, $queryPosition);

$candidateFilePath = __DIR__ . \urldecode($requestPath);

if ($requestPath !== '/' && \is_file($candidateFilePath)) {
    return false;
}

require __DIR__ . '/index.php';
