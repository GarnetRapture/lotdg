<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\BountyService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class BountyController implements ControllerInterface
{
    private readonly BountyService $bountyService;

    public function __construct(PDO $connection)
    {
        $this->bountyService = new BountyService($connection, new GameSettingRepository($connection));
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
            'inspect' => $this->bountyService->inspect($characterId) + $this->bountyService->listBounty(),
            'search' => $this->bountyService->searchTarget($characterId, $this->readSearchTerm()),
            'place' => $this->bountyService->placeBounty(
                $characterId,
                (int) ($_POST['target_character_id'] ?? 0),
                (int) ($_POST['amount'] ?? 0),
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    private function readSearchTerm(): string
    {
        $searchTerm = $_GET['search_term'] ?? $_POST['search_term'] ?? '';

        return \is_string($searchTerm) ? \trim($searchTerm) : '';
    }
}
