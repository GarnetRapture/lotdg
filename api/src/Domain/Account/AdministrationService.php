<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LegacyLikePatternBuilder;
use Lotdg\Support\LocalizedException;
use PDO;

final class AdministrationService
{
    public const int LEVEL_STANDARD = 0;

    public const int LEVEL_UNLIMITED_DAY = 1;

    public const int LEVEL_CONTENT_ADMIN = 2;

    public const int LEVEL_USER_ADMIN = 3;

    private const string BIOGRAPHY_BLOCK_THRESHOLD = '9000-01-01 00:00:00';

    private const string BIOGRAPHY_BLOCK_TIMESTAMP = '9999-12-31 23:59:59';

    private const string BIOGRAPHY_BLOCKED_TEXT = '`iBlocked for inappropriate usage`i';

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly LegacyLikePatternBuilder $likePatternBuilder = new LegacyLikePatternBuilder(),
    ) {
    }

    public function requireLevel(int $actorAccountId, int $requiredLevel): void
    {
        $statement = $this->connection->prepare(
            'SELECT superuser_level FROM account_privilege WHERE account_id = :account_id',
        );
        $statement->execute(['account_id' => $actorAccountId]);

        $level = $statement->fetchColumn();

        if ($level === false || (int) $level < $requiredLevel) {
            throw new LocalizedException('system-message', 'error.insufficient-privilege');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function listSetting(int $actorAccountId): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        return ['setting_map' => $this->gameSettingRepository->loadAll()];
    }

    /**
     * @return array<string, mixed>
     */
    public function saveSetting(int $actorAccountId, string $settingKey, string $settingValue): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        if (\trim($settingKey) === '') {
            return ['saved' => false, 'message_key' => 'administration.error.empty-setting-key'];
        }

        $this->gameSettingRepository->put($settingKey, $settingValue);

        return ['saved' => true, 'setting_key' => $settingKey, 'setting_value' => $settingValue];
    }

    /**
     * @return array<string, mixed>
     */
    public function listAccount(int $actorAccountId, string $searchTerm = '', int $limit = 100): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        $statement = $this->connection->prepare(
            'SELECT account.account_id,
                    account.login_name,
                    account.is_locked,
                    account.is_logged_in,
                    account.last_seen_at,
                    account_privilege.superuser_level,
                    game_character.character_id,
                    game_character.display_name,
                    game_character.level,
                    character_progression.dragon_kill_count
               FROM account
               JOIN account_privilege     ON account_privilege.account_id = account.account_id
               LEFT JOIN game_character   ON game_character.account_id = account.account_id
               LEFT JOIN character_progression ON character_progression.character_id = game_character.character_id
              WHERE (:search_term = \'\' OR account.login_name LIKE :like_term)
              ORDER BY account.account_id ASC
              LIMIT :limit',
        );
        $statement->bindValue('search_term', $searchTerm);
        $statement->bindValue('like_term', '%' . $searchTerm . '%');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return [
            'account_list' => \array_map(
                static fn (array $row): array => [
                    'account_id' => (int) $row['account_id'],
                    'login_name' => (string) $row['login_name'],
                    'is_locked' => (int) $row['is_locked'] === 1,
                    'is_logged_in' => (int) $row['is_logged_in'] === 1,
                    'superuser_level' => (int) $row['superuser_level'],
                    'character_id' => $row['character_id'] === null ? null : (int) $row['character_id'],
                    'display_name' => (string) ($row['display_name'] ?? ''),
                    'level' => $row['level'] === null ? null : (int) $row['level'],
                    'dragon_kill_count' => $row['dragon_kill_count'] === null
                        ? null
                        : (int) $row['dragon_kill_count'],
                    'last_seen_at' => $row['last_seen_at'] === null ? null : (string) $row['last_seen_at'],
                ],
                $statement->fetchAll(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function setAccountLock(int $actorAccountId, int $targetAccountId, bool $isLocked): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        $statement = $this->connection->prepare(
            'UPDATE account SET is_locked = :is_locked WHERE account_id = :account_id',
        );
        $statement->execute([
            'is_locked' => $isLocked ? 1 : 0,
            'account_id' => $targetAccountId,
        ]);

        $this->writeDebugLog($actorAccountId, $targetAccountId, $isLocked ? '계정 잠금' : '계정 잠금 해제');

        return ['updated' => $statement->rowCount() > 0, 'is_locked' => $isLocked];
    }

    /**
     * @return array<string, mixed>
     */
    public function setSuperuserLevel(int $actorAccountId, int $targetAccountId, int $level): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        if ($level < self::LEVEL_STANDARD || $level > self::LEVEL_USER_ADMIN) {
            return ['updated' => false, 'message_key' => 'administration.error.invalid-level'];
        }

        $statement = $this->connection->prepare(
            'UPDATE account_privilege SET superuser_level = :level WHERE account_id = :account_id',
        );
        $statement->execute(['level' => $level, 'account_id' => $targetAccountId]);

        $this->writeDebugLog(
            $actorAccountId,
            $targetAccountId,
            \sprintf('superuser_level 을 %d 로 변경', $level),
        );

        return ['updated' => $statement->rowCount() > 0, 'superuser_level' => $level];
    }

    /**
     * @return array<string, mixed>
     */
    public function createBan(
        int $actorAccountId,
        string $ipPrefix,
        string $uniqueCookieId,
        ?string $banExpireDate,
        string $banReason,
    ): array {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        if ($ipPrefix === '' && $uniqueCookieId === '') {
            return ['created' => false, 'message_key' => 'administration.error.ban-target-missing'];
        }

        $this->connection
            ->prepare(
                'INSERT INTO access_ban (ip_prefix, unique_cookie_id, ban_expire_date, ban_reason)
                 VALUES (:ip_prefix, :unique_cookie_id, :ban_expire_date, :ban_reason)',
            )
            ->execute([
                'ip_prefix' => $ipPrefix,
                'unique_cookie_id' => $uniqueCookieId,
                'ban_expire_date' => $banExpireDate,
                'ban_reason' => $banReason,
            ]);

        return ['created' => true, 'access_ban_id' => (int) $this->connection->lastInsertId()];
    }

    /**
     * @return array<string, mixed>
     */
    public function listBan(int $actorAccountId): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_CONTENT_ADMIN);

        $statement = $this->connection->query(
            'SELECT access_ban_id, ip_prefix, unique_cookie_id, ban_expire_date, ban_reason, created_at
               FROM access_ban
              ORDER BY access_ban_id DESC',
        );

        return ['ban_list' => $statement === false ? [] : $statement->fetchAll()];
    }

    /**
     * @return array<string, mixed>
     */
    public function removeBan(int $actorAccountId, int $accessBanId): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        $statement = $this->connection->prepare(
            'DELETE FROM access_ban WHERE access_ban_id = :access_ban_id',
        );
        $statement->execute(['access_ban_id' => $accessBanId]);

        return ['removed' => $statement->rowCount() > 0];
    }

    /**
     * @return array<string, mixed>
     */
    public function listDonator(int $actorAccountId): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        $statement = $this->connection->query(
            'SELECT game_character.display_name,
                    account_donation.account_id,
                    account_donation.donation_point,
                    account_donation.donation_point_spent
               FROM account_donation
               LEFT JOIN game_character ON game_character.account_id = account_donation.account_id
              WHERE account_donation.donation_point > 0
              ORDER BY account_donation.donation_point DESC',
        );

        return [
            'donator_list' => \array_map(
                static fn (array $row): array => [
                    'account_id' => (int) $row['account_id'],
                    'display_name' => (string) ($row['display_name'] ?? ''),
                    'donation_point' => (int) $row['donation_point'],
                    'donation_point_spent' => (int) $row['donation_point_spent'],
                ],
                $statement === false ? [] : $statement->fetchAll(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchDonationTarget(int $actorAccountId, string $searchTerm): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        if (\trim($searchTerm) === '') {
            return ['search_term' => $searchTerm, 'candidate_list' => []];
        }

        $statement = $this->connection->prepare(
            'SELECT account.account_id,
                    account.login_name,
                    game_character.display_name,
                    account_donation.donation_point,
                    account_donation.donation_point_spent
               FROM account
               JOIN account_donation    ON account_donation.account_id = account.account_id
               LEFT JOIN game_character ON game_character.account_id = account.account_id
              WHERE account.login_name LIKE :pattern
              ORDER BY account.login_name ASC',
        );
        $statement->execute(['pattern' => $this->likePatternBuilder->build($searchTerm)]);

        return [
            'search_term' => $searchTerm,
            'candidate_list' => \array_map(
                static fn (array $row): array => [
                    'account_id' => (int) $row['account_id'],
                    'login_name' => (string) $row['login_name'],
                    'display_name' => (string) ($row['display_name'] ?? ''),
                    'donation_point' => (int) $row['donation_point'],
                    'donation_point_spent' => (int) $row['donation_point_spent'],
                ],
                $statement->fetchAll(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function addDonationPoint(int $actorAccountId, int $targetAccountId, int $amount): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_USER_ADMIN);

        if ($amount === 0) {
            return ['added' => false, 'message_key' => 'donation.error.zero-amount'];
        }

        $statement = $this->connection->prepare(
            'UPDATE account_donation
                SET donation_point = MAX(0, donation_point + :amount)
              WHERE account_id = :account_id',
        );
        $statement->execute(['amount' => $amount, 'account_id' => $targetAccountId]);

        if ($statement->rowCount() === 0) {
            return ['added' => false, 'message_key' => 'donation.error.target-not-found'];
        }

        $this->writeDebugLog(
            $actorAccountId,
            $targetAccountId,
            \sprintf('donation_point %+d', $amount),
        );

        return ['added' => true, 'amount' => $amount];
    }

    /**
     * @return array<string, mixed>
     */
    public function listBiography(int $actorAccountId, int $limit = 100): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_CONTENT_ADMIN);

        return [
            'biography_list' => $this->fetchBiographyRowList(
                'character_social.biography_updated_at IS NULL
                 OR character_social.biography_updated_at < :threshold',
                $limit,
            ),
            'blocked_list' => $this->fetchBiographyRowList(
                'character_social.biography_updated_at IS NOT NULL
                 AND character_social.biography_updated_at > :threshold',
                $limit,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function setBiographyBlock(int $actorAccountId, int $characterId, bool $isBlocked): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_CONTENT_ADMIN);

        $statement = $this->connection->prepare(
            'SELECT account_id FROM game_character WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $targetAccountId = $statement->fetchColumn();

        if ($targetAccountId === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        $this->connection
            ->prepare(
                'UPDATE character_social
                    SET biography            = :biography,
                        biography_updated_at = :biography_updated_at
                  WHERE character_id = :character_id',
            )
            ->execute([
                'biography' => $isBlocked ? self::BIOGRAPHY_BLOCKED_TEXT : '',
                'biography_updated_at' => $isBlocked ? self::BIOGRAPHY_BLOCK_TIMESTAMP : null,
                'character_id' => $characterId,
            ]);

        $this->writeDebugLog(
            $actorAccountId,
            (int) $targetAccountId,
            $isBlocked ? 'biography blocked' : 'biography unblocked',
        );

        return [
            'updated' => true,
            'is_blocked' => $isBlocked,
            'target_account_id' => (int) $targetAccountId,
            'notice_label_path' => $isBlocked
                ? 'biography.notice.blocked'
                : 'biography.notice.unblocked',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchBiographyRowList(string $condition, int $limit): array
    {
        $statement = $this->connection->prepare(
            \sprintf(
                'SELECT game_character.character_id,
                        game_character.display_name,
                        character_social.biography,
                        character_social.biography_updated_at
                   FROM game_character
                   JOIN account          ON account.account_id = game_character.account_id
                   JOIN character_social ON character_social.character_id = game_character.character_id
                  WHERE account.is_locked = 0
                    AND character_social.biography <> \'\'
                    AND (%s)
                  ORDER BY character_social.biography_updated_at DESC
                  LIMIT :limit',
                $condition,
            ),
        );
        $statement->bindValue('threshold', self::BIOGRAPHY_BLOCK_THRESHOLD);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return \array_map(
            static fn (array $row): array => [
                'character_id' => (int) $row['character_id'],
                'display_name' => (string) $row['display_name'],
                'biography' => (string) $row['biography'],
                'biography_updated_at' => $row['biography_updated_at'] === null
                    ? null
                    : (string) $row['biography_updated_at'],
            ],
            $statement->fetchAll(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(int $actorAccountId): array
    {
        $this->requireLevel($actorAccountId, self::LEVEL_CONTENT_ADMIN);

        return [
            'account_count' => $this->countTable('account'),
            'character_count' => $this->countTable('game_character'),
            'creature_count' => $this->countTable('creature'),
            'weapon_count' => $this->countTable('weapon'),
            'armor_count' => $this->countTable('armor'),
            'mail_count' => $this->countTable('mail_message'),
            'news_count' => $this->countTable('daily_news'),
            'petition_count' => $this->countTable('petition'),
            'ban_count' => $this->countTable('access_ban'),
        ];
    }

    private function countTable(string $tableName): int
    {
        $statement = $this->connection->query(\sprintf('SELECT COUNT(*) FROM %s', $tableName));

        return $statement === false ? 0 : (int) $statement->fetchColumn();
    }

    private function writeDebugLog(int $actorAccountId, int $targetAccountId, string $message): void
    {
        $this->connection
            ->prepare(
                'INSERT INTO debug_log (actor_account_id, target_account_id, message)
                 VALUES (:actor_account_id, :target_account_id, :message)',
            )
            ->execute([
                'actor_account_id' => $actorAccountId,
                'target_account_id' => $targetAccountId,
                'message' => $message,
            ]);
    }
}
