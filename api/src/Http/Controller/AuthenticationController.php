<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\AuthenticationFailureReason;
use Lotdg\Domain\Account\AuthenticationService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\AccountRepository;
use Lotdg\Persistence\Repository\CharacterRepository;
use PDO;

final class AuthenticationController implements ControllerInterface
{
    private const string LABEL_NAMESPACE = 'authentication';

    private readonly AuthenticationService $authenticationService;

    private readonly AccountRepository $accountRepository;

    private readonly CharacterRepository $characterRepository;

    public function __construct(PDO $connection)
    {
        $this->accountRepository = new AccountRepository($connection);
        $this->characterRepository = new CharacterRepository($connection);
        $this->authenticationService = new AuthenticationService($this->accountRepository);
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        unset($parameterMap);

        $loginName = $this->readPostField('login_name');
        $plainPassword = $this->readPostField('password');

        if ($loginName === '' || $plainPassword === '') {
            return [
                'authenticated' => false,
                'message_key' => self::LABEL_NAMESPACE . '.error.credential-mismatch',
            ];
        }

        $result = $this->authenticationService->authenticate($loginName, $plainPassword);

        if (!$result->isSuccessful) {
            return [
                'authenticated' => false,
                'message_key' => self::LABEL_NAMESPACE . '.error.' . $this->exposedReason(
                    $result->failureReason,
                )->value,
            ];
        }

        $accountId = (int) $result->accountId;

        $this->accountRepository->updateDeviceFingerprint(
            $accountId,
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            (string) ($_COOKIE['lotdg_device'] ?? ''),
        );

        return [
            'authenticated' => true,
            'account_id' => $accountId,
            'character_id' => $this->characterRepository->findIdByAccountId($accountId),
            'privilege' => $this->accountRepository->findPrivilege($accountId),
            'preference' => $this->accountRepository->findPreference($accountId),
        ];
    }

    private function exposedReason(?AuthenticationFailureReason $reason): AuthenticationFailureReason
    {
        return match ($reason) {
            AuthenticationFailureReason::EMAIL_NOT_VALIDATED,
            AuthenticationFailureReason::ACCESS_BANNED => $reason,
            default => AuthenticationFailureReason::CREDENTIAL_MISMATCH,
        };
    }

    private function readPostField(string $fieldName): string
    {
        $value = $_POST[$fieldName] ?? '';

        return \is_string($value) ? \trim($value) : '';
    }
}
