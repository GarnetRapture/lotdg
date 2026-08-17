<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\HallOfFameService;
use Lotdg\Http\ControllerInterface;
use PDO;

final class HallOfFameController implements ControllerInterface
{
    private readonly HallOfFameService $hallOfFameService;

    public function __construct(PDO $connection)
    {
        $this->hallOfFameService = new HallOfFameService($connection);
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        unset($parameterMap);

        return $this->hallOfFameService->build();
    }
}
