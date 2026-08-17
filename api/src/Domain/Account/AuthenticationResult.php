<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

final class AuthenticationResult
{
    private function __construct(
        public readonly bool $isSuccessful,
        public readonly ?int $accountId,
        public readonly ?AuthenticationFailureReason $failureReason,
    ) {
    }

    public static function success(int $accountId): self
    {
        return new self(true, $accountId, null);
    }

    public static function failure(AuthenticationFailureReason $failureReason): self
    {
        return new self(false, null, $failureReason);
    }
}
