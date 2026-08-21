<?php

declare(strict_types=1);

namespace Lotdg\Domain\Catalog;

use Lotdg\I18n\CatalogTranslator;
use PDO;

final class EquipmentEditorService
{
    public const int MINIMUM_POWER = 1;

    public const int MAXIMUM_POWER = 20;

    /** @var array<int, int> */
    private const PRICE_BY_POWER = [
        1 => 50, 2 => 300, 3 => 600, 4 => 1200, 5 => 2000,
        6 => 2500, 7 => 3200, 8 => 4000, 9 => 5200, 10 => 6500,
        11 => 8000, 12 => 9000, 13 => 12000, 14 => 15000, 15 => 18000,
        16 => 20000, 17 => 23000, 18 => 25000, 19 => 28000, 20 => 30000,
    ];

    public function __construct(
        private readonly PDO $connection,
        private readonly CatalogTranslator $catalogTranslator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function listByTier(string $shopType, int $dragonKillTier, string $localeCode): array
    {
        $isWeapon = $shopType === EquipmentShopService::SHOP_WEAPON;
        $tableName = $isWeapon ? 'weapon' : 'armor';
        $nameColumn = $isWeapon ? 'weapon_name' : 'armor_name';
        $powerColumn = $isWeapon ? 'damage' : 'defense';
        $identifierColumn = $isWeapon ? 'weapon_id' : 'armor_id';

        $statement = $this->connection->prepare(
            \sprintf(
                'SELECT %s AS item_id, %s AS item_name, %s AS power, price
                   FROM %s
                  WHERE dragon_kill_tier = :dragon_kill_tier
                  ORDER BY %s ASC',
                $identifierColumn,
                $nameColumn,
                $powerColumn,
                $tableName,
                $powerColumn,
            ),
        );
        $statement->execute(['dragon_kill_tier' => $dragonKillTier]);

        $entityType = $isWeapon
            ? CatalogTranslator::ENTITY_WEAPON
            : CatalogTranslator::ENTITY_ARMOR;

        return [
            'shop_type' => $shopType,
            'dragon_kill_tier' => $dragonKillTier,
            'maximum_tier' => $this->maximumTier($tableName),
            'minimum_power' => self::MINIMUM_POWER,
            'maximum_power' => self::MAXIMUM_POWER,
            'price_by_power' => self::PRICE_BY_POWER,
            'item_list' => \array_map(
                fn (array $row): array => [
                    'item_id' => (int) $row['item_id'],
                    'item_name' => $this->catalogTranslator->translate(
                        $entityType,
                        (int) $row['item_id'],
                        $nameColumn,
                        (string) $row['item_name'],
                        $localeCode,
                    ),
                    'source_name' => (string) $row['item_name'],
                    'power' => (int) $row['power'],
                    'price' => (int) $row['price'],
                ],
                $statement->fetchAll(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function save(
        string $shopType,
        int $itemId,
        int $dragonKillTier,
        string $itemName,
        int $power,
    ): array {
        $price = self::PRICE_BY_POWER[$power] ?? null;

        if ($price === null) {
            return ['saved' => false, 'message_key' => 'equipment.error.invalid-power'];
        }

        if (\trim($itemName) === '') {
            return ['saved' => false, 'message_key' => 'equipment.error.empty-name'];
        }

        $isWeapon = $shopType === EquipmentShopService::SHOP_WEAPON;
        $tableName = $isWeapon ? 'weapon' : 'armor';
        $nameColumn = $isWeapon ? 'weapon_name' : 'armor_name';
        $powerColumn = $isWeapon ? 'damage' : 'defense';
        $identifierColumn = $isWeapon ? 'weapon_id' : 'armor_id';

        if ($itemId > 0) {
            $statement = $this->connection->prepare(
                \sprintf(
                    'UPDATE %s
                        SET %s = :item_name,
                            %s = :power,
                            price = :price
                      WHERE %s = :item_id',
                    $tableName,
                    $nameColumn,
                    $powerColumn,
                    $identifierColumn,
                ),
            );
            $statement->execute([
                'item_name' => \trim($itemName),
                'power' => $power,
                'price' => $price,
                'item_id' => $itemId,
            ]);

            if ($statement->rowCount() === 0) {
                return ['saved' => false, 'message_key' => 'equipment.error.not-found'];
            }

            return ['saved' => true, 'item_id' => $itemId, 'power' => $power, 'price' => $price];
        }

        $this->connection
            ->prepare(
                \sprintf(
                    'INSERT INTO %s (dragon_kill_tier, %s, %s, price)
                     VALUES (:dragon_kill_tier, :item_name, :power, :price)',
                    $tableName,
                    $nameColumn,
                    $powerColumn,
                ),
            )
            ->execute([
                'dragon_kill_tier' => $dragonKillTier,
                'item_name' => \trim($itemName),
                'power' => $power,
                'price' => $price,
            ]);

        return [
            'saved' => true,
            'item_id' => (int) $this->connection->lastInsertId(),
            'power' => $power,
            'price' => $price,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(string $shopType, int $itemId): array
    {
        $isWeapon = $shopType === EquipmentShopService::SHOP_WEAPON;
        $tableName = $isWeapon ? 'weapon' : 'armor';
        $identifierColumn = $isWeapon ? 'weapon_id' : 'armor_id';

        $statement = $this->connection->prepare(
            \sprintf('DELETE FROM %s WHERE %s = :item_id', $tableName, $identifierColumn),
        );
        $statement->execute(['item_id' => $itemId]);

        return ['removed' => $statement->rowCount() > 0];
    }

    /**
     * @return array<string, mixed>
     */
    public function suggestNextPower(string $shopType, int $dragonKillTier): array
    {
        $isWeapon = $shopType === EquipmentShopService::SHOP_WEAPON;
        $tableName = $isWeapon ? 'weapon' : 'armor';
        $powerColumn = $isWeapon ? 'damage' : 'defense';

        $statement = $this->connection->prepare(
            \sprintf(
                'SELECT MAX(%s) FROM %s WHERE dragon_kill_tier = :dragon_kill_tier',
                $powerColumn,
                $tableName,
            ),
        );
        $statement->execute(['dragon_kill_tier' => $dragonKillTier]);

        $maximumPower = $statement->fetchColumn();
        $nextPower = \min(
            self::MAXIMUM_POWER,
            ($maximumPower === false || $maximumPower === null ? 0 : (int) $maximumPower) + 1,
        );

        return [
            'next_power' => \max(self::MINIMUM_POWER, $nextPower),
            'price' => self::PRICE_BY_POWER[\max(self::MINIMUM_POWER, $nextPower)],
        ];
    }

    private function maximumTier(string $tableName): int
    {
        $statement = $this->connection->query(
            \sprintf('SELECT MAX(dragon_kill_tier) FROM %s', $tableName),
        );

        if ($statement === false) {
            return 0;
        }

        $maximumTier = $statement->fetchColumn();

        return $maximumTier === false || $maximumTier === null ? 0 : (int) $maximumTier;
    }
}
