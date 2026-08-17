<?php

declare(strict_types=1);

namespace Lotdg\Http;

use PDO;

final class MatchedRoute
{
    /**
     * @param class-string<ControllerInterface> $controllerClassName
     * @param array<string, string>             $parameterMap
     */
    public function __construct(
        public readonly string $controllerClassName,
        public readonly array $parameterMap,
    ) {
    }

    public function createController(PDO $connection): ControllerInterface
    {
        /** @var ControllerInterface $controller */
        $controller = new ($this->controllerClassName)($connection);

        return $controller;
    }
}
