<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\AdministrationService;
use Lotdg\Domain\Catalog\CreatureEditorService;
use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\MailService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class CreatureEditorController implements ControllerInterface
{
    private readonly CreatureEditorService $creatureEditorService;

    private readonly AdministrationService $administrationService;

    private readonly MailService $mailService;

    private readonly PDO $connection;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->connection = $connection;
        $this->creatureEditorService = new CreatureEditorService($connection);
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
            'list' => $this->creatureEditorService->listAll(),
            'save' => $this->creatureEditorService->save(
                (int) ($_POST['creature_id'] ?? 0),
                $this->readStringField('creature_name'),
                $this->readStringField('weapon_name'),
                $this->readStringField('defeat_message'),
                (int) ($_POST['creature_level'] ?? 0),
                (int) ($_POST['location_code'] ?? 0),
                $this->resolveLoginName($accountId),
            ),
            'remove' => $this->creatureEditorService->remove((int) ($_POST['creature_id'] ?? 0)),
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

    private function readStringField(string $fieldName): string
    {
        $value = $_POST[$fieldName] ?? '';

        return \is_string($value) ? \trim($value) : '';
    }
}
