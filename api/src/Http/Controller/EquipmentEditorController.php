<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Account\AdministrationService;
use Lotdg\Domain\Catalog\EquipmentEditorService;
use Lotdg\Domain\Catalog\EquipmentShopService;
use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\MailService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class EquipmentEditorController implements ControllerInterface
{
    private readonly EquipmentEditorService $equipmentEditorService;

    private readonly AdministrationService $administrationService;

    private readonly MailService $mailService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->equipmentEditorService = new EquipmentEditorService($connection);
        $this->administrationService = new AdministrationService($connection, $gameSettingRepository);
        $this->mailService = new MailService(
            $connection,
            $gameSettingRepository,
            new BadWordFilter($connection, $gameSettingRepository),
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
        $shopType = $parameterMap['shop_type'] ?? '';

        if ($characterId <= 0) {
            throw new LocalizedException('system-message', 'error.invalid-character-id');
        }

        if (!\in_array($shopType, [EquipmentShopService::SHOP_WEAPON, EquipmentShopService::SHOP_ARMOR], true)) {
            throw new LocalizedException('system-message', 'error.unknown-shop');
        }

        $accountId = $this->mailService->requireAccountId($characterId);
        $this->administrationService->requireLevel($accountId, AdministrationService::LEVEL_CONTENT_ADMIN);

        return match ($parameterMap['action'] ?? 'list') {
            'list' => $this->equipmentEditorService->listByTier(
                $shopType,
                (int) ($_GET['dragon_kill_tier'] ?? 0),
            ),
            'next-power' => $this->equipmentEditorService->suggestNextPower(
                $shopType,
                (int) ($_GET['dragon_kill_tier'] ?? 0),
            ),
            'save' => $this->equipmentEditorService->save(
                $shopType,
                (int) ($_POST['item_id'] ?? 0),
                (int) ($_POST['dragon_kill_tier'] ?? 0),
                \is_string($_POST['item_name'] ?? null) ? $_POST['item_name'] : '',
                (int) ($_POST['power'] ?? 0),
            ),
            'remove' => $this->equipmentEditorService->remove(
                $shopType,
                (int) ($_POST['item_id'] ?? 0),
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
