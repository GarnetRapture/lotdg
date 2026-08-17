<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\PetitionService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Support\LocalizedException;
use PDO;

final class PetitionController implements ControllerInterface
{
    private readonly PetitionService $petitionService;

    private readonly PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->petitionService = new PetitionService($connection);
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        return match ($parameterMap['action'] ?? 'list') {
            'list' => $this->petitionService->listByStatus(
                isset($_GET['status_code']) ? (int) $_GET['status_code'] : null,
            ),
            'submit' => $this->petitionService->submit(
                $this->resolveAccountId((int) ($_POST['character_id'] ?? 0)),
                \is_string($_POST['body'] ?? null) ? $_POST['body'] : '',
                ['request_uri' => (string) ($_SERVER['REQUEST_URI'] ?? '')],
            ),
            'status' => $this->petitionService->updateStatus(
                (int) ($_POST['petition_id'] ?? 0),
                (int) ($_POST['status_code'] ?? 0),
            ),
            'view' => $this->petitionService->view(
                (int) ($_GET['petition_id'] ?? $_POST['petition_id'] ?? 0),
            ),
            'remove' => $this->petitionService->remove((int) ($_POST['petition_id'] ?? 0)),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    private function resolveAccountId(int $characterId): ?int
    {
        if ($characterId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT account_id FROM game_character WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $accountId = $statement->fetchColumn();

        return $accountId === false ? null : (int) $accountId;
    }
}
