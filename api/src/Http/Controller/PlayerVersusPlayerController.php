<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\PlayerVersusPlayerService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class PlayerVersusPlayerController implements ControllerInterface
{
    private readonly PlayerVersusPlayerService $playerVersusPlayerService;

    public function __construct(PDO $connection)
    {
        $this->playerVersusPlayerService = new PlayerVersusPlayerService(
            $connection,
            new GameSettingRepository($connection),
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

        return match ($parameterMap['action'] ?? 'list') {
            'list' => $this->playerVersusPlayerService->listTargets($characterId),
            'attack' => $this->playerVersusPlayerService->attack(
                $characterId,
                (int) ($_POST['target_character_id'] ?? 0),
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
