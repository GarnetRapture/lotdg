<?php

declare(strict_types=1);

namespace Lotdg\Persistence\Repository;

use PDO;

final class AccountRepository
{
    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array{account_id: int, login_name: string, password_hash: string,
     *               email_address: string, email_validation_key: string,
     *               email_validated: int, is_locked: int, is_logged_in: int}|null
     */
    public function findByLoginName(string $loginName): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT account_id, login_name, password_hash, email_address,
                    email_validation_key, email_validated, is_locked, is_logged_in
               FROM account
              WHERE login_name = :login_name',
        );
        $statement->execute(['login_name' => $loginName]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array{account_id: int, superuser_level: int, superuser_flag_bitmap: int,
     *               ban_override: int, beta_enabled: int}|null
     */
    public function findPrivilege(int $accountId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT account_id, superuser_level, superuser_flag_bitmap, ban_override, beta_enabled
               FROM account_privilege
              WHERE account_id = :account_id',
        );
        $statement->execute(['account_id' => $accountId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array{account_id: int, locale_code: string, template_name: string,
     *               preference_json: string}|null
     */
    public function findPreference(int $accountId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT account_id, locale_code, template_name, preference_json
               FROM account_preference
              WHERE account_id = :account_id',
        );
        $statement->execute(['account_id' => $accountId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function existsLoginName(string $loginName): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM account WHERE login_name = :login_name',
        );
        $statement->execute(['login_name' => $loginName]);

        return $statement->fetchColumn() !== false;
    }

    public function existsEmailAddress(string $emailAddress): bool
    {
        $statement = $this->connection->prepare(
            "SELECT 1 FROM account WHERE email_address = :email_address AND email_address <> ''",
        );
        $statement->execute(['email_address' => $emailAddress]);

        return $statement->fetchColumn() !== false;
    }

    public function insertAccount(
        string $loginName,
        string $passwordHash,
        string $emailAddress,
        string $emailValidationKey,
        string $localeCode,
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO account
                 (login_name, password_hash, email_address, email_validation_key, email_validated)
             VALUES
                 (:login_name, :password_hash, :email_address, :email_validation_key, :email_validated)',
        );
        $statement->execute([
            'login_name' => $loginName,
            'password_hash' => $passwordHash,
            'email_address' => $emailAddress,
            'email_validation_key' => $emailValidationKey,
            'email_validated' => $emailValidationKey === '' ? 1 : 0,
        ]);

        $accountId = (int) $this->connection->lastInsertId();

        $this->connection
            ->prepare('INSERT INTO account_privilege (account_id) VALUES (:account_id)')
            ->execute(['account_id' => $accountId]);

        $this->connection
            ->prepare(
                'INSERT INTO account_preference (account_id, locale_code)
                 VALUES (:account_id, :locale_code)',
            )
            ->execute(['account_id' => $accountId, 'locale_code' => $localeCode]);

        $this->connection
            ->prepare('INSERT INTO account_device_fingerprint (account_id) VALUES (:account_id)')
            ->execute(['account_id' => $accountId]);

        $this->connection
            ->prepare('INSERT INTO account_donation (account_id) VALUES (:account_id)')
            ->execute(['account_id' => $accountId]);

        $this->connection
            ->prepare('INSERT INTO account_referral (account_id) VALUES (:account_id)')
            ->execute(['account_id' => $accountId]);

        return $accountId;
    }

    public function updatePasswordHash(int $accountId, string $passwordHash): void
    {
        $this->connection
            ->prepare('UPDATE account SET password_hash = :password_hash WHERE account_id = :account_id')
            ->execute(['password_hash' => $passwordHash, 'account_id' => $accountId]);
    }

    public function markLoginState(int $accountId, bool $isLoggedIn): void
    {
        $this->connection
            ->prepare(
                'UPDATE account
                    SET is_logged_in = :is_logged_in,
                        last_seen_at = datetime(\'now\'),
                        last_hit_at  = datetime(\'now\')
                  WHERE account_id = :account_id',
            )
            ->execute(['is_logged_in' => $isLoggedIn ? 1 : 0, 'account_id' => $accountId]);
    }

    public function updateDeviceFingerprint(
        int $accountId,
        string $ipAddress,
        string $uniqueCookieId,
    ): void {
        $this->connection
            ->prepare(
                'UPDATE account_device_fingerprint
                    SET last_ip_address  = :last_ip_address,
                        unique_cookie_id = :unique_cookie_id,
                        updated_at       = datetime(\'now\')
                  WHERE account_id = :account_id',
            )
            ->execute([
                'last_ip_address' => $ipAddress,
                'unique_cookie_id' => $uniqueCookieId,
                'account_id' => $accountId,
            ]);
    }
}
