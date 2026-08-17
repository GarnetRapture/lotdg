<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

final class AccountRegistrationResult
{
    /**
     * @param list<string> $errorKeyList
     */
    private function __construct(
        public readonly bool $isSuccessful,
        public readonly ?int $accountId,
        public readonly ?int $characterId,
        public readonly ?string $loginName,
        public readonly ?string $emailValidationKey,
        public readonly array $errorKeyList,
    ) {
    }

    public static function success(
        int $accountId,
        int $characterId,
        string $loginName,
        string $emailValidationKey,
    ): self {
        return new self(true, $accountId, $characterId, $loginName, $emailValidationKey, []);
    }

    /**
     * @param list<string> $errorKeyList
     */
    public static function failure(array $errorKeyList): self
    {
        return new self(false, null, null, null, null, $errorKeyList);
    }
}
