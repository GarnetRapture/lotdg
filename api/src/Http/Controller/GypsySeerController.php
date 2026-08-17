<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\CommentaryService;
use Lotdg\Domain\Social\GypsySeerService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class GypsySeerController implements ControllerInterface
{
    private readonly GypsySeerService $gypsySeerService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->gypsySeerService = new GypsySeerService(
            $connection,
            new CommentaryService(
                $connection,
                $gameSettingRepository,
                new BadWordFilter($connection, $gameSettingRepository),
            ),
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
            'inspect' => $this->gypsySeerService->inspect($characterId),
            'listen' => $this->gypsySeerService->payAndListen($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
