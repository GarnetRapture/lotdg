<?php

declare(strict_types=1);

namespace Lotdg\Http\Middleware;

use PDO;

interface MiddlewareInterface
{
    /**
     * @param array<string, string> $serverParameterMap
     *
     * @return array{status_code: int, payload: array<string, mixed>}|null
     */
    public function process(PDO $connection, string $requestPath, array $serverParameterMap): ?array;
}
