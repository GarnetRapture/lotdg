<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\CommentaryService;
use Lotdg\Domain\World\GameClock;
use Lotdg\Domain\World\ShadeRealmService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class ShadeRealmController implements ControllerInterface
{
    private readonly ShadeRealmService $shadeRealmService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->shadeRealmService = new ShadeRealmService(
            $connection,
            new CommentaryService(
                $connection,
                $gameSettingRepository,
                new BadWordFilter($connection, $gameSettingRepository),
            ),
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
        $characterId = (int) ($parameterMap['character_id'] ?? 0);

        if ($characterId <= 0) {
            throw new LocalizedException('system-message', 'error.invalid-character-id');
        }

        return match ($parameterMap['action'] ?? 'enter') {
            'enter' => $this->shadeRealmService->enter($characterId),
            'post' => $this->shadeRealmService->post(
                $characterId,
                \is_string($_POST['comment_text'] ?? null) ? $_POST['comment_text'] : '',
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
