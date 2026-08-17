<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\WebVoteService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class WebVoteController implements ControllerInterface
{
    private readonly WebVoteService $webVoteService;

    public function __construct(PDO $connection)
    {
        $this->webVoteService = new WebVoteService(
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

        return match ($parameterMap['action'] ?? 'inspect') {
            'inspect' => $this->webVoteService->inspect($characterId),
            'claim' => $this->webVoteService->claim($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
