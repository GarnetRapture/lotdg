<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\CharacterRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class CharacterController implements ControllerInterface
{
    private readonly CharacterRepository $characterRepository;

    public function __construct(PDO $connection)
    {
        $this->characterRepository = new CharacterRepository($connection);
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

        $bundle = $this->characterRepository->findBundle($characterId);

        if ($bundle === null) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return $bundle;
    }
}
