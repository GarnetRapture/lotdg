<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

use Lotdg\Persistence\Repository\AccountRepository;
use Lotdg\Persistence\Repository\CharacterRepository;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\PasswordHasher;

final class AccountRegistrationService
{
    private const int LOGIN_NAME_MINIMUM_LENGTH = 3;

    private const int LOGIN_NAME_MAXIMUM_LENGTH = 25;

    private const array RANK_TITLE_BY_SEX_CODE = [0 => 'Farmboy', 1 => 'Farmgirl'];

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly CharacterRepository $characterRepository,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly PasswordHasher $passwordHasher = new PasswordHasher(),
    ) {
    }

    public function register(
        string $loginName,
        string $plainPassword,
        string $plainPasswordConfirmation,
        string $emailAddress,
        int $sexCode,
        string $localeCode,
    ): AccountRegistrationResult {
        $normalizedLoginName = $this->normalizeLoginName($loginName);
        $errorKeyList = $this->collectErrorKeyList(
            $normalizedLoginName,
            $plainPassword,
            $plainPasswordConfirmation,
            $emailAddress,
        );

        if ($errorKeyList !== []) {
            return AccountRegistrationResult::failure($errorKeyList);
        }

        $emailValidationKey = $this->gameSettingRepository->getBool('requirevalidemail', false)
            ? \bin2hex(\random_bytes(16))
            : '';

        $accountId = $this->accountRepository->insertAccount(
            $normalizedLoginName,
            $this->passwordHasher->hash($plainPassword),
            $emailAddress,
            $emailValidationKey,
            $localeCode,
        );

        $rankTitle = self::RANK_TITLE_BY_SEX_CODE[$sexCode === 1 ? 1 : 0];

        $characterId = $this->characterRepository->createForAccount(
            $accountId,
            $rankTitle . ' ' . $normalizedLoginName,
            $sexCode === 1 ? 1 : 0,
            0,
            $rankTitle,
            $this->gameSettingRepository->getInt('newplayerstartgold', 50),
            $this->gameSettingRepository->getInt('turns', 10),
        );

        return AccountRegistrationResult::success(
            $accountId,
            $characterId,
            $normalizedLoginName,
            $emailValidationKey,
        );
    }

    /**
     * @return list<string>
     */
    private function collectErrorKeyList(
        string $normalizedLoginName,
        string $plainPassword,
        string $plainPasswordConfirmation,
        string $emailAddress,
    ): array {
        $errorKeyList = [];
        $loginNameLength = \mb_strlen($normalizedLoginName);

        if ($loginNameLength < self::LOGIN_NAME_MINIMUM_LENGTH) {
            $errorKeyList[] = 'error.login-name-too-short';
        }

        if ($loginNameLength > self::LOGIN_NAME_MAXIMUM_LENGTH) {
            $errorKeyList[] = 'error.login-name-too-long';
        }

        if (!$this->passwordHasher->isAcceptableLength($plainPassword)) {
            $errorKeyList[] = 'error.password-too-short';
        }

        if ($plainPassword !== $plainPasswordConfirmation) {
            $errorKeyList[] = 'error.password-mismatch';
        }

        $requiresEmail = $this->gameSettingRepository->getBool('requireemail', false);

        if ($requiresEmail && !$this->isEmailAddress($emailAddress)) {
            $errorKeyList[] = 'error.email-invalid';
        }

        if (
            $requiresEmail
            && $this->gameSettingRepository->getBool('blockdupeemail', false)
            && $this->accountRepository->existsEmailAddress($emailAddress)
        ) {
            $errorKeyList[] = 'error.email-already-used';
        }

        if ($normalizedLoginName !== '' && $this->accountRepository->existsLoginName($normalizedLoginName)) {
            $errorKeyList[] = 'error.login-name-already-used';
        }

        if (
            $normalizedLoginName !== ''
            && $this->characterRepository->existsDisplayName($normalizedLoginName)
        ) {
            $errorKeyList[] = 'error.login-name-already-used';
        }

        return \array_values(\array_unique($errorKeyList));
    }

    private function normalizeLoginName(string $loginName): string
    {
        $allowsSpace = $this->gameSettingRepository->getBool('spaceinname', false);
        $forbiddenPattern = $allowsSpace ? '/[\[\]&#<>`_\-]/u' : '/[\[\]&#<>`_\-\s]/u';
        $normalized = \preg_replace($forbiddenPattern, '', $loginName);

        return \trim($normalized ?? '');
    }

    private function isEmailAddress(string $emailAddress): bool
    {
        return \filter_var($emailAddress, \FILTER_VALIDATE_EMAIL) !== false;
    }
}
