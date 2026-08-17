<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\AdministrationService;
use Lotdg\Domain\Account\TitleRebuildService;
use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\MailService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class AdministrationController implements ControllerInterface
{
    private readonly AdministrationService $administrationService;

    private readonly MailService $mailService;

    private readonly BadWordFilter $badWordFilter;

    private readonly TitleRebuildService $titleRebuildService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->badWordFilter = new BadWordFilter($connection, $gameSettingRepository);
        $this->administrationService = new AdministrationService($connection, $gameSettingRepository);
        $this->mailService = new MailService(
            $connection,
            $gameSettingRepository,
            $this->badWordFilter,
        );
        $this->titleRebuildService = new TitleRebuildService($connection);
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

        $actorAccountId = $this->mailService->requireAccountId($characterId);

        return match ($parameterMap['action'] ?? 'summary') {
            'summary' => $this->administrationService->summarize($actorAccountId),
            'setting-list' => $this->administrationService->listSetting($actorAccountId),
            'setting-save' => $this->administrationService->saveSetting(
                $actorAccountId,
                $this->readStringField('setting_key'),
                $this->readStringField('setting_value'),
            ),
            'account-list' => $this->administrationService->listAccount(
                $actorAccountId,
                \is_string($_GET['search_term'] ?? null) ? \trim($_GET['search_term']) : '',
            ),
            'account-lock' => $this->administrationService->setAccountLock(
                $actorAccountId,
                (int) ($_POST['target_account_id'] ?? 0),
                ($_POST['is_locked'] ?? '0') === '1',
            ),
            'account-level' => $this->administrationService->setSuperuserLevel(
                $actorAccountId,
                (int) ($_POST['target_account_id'] ?? 0),
                (int) ($_POST['superuser_level'] ?? 0),
            ),
            'ban-list' => $this->administrationService->listBan($actorAccountId),
            'ban-create' => $this->administrationService->createBan(
                $actorAccountId,
                $this->readStringField('ip_prefix'),
                $this->readStringField('unique_cookie_id'),
                $this->readStringField('ban_expire_date') === ''
                    ? null
                    : $this->readStringField('ban_expire_date'),
                $this->readStringField('ban_reason'),
            ),
            'ban-remove' => $this->administrationService->removeBan(
                $actorAccountId,
                (int) ($_POST['access_ban_id'] ?? 0),
            ),
            'donator-list' => $this->administrationService->listDonator($actorAccountId),
            'donator-search' => $this->administrationService->searchDonationTarget(
                $actorAccountId,
                \is_string($_GET['search_term'] ?? null) ? \trim($_GET['search_term']) : '',
            ),
            'donator-add' => $this->administrationService->addDonationPoint(
                $actorAccountId,
                (int) ($_POST['target_account_id'] ?? 0),
                (int) ($_POST['amount'] ?? 0),
            ),
            'badword-list' => $this->listBadWord($actorAccountId),
            'badword-add' => $this->mutateBadWord($actorAccountId, 'add'),
            'badword-remove' => $this->mutateBadWord($actorAccountId, 'remove'),
            'badword-test' => $this->mutateBadWord($actorAccountId, 'test'),
            'title-rebuild' => $this->rebuildTitle($actorAccountId),
            'biography-list' => $this->administrationService->listBiography($actorAccountId),
            'biography-block' => $this->setBiographyBlock($actorAccountId, true),
            'biography-unblock' => $this->setBiographyBlock($actorAccountId, false),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function listBadWord(int $actorAccountId): array
    {
        $this->administrationService->requireLevel(
            $actorAccountId,
            AdministrationService::LEVEL_CONTENT_ADMIN,
        );

        return $this->badWordFilter->listWord();
    }

    /**
     * @return array<string, mixed>
     */
    private function mutateBadWord(int $actorAccountId, string $operation): array
    {
        $this->administrationService->requireLevel(
            $actorAccountId,
            AdministrationService::LEVEL_CONTENT_ADMIN,
        );

        $word = $this->readStringField('word');

        return match ($operation) {
            'add' => $this->badWordFilter->addWord($word),
            'remove' => $this->badWordFilter->removeWord($word),
            default => $this->badWordFilter->testWord($word),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function rebuildTitle(int $actorAccountId): array
    {
        $this->administrationService->requireLevel(
            $actorAccountId,
            AdministrationService::LEVEL_USER_ADMIN,
        );

        return $this->titleRebuildService->rebuild();
    }

    /**
     * @return array<string, mixed>
     */
    private function setBiographyBlock(int $actorAccountId, bool $isBlocked): array
    {
        $result = $this->administrationService->setBiographyBlock(
            $actorAccountId,
            (int) ($_POST['target_character_id'] ?? 0),
            $isBlocked,
        );

        $this->mailService->sendSystemMessage(
            (int) $result['target_account_id'],
            (string) $result['notice_label_path'],
            (string) $result['notice_label_path'],
        );

        return $result;
    }

    private function readStringField(string $fieldName): string
    {
        $value = $_POST[$fieldName] ?? '';

        return \is_string($value) ? \trim($value) : '';
    }
}
