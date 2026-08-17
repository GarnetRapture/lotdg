<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\NewsService;
use Lotdg\Domain\World\GameClock;
use Lotdg\Domain\World\OuthouseService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class OuthouseController implements ControllerInterface
{
    private readonly OuthouseService $outhouseService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->outhouseService = new OuthouseService(
            $connection,
            new NewsService($connection, $gameSettingRepository, new GameClock($gameSettingRepository)),
        );
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        $characterId = (int) ($parameterMap['character_id'] ?? 0);

        if ($characterId <= 0) {
            throw new LocalizedException('system-message', 'error.invalid-character-id');
        }

        return match ($parameterMap['action'] ?? 'inspect') {
            'inspect' => $this->outhouseService->inspect($characterId),
            'use' => $this->outhouseService->useToilet($characterId, $this->readToiletType()),
            'wash' => $this->outhouseService->washHands($characterId, $this->readToiletType()),
            'skip-wash' => $this->outhouseService->skipWashing($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    private function readToiletType(): string
    {
        $toiletType = $_POST['toilet_type'] ?? OuthouseService::TOILET_PUBLIC;

        return \is_string($toiletType) ? $toiletType : OuthouseService::TOILET_PUBLIC;
    }
}
