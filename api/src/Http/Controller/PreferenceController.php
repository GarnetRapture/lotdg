<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\PreferenceService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class PreferenceController implements ControllerInterface
{
    private readonly PreferenceService $preferenceService;

    public function __construct(PDO $connection)
    {
        $this->preferenceService = new PreferenceService(
            $connection,
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
        $characterId = (int) ($parameterMap['character_id'] ?? 0);

        if ($characterId <= 0) {
            throw new LocalizedException('system-message', 'error.invalid-character-id');
        }

        return match ($parameterMap['action'] ?? 'inspect') {
            'inspect' => $this->preferenceService->inspect($characterId),
            'save' => $this->preferenceService->save(
                $characterId,
                $this->readStringField('locale_code'),
                $this->readStringField('template_name'),
                $this->readStringField('email_address'),
                $this->readStringField('biography'),
                $this->readNotificationMap(),
            ),
            'password' => $this->preferenceService->changePassword(
                $characterId,
                $this->readStringField('password'),
                $this->readStringField('password_confirmation'),
            ),
            'delete' => $this->preferenceService->deleteCharacter($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    /**
     * @return array<string, string>
     */
    private function readNotificationMap(): array
    {
        $notificationMap = [];

        foreach (['emailonmail', 'systemmail', 'dirtyemail'] as $key) {
            if (isset($_POST[$key]) && \is_string($_POST[$key])) {
                $notificationMap[$key] = $_POST[$key];
            }
        }

        return $notificationMap;
    }

    private function readStringField(string $fieldName): string
    {
        $value = $_POST[$fieldName] ?? '';

        return \is_string($value) ? \trim($value) : '';
    }
}
