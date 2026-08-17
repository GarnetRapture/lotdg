<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\AdministrationService;
use Lotdg\Domain\Catalog\TauntEditorService;
use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\MailService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class TauntEditorController implements ControllerInterface
{
    private readonly TauntEditorService $tauntEditorService;

    private readonly AdministrationService $administrationService;

    private readonly MailService $mailService;

    private readonly PDO $connection;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->connection = $connection;
        $this->tauntEditorService = new TauntEditorService($connection);
        $this->administrationService = new AdministrationService($connection, $gameSettingRepository);
        $this->mailService = new MailService(
            $connection,
            $gameSettingRepository,
            new BadWordFilter($connection, $gameSettingRepository),
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

        $accountId = $this->mailService->requireAccountId($characterId);
        $this->administrationService->requireLevel($accountId, AdministrationService::LEVEL_CONTENT_ADMIN);

        return match ($parameterMap['action'] ?? 'list') {
            'list' => $this->tauntEditorService->listAll(),
            'save' => $this->tauntEditorService->save(
                (int) ($_POST['taunt_id'] ?? 0),
                \is_string($_POST['taunt_text'] ?? null) ? $_POST['taunt_text'] : '',
                $this->resolveLoginName($accountId),
            ),
            'remove' => $this->tauntEditorService->remove((int) ($_POST['taunt_id'] ?? 0)),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    private function resolveLoginName(int $accountId): string
    {
        $statement = $this->connection->prepare(
            'SELECT login_name FROM account WHERE account_id = :account_id',
        );
        $statement->execute(['account_id' => $accountId]);

        $loginName = $statement->fetchColumn();

        return \is_string($loginName) ? $loginName : '';
    }
}
