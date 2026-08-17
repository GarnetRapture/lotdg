<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\TrainingService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\CreatureRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class TrainingController implements ControllerInterface
{
    private readonly TrainingService $trainingService;

    public function __construct(PDO $connection)
    {
        $this->trainingService = new TrainingService($connection, new CreatureRepository($connection));
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
            'inspect' => $this->trainingService->inspect($characterId),
            'challenge' => $this->trainingService->challenge($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
