<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\DragonService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class DragonController implements ControllerInterface
{
    private readonly DragonService $dragonService;

    public function __construct(PDO $connection)
    {
        $this->dragonService = new DragonService($connection, new GameSettingRepository($connection));
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

        return match ($parameterMap['action'] ?? 'enter') {
            'enter' => $this->dragonService->enterLair($characterId),
            'fight' => $this->dragonService->fightRound($characterId),
            'rebirth' => $this->dragonService->completeRebirth(
                $characterId,
                ($_POST['flawless'] ?? '0') === '1',
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
