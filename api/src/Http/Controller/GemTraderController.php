<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Catalog\GemTraderService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class GemTraderController implements ControllerInterface
{
    private readonly GemTraderService $gemTraderService;

    public function __construct(PDO $connection)
    {
        $this->gemTraderService = new GemTraderService($connection, new GameSettingRepository($connection));
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
            'inspect' => $this->gemTraderService->inspect($characterId),
            'buy' => $this->gemTraderService->buy($characterId, (int) ($_POST['option_code'] ?? 0)),
            'sell' => $this->gemTraderService->sell($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
