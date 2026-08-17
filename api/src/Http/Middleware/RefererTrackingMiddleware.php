<?php

declare(strict_types=1);

namespace Lotdg\Http\Middleware;

use Lotdg\Persistence\Repository\GameSettingRepository;
use PDO;

final class RefererTrackingMiddleware implements MiddlewareInterface
{
    private const int EXPIRE_DAY_DEFAULT = 180;

    /**
     * @param array<string, string> $serverParameterMap
     *
     * @return array{status_code: int, payload: array<string, mixed>}|null
     */
    public function process(PDO $connection, string $requestPath, array $serverParameterMap): ?array
    {
        unset($requestPath);

        $refererUri = $serverParameterMap['HTTP_REFERER'] ?? '';

        if ($refererUri === '' || $this->isOwnSite($refererUri, $serverParameterMap)) {
            return null;
        }

        $this->purgeExpired($connection);

        $connection
            ->prepare(
                'INSERT INTO referer_hit (referer_uri, site_host, hit_count, last_hit_at)
                 VALUES (:referer_uri, :site_host, 1, datetime(\'now\'))
                 ON CONFLICT(referer_uri)
                 DO UPDATE SET hit_count   = hit_count + 1,
                               site_host   = excluded.site_host,
                               last_hit_at = excluded.last_hit_at',
            )
            ->execute([
                'referer_uri' => $refererUri,
                'site_host' => $this->extractHost($refererUri),
            ]);

        return null;
    }

    /**
     * @param array<string, string> $serverParameterMap
     */
    private function isOwnSite(string $refererUri, array $serverParameterMap): bool
    {
        $serverName = $serverParameterMap['SERVER_NAME'] ?? '';

        if ($serverName === '') {
            return false;
        }

        return $this->extractHost($refererUri) === $serverName;
    }

    private function extractHost(string $refererUri): string
    {
        $host = \preg_replace('#^https?://#i', '', $refererUri) ?? $refererUri;
        $slashPosition = \strpos($host, '/');

        return $slashPosition === false ? $host : \substr($host, 0, $slashPosition);
    }

    private function purgeExpired(PDO $connection): void
    {
        $expireDay = (new GameSettingRepository($connection))
            ->getInt('expirecontent', self::EXPIRE_DAY_DEFAULT);

        $connection
            ->prepare(
                'DELETE FROM referer_hit
                  WHERE last_hit_at IS NOT NULL
                    AND last_hit_at < datetime(\'now\', :expire_offset)',
            )
            ->execute(['expire_offset' => \sprintf('-%d days', $expireDay)]);
    }
}
