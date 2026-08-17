<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use Lotdg\Support\PasswordHasher;
use PDO;

final class PreferenceService
{
    public const int BIOGRAPHY_MAXIMUM_LENGTH = 255;

    private const string BIOGRAPHY_BLOCK_THRESHOLD = '9000-01-01 00:00:00';

    /** @var list<string> */
    private const NOTIFICATION_KEY_LIST = ['emailonmail', 'systemmail', 'dirtyemail'];

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly PasswordHasher $passwordHasher = new PasswordHasher(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId): array
    {
        $row = $this->fetchPreferenceRow($characterId);
        $preference = \json_decode((string) $row['preference_json'], true);

        return [
            'locale_code' => (string) $row['locale_code'],
            'template_name' => (string) $row['template_name'],
            'email_address' => (string) $row['email_address'],
            'email_change_allowed' => !$this->gameSettingRepository->getBool('requirevalidemail', false),
            'biography' => (string) $row['biography'],
            'biography_editable' => $this->isBiographyEditable($row['biography_updated_at']),
            'notification' => \is_array($preference) ? $preference : [],
            'self_delete_allowed' => $this->gameSettingRepository->getBool('selfdelete', false)
                && (int) $row['is_alive'] === 1,
        ];
    }

    /**
     * @param array<string, string> $notificationMap
     *
     * @return array<string, mixed>
     */
    public function save(
        int $characterId,
        string $localeCode,
        string $templateName,
        string $emailAddress,
        string $biography,
        array $notificationMap,
    ): array {
        $row = $this->fetchPreferenceRow($characterId);
        $noticeKeyList = [];

        $this->connection->beginTransaction();

        try {
            $this->saveLocaleAndTemplate($row, $localeCode, $templateName);
            $noticeKeyList = \array_merge(
                $noticeKeyList,
                $this->saveEmailAddress($row, $emailAddress),
                $this->saveBiography($characterId, $row, $biography),
            );
            $this->saveNotification($row, $notificationMap);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return ['saved' => true, 'notice_key_list' => $noticeKeyList];
    }

    /**
     * @return array<string, mixed>
     */
    public function changePassword(
        int $characterId,
        string $newPassword,
        string $newPasswordConfirmation,
    ): array {
        if ($newPassword !== $newPasswordConfirmation) {
            return ['changed' => false, 'message_key' => 'preference.error.password-mismatch'];
        }

        if (!$this->passwordHasher->isAcceptableLength($newPassword)) {
            return ['changed' => false, 'message_key' => 'preference.error.password-too-short'];
        }

        $row = $this->fetchPreferenceRow($characterId);

        $this->connection
            ->prepare('UPDATE account SET password_hash = :password_hash WHERE account_id = :account_id')
            ->execute([
                'password_hash' => $this->passwordHasher->hash($newPassword),
                'account_id' => (int) $row['account_id'],
            ]);

        return ['changed' => true];
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteCharacter(int $characterId): array
    {
        if (!$this->gameSettingRepository->getBool('selfdelete', false)) {
            return ['deleted' => false, 'message_key' => 'preference.error.self-delete-disabled'];
        }

        $row = $this->fetchPreferenceRow($characterId);

        if ((int) $row['is_alive'] !== 1) {
            return ['deleted' => false, 'message_key' => 'preference.error.self-delete-while-dead'];
        }

        $this->connection
            ->prepare('DELETE FROM account WHERE account_id = :account_id')
            ->execute(['account_id' => (int) $row['account_id']]);

        return ['deleted' => true];
    }

    /**
     * 언어만 바꾸는 경로. 좌측 언어 메뉴가 다른 설정을 건드리지 않고 호출한다.
     *
     * @return array<string, mixed>
     */
    public function saveLocale(int $characterId, string $localeCode): array
    {
        $row = $this->fetchPreferenceRow($characterId);

        $this->saveLocaleAndTemplate($row, $localeCode, '');

        return [
            'saved' => true,
            'locale_code' => (string) $this->connection
                ->query(
                    'SELECT locale_code FROM account_preference WHERE account_id = '
                    . (int) $row['account_id'],
                )
                ?->fetchColumn(),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function saveLocaleAndTemplate(array $row, string $localeCode, string $templateName): void
    {
        $supportedLocaleCodeList = ['en', 'ko', 'ja', 'zh', 'ru'];

        $this->connection
            ->prepare(
                'UPDATE account_preference
                    SET locale_code   = :locale_code,
                        template_name = :template_name
                  WHERE account_id = :account_id',
            )
            ->execute([
                'locale_code' => \in_array($localeCode, $supportedLocaleCodeList, true)
                    ? $localeCode
                    : (string) $row['locale_code'],
                'template_name' => $templateName === '' ? (string) $row['template_name'] : $templateName,
                'account_id' => (int) $row['account_id'],
            ]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private function saveEmailAddress(array $row, string $emailAddress): array
    {
        if ($emailAddress === (string) $row['email_address']) {
            return [];
        }

        if ($this->gameSettingRepository->getBool('requirevalidemail', false)) {
            return ['preference.notice.email-change-prohibited'];
        }

        if (
            $this->gameSettingRepository->getBool('requireemail', false)
            && \filter_var($emailAddress, \FILTER_VALIDATE_EMAIL) === false
        ) {
            return ['preference.notice.email-invalid'];
        }

        $this->connection
            ->prepare('UPDATE account SET email_address = :email_address WHERE account_id = :account_id')
            ->execute([
                'email_address' => $emailAddress,
                'account_id' => (int) $row['account_id'],
            ]);

        return ['preference.notice.email-changed'];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private function saveBiography(int $characterId, array $row, string $biography): array
    {
        if ($biography === (string) $row['biography']) {
            return [];
        }

        if (!$this->isBiographyEditable($row['biography_updated_at'])) {
            return ['preference.notice.biography-blocked'];
        }

        $this->connection
            ->prepare(
                'UPDATE character_social
                    SET biography = :biography,
                        biography_updated_at = datetime(\'now\')
                  WHERE character_id = :character_id',
            )
            ->execute([
                'biography' => \mb_substr($biography, 0, self::BIOGRAPHY_MAXIMUM_LENGTH),
                'character_id' => $characterId,
            ]);

        return ['preference.notice.biography-saved'];
    }

    /**
     * @param array<string, mixed>  $row
     * @param array<string, string> $notificationMap
     */
    private function saveNotification(array $row, array $notificationMap): void
    {
        $preference = \json_decode((string) $row['preference_json'], true);
        $preference = \is_array($preference) ? $preference : [];

        foreach (self::NOTIFICATION_KEY_LIST as $key) {
            if (\array_key_exists($key, $notificationMap)) {
                $preference[$key] = $notificationMap[$key] === '1' ? 1 : 0;
            }
        }

        $encoded = \json_encode($preference, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        $this->connection
            ->prepare(
                'UPDATE account_preference
                    SET preference_json = :preference_json
                  WHERE account_id = :account_id',
            )
            ->execute([
                'preference_json' => $encoded === false ? '{}' : $encoded,
                'account_id' => (int) $row['account_id'],
            ]);
    }

    private function isBiographyEditable(mixed $biographyUpdatedAt): bool
    {
        if (!\is_string($biographyUpdatedAt) || $biographyUpdatedAt === '') {
            return true;
        }

        return $biographyUpdatedAt < self::BIOGRAPHY_BLOCK_THRESHOLD;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchPreferenceRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.account_id,
                    account.email_address,
                    account_preference.locale_code,
                    account_preference.template_name,
                    account_preference.preference_json,
                    character_vital.is_alive,
                    character_social.biography,
                    character_social.biography_updated_at
               FROM game_character
               JOIN account            ON account.account_id = game_character.account_id
               JOIN account_preference ON account_preference.account_id = game_character.account_id
               JOIN character_vital    ON character_vital.character_id = game_character.character_id
               JOIN character_social   ON character_social.character_id = game_character.character_id
              WHERE game_character.character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return $row;
    }
}
