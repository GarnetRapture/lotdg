<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\InnService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class InnController implements ControllerInterface
{
    private readonly InnService $innService;

    public function __construct(PDO $connection)
    {
        $this->innService = new InnService($connection, new GameSettingRepository($connection));
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
            'enter' => $this->innService->enter($characterId),
            'ale' => $this->innService->buyAle(
                $characterId,
                \is_string($_POST['drink_code'] ?? null) ? $_POST['drink_code'] : InnService::DRINK_ALE,
            ),
            'room' => $this->innService->rentRoom($characterId, ($_POST['pay_from_bank'] ?? '0') === '1'),
            'specialty' => $this->innService->changeSpecialty(
                $characterId,
                (int) ($_POST['specialty_code'] ?? 0),
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
