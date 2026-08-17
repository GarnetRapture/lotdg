<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\AccountRegistrationService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\AccountRepository;
use Lotdg\Persistence\Repository\CharacterRepository;
use Lotdg\Persistence\Repository\GameSettingRepository;
use PDO;

final class RegistrationController implements ControllerInterface
{
    private const string LABEL_NAMESPACE = 'authentication';

    private const array SUPPORTED_LOCALE_CODE_LIST = ['en', 'ko', 'ja', 'zh', 'ru'];

    private readonly AccountRegistrationService $registrationService;

    public function __construct(PDO $connection)
    {
        $this->registrationService = new AccountRegistrationService(
            new AccountRepository($connection),
            new CharacterRepository($connection),
            new GameSettingRepository($connection),
        );
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        unset($parameterMap);

        $result = $this->registrationService->register(
            $this->readPostField('login_name'),
            $this->readPostField('password'),
            $this->readPostField('password_confirmation'),
            $this->readPostField('email_address'),
            (int) $this->readPostField('sex_code'),
            $this->resolveLocaleCode($this->readPostField('locale_code')),
        );

        if (!$result->isSuccessful) {
            return [
                'registered' => false,
                'message_key_list' => \array_map(
                    static fn (string $errorKey): string => self::LABEL_NAMESPACE . '.' . $errorKey,
                    $result->errorKeyList,
                ),
            ];
        }

        return [
            'registered' => true,
            'account_id' => $result->accountId,
            'character_id' => $result->characterId,
            'login_name' => $result->loginName,
            'email_validation_required' => $result->emailValidationKey !== '',
        ];
    }

    private function resolveLocaleCode(string $requestedLocaleCode): string
    {
        return \in_array($requestedLocaleCode, self::SUPPORTED_LOCALE_CODE_LIST, true)
            ? $requestedLocaleCode
            : 'en';
    }

    private function readPostField(string $fieldName): string
    {
        $value = $_POST[$fieldName] ?? '';

        return \is_string($value) ? \trim($value) : '';
    }
}
