<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\WarriorListService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use PDO;

final class WarriorListController implements ControllerInterface
{
    private readonly WarriorListService $warriorListService;

    public function __construct(PDO $connection)
    {
        $this->warriorListService = new WarriorListService(
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
        unset($parameterMap);

        $searchTerm = \is_string($_GET['search_term'] ?? null) ? \trim($_GET['search_term']) : '';

        if ($searchTerm !== '') {
            return $this->warriorListService->search($searchTerm);
        }

        $page = (int) ($_GET['page'] ?? 0);

        return $page > 0
            ? $this->warriorListService->listPage($page)
            : $this->warriorListService->listOnline();
    }
}
