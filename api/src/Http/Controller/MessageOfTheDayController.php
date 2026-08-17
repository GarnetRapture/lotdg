<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\AdministrationService;
use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\MailService;
use Lotdg\Domain\Social\MessageOfTheDayService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class MessageOfTheDayController implements ControllerInterface
{
    private readonly MessageOfTheDayService $messageOfTheDayService;

    private readonly MailService $mailService;

    private readonly AdministrationService $administrationService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->messageOfTheDayService = new MessageOfTheDayService($connection);
        $this->mailService = new MailService(
            $connection,
            $gameSettingRepository,
            new BadWordFilter($connection, $gameSettingRepository),
        );
        $this->administrationService = new AdministrationService($connection, $gameSettingRepository);
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

        return match ($parameterMap['action'] ?? 'list') {
            'list' => $this->messageOfTheDayService->listAll($accountId),
            'seen' => $this->markSeen($accountId),
            'vote' => $this->messageOfTheDayService->vote(
                (int) ($_POST['motd_id'] ?? 0),
                $accountId,
                (int) ($_POST['choice_index'] ?? 0),
            ),
            'publish' => $this->publish($accountId),
            'publish-poll' => $this->publishPoll($accountId),
            'remove' => $this->remove($accountId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function markSeen(int $accountId): array
    {
        $this->messageOfTheDayService->markSeen($accountId);

        return ['marked_seen' => true];
    }

    /**
     * @return array<string, mixed>
     */
    private function publish(int $accountId): array
    {
        $this->administrationService->requireLevel($accountId, AdministrationService::LEVEL_USER_ADMIN);

        $title = $this->readStringField('title');
        $body = $this->readStringField('body');

        if ($title === '' || $body === '') {
            return ['published' => false, 'message_key' => 'motd.error.empty-field'];
        }

        return [
            'published' => true,
            'motd_id' => $this->messageOfTheDayService->publish($title, $body),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publishPoll(int $accountId): array
    {
        $this->administrationService->requireLevel($accountId, AdministrationService::LEVEL_USER_ADMIN);

        $title = $this->readStringField('title');
        $body = $this->readStringField('body');
        $choiceList = $this->readChoiceList();

        if ($title === '' || $body === '' || $choiceList === []) {
            return ['published' => false, 'message_key' => 'motd.error.empty-field'];
        }

        return [
            'published' => true,
            'motd_id' => $this->messageOfTheDayService->publishPoll($title, $body, $choiceList),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function remove(int $accountId): array
    {
        $this->administrationService->requireLevel($accountId, AdministrationService::LEVEL_USER_ADMIN);

        $motdId = (int) ($_POST['motd_id'] ?? 0);

        return [
            'removed' => $motdId > 0 && $this->messageOfTheDayService->remove($motdId),
        ];
    }

    /**
     * @return list<string>
     */
    private function readChoiceList(): array
    {
        $rawChoiceList = $_POST['choice_list'] ?? [];

        if (!\is_array($rawChoiceList)) {
            return [];
        }

        $choiceList = [];

        foreach ($rawChoiceList as $choiceText) {
            if (\is_string($choiceText) && \trim($choiceText) !== '') {
                $choiceList[] = \trim($choiceText);
            }
        }

        return $choiceList;
    }

    private function readStringField(string $fieldName): string
    {
        $value = $_POST[$fieldName] ?? '';

        return \is_string($value) ? \trim($value) : '';
    }
}
