<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

use Lotdg\Support\LocalizedException;
use PDO;

final class ReferralService
{
    public const int AWARD_LEVEL = 4;

    public const int AWARD_POINT = 25;

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $accountId): array
    {
        $statement = $this->connection->prepare(
            'SELECT account.login_name,
                    game_character.display_name,
                    game_character.level,
                    character_progression.dragon_kill_count,
                    account_referral.referral_awarded
               FROM account_referral
               JOIN account                    ON account.account_id = account_referral.account_id
               LEFT JOIN game_character        ON game_character.account_id = account.account_id
               LEFT JOIN character_progression ON character_progression.character_id = game_character.character_id
              WHERE account_referral.referrer_account_id = :account_id
              ORDER BY character_progression.dragon_kill_count ASC, game_character.level ASC',
        );
        $statement->execute(['account_id' => $accountId]);

        $referralList = [];
        $awardedCount = 0;

        foreach ($statement->fetchAll() as $row) {
            $isAwarded = (int) $row['referral_awarded'] === 1;

            if ($isAwarded) {
                ++$awardedCount;
            }

            $referralList[] = [
                'login_name' => (string) $row['login_name'],
                'display_name' => (string) ($row['display_name'] ?? ''),
                'level' => (int) ($row['level'] ?? 0),
                'is_awarded' => $isAwarded,
            ];
        }

        return [
            'referral_link_key' => $this->resolveLoginName($accountId),
            'award_level' => self::AWARD_LEVEL,
            'award_point' => self::AWARD_POINT,
            'awarded_count' => $awardedCount,
            'earned_point' => $awardedCount * self::AWARD_POINT,
            'referral_list' => $referralList,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function registerReferrer(int $accountId, string $referrerLoginName): array
    {
        $referrerAccountId = $this->resolveAccountId($referrerLoginName);

        if ($referrerAccountId === null || $referrerAccountId === $accountId) {
            return ['registered' => false, 'message_key' => 'referral.error.referrer-not-found'];
        }

        $statement = $this->connection->prepare(
            'INSERT INTO account_referral (account_id, referrer_account_id, referral_awarded)
             VALUES (:account_id, :referrer_account_id, 0)
             ON CONFLICT(account_id) DO NOTHING',
        );
        $statement->execute([
            'account_id' => $accountId,
            'referrer_account_id' => $referrerAccountId,
        ]);

        return [
            'registered' => $statement->rowCount() > 0,
            'referrer_account_id' => $referrerAccountId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function awardIfEligible(int $accountId, int $level): array
    {
        if ($level < self::AWARD_LEVEL) {
            return ['awarded' => false];
        }

        $statement = $this->connection->prepare(
            'SELECT referrer_account_id
               FROM account_referral
              WHERE account_id = :account_id
                AND referral_awarded = 0
                AND referrer_account_id IS NOT NULL',
        );
        $statement->execute(['account_id' => $accountId]);

        $referrerAccountId = $statement->fetchColumn();

        if ($referrerAccountId === false) {
            return ['awarded' => false];
        }

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE account_referral SET referral_awarded = 1 WHERE account_id = :account_id',
                )
                ->execute(['account_id' => $accountId]);

            $this->connection
                ->prepare(
                    'UPDATE account_donation
                        SET donation_point = donation_point + :award_point
                      WHERE account_id = :account_id',
                )
                ->execute([
                    'award_point' => self::AWARD_POINT,
                    'account_id' => (int) $referrerAccountId,
                ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'awarded' => true,
            'referrer_account_id' => (int) $referrerAccountId,
            'award_point' => self::AWARD_POINT,
        ];
    }

    private function resolveAccountId(string $loginName): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT account_id FROM account WHERE login_name = :login_name AND is_locked = 0',
        );
        $statement->execute(['login_name' => $loginName]);

        $accountId = $statement->fetchColumn();

        return $accountId === false ? null : (int) $accountId;
    }

    private function resolveLoginName(int $accountId): string
    {
        $statement = $this->connection->prepare(
            'SELECT login_name FROM account WHERE account_id = :account_id',
        );
        $statement->execute(['account_id' => $accountId]);

        $loginName = $statement->fetchColumn();

        if ($loginName === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return (string) $loginName;
    }
}
