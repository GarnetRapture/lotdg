<?php

declare(strict_types=1);

namespace Lotdg\Kernel;

use Lotdg\Http\JsonResponse;
use Lotdg\Http\Middleware\AccessBanMiddleware;
use Lotdg\Http\Middleware\MiddlewareInterface;
use Lotdg\Http\Middleware\RefererTrackingMiddleware;
use Lotdg\Http\RouteRegistry;
use Lotdg\I18n\LabelTranslator;
use Lotdg\I18n\LocaleResolver;
use Lotdg\Persistence\Repository\LocaleRepository;
use Lotdg\Persistence\Sqlite\SqliteConnectionFactory;
use Lotdg\Support\LocalizedException;
use PDO;
use Throwable;

final class HttpKernel
{
    private ?PDO $connection = null;

    /** @var list<MiddlewareInterface> */
    private readonly array $middlewareList;

    /**
     * @param array<string, mixed> $configuration api/config/application.php 의 반환값.
     */
    public function __construct(
        private readonly array $configuration,
        private readonly RouteRegistry $routeRegistry = new RouteRegistry(),
    ) {
        $this->middlewareList = [
            new AccessBanMiddleware(),
            new RefererTrackingMiddleware(),
        ];
    }

    public function handle(string $requestMethod, string $requestUri): void
    {
        try {
            $requestPath = $this->extractPath($requestUri);
            $interception = $this->runMiddleware($requestPath);

            if ($interception !== null) {
                JsonResponse::send($interception['status_code'], $interception['payload']);

                return;
            }

            $route = $this->routeRegistry->match($requestMethod, $requestPath);

            if ($route === null) {
                JsonResponse::send(404, [
                    'error_namespace' => 'system-message',
                    'error_label_path' => 'error.route-not-found',
                    'error_placeholder' => [],
                ]);

                return;
            }

            $controller = $route->createController($this->connection());
            $parameterMap = $route->parameterMap;
            $parameterMap['request_locale_code'] = $this->requestLocaleCode();

            $payload = $controller->handle($parameterMap);

            JsonResponse::send(200, $payload);
        } catch (Throwable $throwable) {
            $this->sendFailure($throwable);
        }
    }

    /**
     * @return array{status_code: int, payload: array<string, mixed>}|null
     */
    private function runMiddleware(string $requestPath): ?array
    {
        /** @var array<string, string> $serverParameterMap */
        $serverParameterMap = \array_filter($_SERVER, \is_string(...));

        foreach ($this->middlewareList as $middleware) {
            $interception = $middleware->process($this->connection(), $requestPath, $serverParameterMap);

            if ($interception !== null) {
                return $interception;
            }
        }

        return null;
    }

    private function extractPath(string $requestUri): string
    {
        $queryPosition = \strpos($requestUri, '?');
        $path = $queryPosition === false ? $requestUri : \substr($requestUri, 0, $queryPosition);

        return '/' . \trim($path, '/');
    }

    private function connection(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        /** @var array{database_file: string} $pathConfiguration */
        $pathConfiguration = $this->configuration['path'];

        $this->connection = (new SqliteConnectionFactory($pathConfiguration['database_file']))
            ->create();

        return $this->connection;
    }

    private function requestLocaleCode(): string
    {
        $requestedLocaleCode = \is_string($_GET['locale'] ?? null) ? $_GET['locale'] : null;

        return (new LocaleResolver($this->connection()))->resolve($requestedLocaleCode);
    }

    private function sendFailure(Throwable $throwable): void
    {
        if ($throwable instanceof LocalizedException) {
            $connection = $this->connection();
            $labelTranslator = new LabelTranslator(
                new LocaleRepository($connection),
                new LocaleResolver($connection),
            );

            JsonResponse::send(
                400,
                $labelTranslator->decorateException($throwable, $this->requestLocaleCode()),
            );

            return;
        }

        $payload = [
            'error_namespace' => 'system-message',
            'error_label_path' => 'error.internal',
            'error_placeholder' => [],
        ];

        if (($this->configuration['debug'] ?? false) === true) {
            $payload['debug'] = [
                'message' => $throwable->getMessage(),
                'type' => $throwable::class,
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ];
        }

        JsonResponse::send(500, $payload);
    }
}
