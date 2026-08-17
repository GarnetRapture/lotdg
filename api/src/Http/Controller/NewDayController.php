<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\NewDayService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class NewDayController implements ControllerInterface
{
    private readonly NewDayService $newDayService;

    public function __construct(PDO $connection)
    {
        $this->newDayService = new NewDayService($connection, new GameSettingRepository($connection));
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

        return $this->newDayService->run($characterId, ($_POST['resurrection'] ?? '0') === '1');
    }
}
