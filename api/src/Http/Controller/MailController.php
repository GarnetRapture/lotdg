<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\MailService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class MailController implements ControllerInterface
{
    private readonly MailService $mailService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

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

        return match ($parameterMap['action'] ?? 'inbox') {
            'inbox' => $this->mailService->listInbox($accountId),
            'read' => $this->mailService->read($accountId, (int) ($_POST['mail_message_id'] ?? 0)),
            'send' => $this->mailService->send(
                $accountId,
                $this->readStringField('recipient_login_name'),
                $this->readStringField('subject'),
                $this->readStringField('body'),
            ),
            'delete' => $this->mailService->delete($accountId, (int) ($_POST['mail_message_id'] ?? 0)),
            'delete-many' => $this->mailService->deleteMany($accountId, $this->readIdentifierList()),
            'reply' => $this->mailService->prepareReply(
                $accountId,
                (int) ($_POST['mail_message_id'] ?? $_GET['mail_message_id'] ?? 0),
            ),
            'search-recipient' => $this->mailService->searchRecipient(
                \is_string($_GET['search_term'] ?? null) ? \trim($_GET['search_term']) : '',
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return list<int>
     */
    private function readIdentifierList(): array
    {
        $rawList = $_POST['mail_message_id_list'] ?? [];

        if (!\is_array($rawList)) {
            return [];
        }

        return \array_values(\array_map(static fn (mixed $value): int => (int) $value, $rawList));
    }

    private function readStringField(string $fieldName): string
    {
        $value = $_POST[$fieldName] ?? '';

        return \is_string($value) ? \trim($value) : '';
    }
}
