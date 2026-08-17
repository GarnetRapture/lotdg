<?php

declare(strict_types=1);

namespace Lotdg\Http\Middleware;

use PDO;

final class AccessBanMiddleware implements MiddlewareInterface
{
    public const string UNIQUE_COOKIE_NAME = 'lotdg_unique_id';

    /**
     * @param array<string, string> $serverParameterMap
     *
     * @return array{status_code: int, payload: array<string, mixed>}|null
     */
    public function process(PDO $connection, string $requestPath, array $serverParameterMap): ?array
    {
        unset($requestPath);

        $connection
            ->prepare('DELETE FROM access_ban WHERE ban_expire_date IS NOT NULL AND ban_expire_date < date(\'now\')')
            ->execute();

        $ipAddress = $serverParameterMap['REMOTE_ADDR'] ?? '';
        $uniqueCookieId = \is_string($_COOKIE[self::UNIQUE_COOKIE_NAME] ?? null)
            ? $_COOKIE[self::UNIQUE_COOKIE_NAME]
            : '';

        $statement = $connection->prepare(
            'SELECT ban_reason, ban_expire_date
               FROM access_ban
              WHERE (ip_prefix        <> \'\' AND :ip_address LIKE ip_prefix || \'%\')
                 OR (unique_cookie_id <> \'\' AND unique_cookie_id = :unique_cookie_id)
              LIMIT 1',
        );
        $statement->execute([
            'ip_address' => $ipAddress,
            'unique_cookie_id' => $uniqueCookieId,
        ]);

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return [
            'status_code' => 403,
            'payload' => [
                'error_namespace' => 'system-message',
                'error_label_path' => 'error.access-banned',
                'error_placeholder' => [
                    'reason' => (string) $row['ban_reason'],
                    'expire_date' => $row['ban_expire_date'] === null
                        ? ''
                        : (string) $row['ban_expire_date'],
                ],
            ],
        ];
    }
}
