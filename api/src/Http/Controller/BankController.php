<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\BankService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class BankController implements ControllerInterface
{
    private readonly BankService $bankService;

    public function __construct(PDO $connection)
    {
        $this->bankService = new BankService($connection, new GameSettingRepository($connection));
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        $characterId = (int) ($parameterMap['character_id'] ?? 0);

        if ($characterId <= 0) {
            throw new LocalizedException('system-message', 'error.invalid-character-id');
        }

        return match ($parameterMap['action'] ?? 'inspect') {
            'inspect' => $this->bankService->inspect($characterId),
            'deposit' => $this->bankService->deposit($characterId, $this->readIntField('amount')),
            'withdraw' => $this->bankService->withdraw($characterId, $this->readIntField('amount'), false),
            'borrow' => $this->bankService->withdraw($characterId, $this->readIntField('amount'), true),
            'transfer' => $this->bankService->transfer(
                $characterId,
                $this->readStringField('recipient_login_name'),
                $this->readIntField('amount'),
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    private function readIntField(string $fieldName): int
    {
        return (int) ($_POST[$fieldName] ?? 0);
    }

    private function readStringField(string $fieldName): string
    {
        $value = $_POST[$fieldName] ?? '';

        return \is_string($value) ? \trim($value) : '';
    }
}
