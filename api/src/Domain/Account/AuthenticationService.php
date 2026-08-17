<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

use Lotdg\Persistence\Repository\AccountRepository;
use Lotdg\Support\PasswordHasher;

final class AuthenticationService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly PasswordHasher $passwordHasher = new PasswordHasher(),
    ) {
    }

    public function authenticate(string $loginName, string $plainPassword): AuthenticationResult
    {
        $account = $this->accountRepository->findByLoginName($loginName);

        if ($account === null) {
            return AuthenticationResult::failure(
                AuthenticationFailureReason::CREDENTIAL_MISMATCH,
            );
        }

        if (!$this->passwordHasher->verify($plainPassword, $account['password_hash'])) {
            return AuthenticationResult::failure(
                AuthenticationFailureReason::CREDENTIAL_MISMATCH,
            );
        }

        if ($account['is_locked'] === 1) {
            return AuthenticationResult::failure(
                AuthenticationFailureReason::ACCOUNT_LOCKED,
            );
        }

        if ($account['email_validated'] === 0 && $account['email_validation_key'] !== '') {
            return AuthenticationResult::failure(
                AuthenticationFailureReason::EMAIL_NOT_VALIDATED,
            );
        }

        if ($this->passwordHasher->needsRehash($account['password_hash'])) {
            $this->accountRepository->updatePasswordHash(
                $account['account_id'],
                $this->passwordHasher->hash($plainPassword),
            );
        }

        $this->accountRepository->markLoginState($account['account_id'], true);

        return AuthenticationResult::success($account['account_id']);
    }

    public function logout(int $accountId): void
    {
        $this->accountRepository->markLoginState($accountId, false);
    }
}
