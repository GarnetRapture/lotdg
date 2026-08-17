<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\GameClock;
use Lotdg\Domain\World\GameInformationService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use PDO;

final class GameInformationController implements ControllerInterface
{
    private readonly GameInformationService $gameInformationService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->gameInformationService = new GameInformationService(
            $gameSettingRepository,
            new GameClock($gameSettingRepository),
        );
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        unset($parameterMap);

        return $this->gameInformationService->describe();
    }
}
