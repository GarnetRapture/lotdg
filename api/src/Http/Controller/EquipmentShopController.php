<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Catalog\EquipmentShopService;
use Lotdg\Http\ControllerInterface;
use Lotdg\I18n\CatalogTranslator;
use Lotdg\I18n\LocaleResolver;
use Lotdg\Support\LocalizedException;
use PDO;

final class EquipmentShopController implements ControllerInterface
{
    private readonly EquipmentShopService $equipmentShopService;

    private readonly LocaleResolver $localeResolver;

    public function __construct(PDO $connection)
    {
        $this->localeResolver = new LocaleResolver($connection);
        $this->equipmentShopService = new EquipmentShopService(
            $connection,
            new CatalogTranslator($connection, $this->localeResolver),
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

        $shopType = $parameterMap['shop_type'] ?? EquipmentShopService::SHOP_WEAPON;

        if (!\in_array($shopType, [EquipmentShopService::SHOP_WEAPON, EquipmentShopService::SHOP_ARMOR], true)) {
            throw new LocalizedException('system-message', 'error.unknown-shop');
        }

        $localeCode = $this->localeResolver->resolve($parameterMap['request_locale_code'] ?? null);

        return match ($parameterMap['action'] ?? 'browse') {
            'browse' => $this->equipmentShopService->browse($characterId, $shopType, $localeCode),
            'buy' => $this->equipmentShopService->buy(
                $characterId,
                $shopType,
                (int) ($_POST['item_id'] ?? 0),
                $localeCode,
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
