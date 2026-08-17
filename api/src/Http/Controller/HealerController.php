<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\HealerService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Support\LocalizedException;
use PDO;

final class HealerController implements ControllerInterface
{
    private readonly HealerService $healerService;

    public function __construct(PDO $connection)
    {
        $this->healerService = new HealerService($connection);
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
            'inspect' => $this->healerService->inspect($characterId),
            'buy' => $this->healerService->buyPotion(
                $characterId,
                (int) ($_POST['percent'] ?? 100),
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
