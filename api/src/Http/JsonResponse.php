<?php

declare(strict_types=1);

namespace Lotdg\Http;

final class JsonResponse
{
    private const int ENCODE_FLAG = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES;

    /**
     * @param array<string, mixed>|list<mixed> $payload
     */
    public static function send(int $statusCode, array $payload): void
    {
        \http_response_code($statusCode);
        \header('Content-Type: application/json; charset=utf-8');

        echo \json_encode($payload, self::ENCODE_FLAG);
    }
}
