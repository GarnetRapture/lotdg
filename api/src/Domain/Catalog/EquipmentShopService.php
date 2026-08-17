<?php

declare(strict_types=1);

namespace Lotdg\Domain\Catalog;

use Lotdg\I18n\CatalogTranslator;
use Lotdg\Support\LocalizedException;
use PDO;

final class EquipmentShopService
{
    public const string SHOP_WEAPON = 'weapon';

    public const string SHOP_ARMOR = 'armor';

    private const float TRADE_IN_RATE = 0.5;

    private const float THEFT_EXPERIENCE_PENALTY_RATE = 0.9;

    public function __construct(
        private readonly PDO $connection,
        private readonly CatalogTranslator $catalogTranslator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function browse(int $characterId, string $shopType, string $localeCode): array
    {
        $characterRow = $this->fetchShopRow($characterId);
        $dragonKillCount = (int) $characterRow['dragon_kill_count'];
        $tier = $this->resolvePurchasableTier($shopType, $dragonKillCount);
        $tradeInValue = $this->tradeInValue($shopType, $characterRow);
        $spendableGold = (int) $characterRow['gold'] + $tradeInValue;
        $entityType = $this->entityType($shopType);

        return [
            'shop_type' => $shopType,
            'dragon_kill_tier' => $tier,
            'trade_in_value' => $tradeInValue,
            'gold' => (int) $characterRow['gold'],
            'item_list' => \array_map(
                fn (array $itemRow): array => [
                    'item_id' => (int) ($itemRow['item_id']),
                    'item_name' => $this->catalogTranslator->translate(
                        $entityType,
                        (int) $itemRow['item_id'],
                        $entityType === CatalogTranslator::ENTITY_WEAPON ? 'weapon_name' : 'armor_name',
                        (string) $itemRow['item_name'],
                        $localeCode,
                    ),
                    'price' => (int) $itemRow['price'],
                    'power' => (int) $itemRow['power'],
                    'affordable' => (int) $itemRow['price'] <= $spendableGold,
                ],
                $this->fetchItemListByTier($shopType, $tier),
            ),
        ];
    }

    private function entityType(string $shopType): string
    {
        return $shopType === self::SHOP_WEAPON
            ? CatalogTranslator::ENTITY_WEAPON
            : CatalogTranslator::ENTITY_ARMOR;
    }

    /**
     * @return array<string, mixed>
     */
    public function buy(int $characterId, string $shopType, int $itemId, string $localeCode): array
    {
        $characterRow = $this->fetchShopRow($characterId);
        $itemRow = $this->fetchItem($shopType, $itemId);

        if ($itemRow === null) {
            return ['succeeded' => false, 'message_key' => 'shop.error.item-not-found'];
        }

        $tradeInValue = $this->tradeInValue($shopType, $characterRow);
        $price = (int) $itemRow['price'];

        if ($price > (int) $characterRow['gold'] + $tradeInValue) {
            $this->punishTheft($characterId, (int) $characterRow['experience']);

            return [
                'succeeded' => false,
                'message_key' => 'shop.error.caught-stealing',
                'slain' => true,
                'gold_lost' => (int) $characterRow['gold'],
                'experience_penalty_rate' => 1 - self::THEFT_EXPERIENCE_PENALTY_RATE,
            ];
        }

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold = gold - :price + :trade_in_value
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'price' => $price,
                    'trade_in_value' => $tradeInValue,
                    'character_id' => $characterId,
                ]);

            if ($shopType === self::SHOP_WEAPON) {
                $this->equipWeapon($characterId, $itemRow, (int) $characterRow['weapon_damage']);
            } else {
                $this->equipArmor($characterId, $itemRow, (int) $characterRow['armor_defense']);
            }

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        $entityType = $this->entityType($shopType);

        return [
            'succeeded' => true,
            'item_name' => $this->catalogTranslator->translate(
                $entityType,
                (int) $itemRow['item_id'],
                $entityType === CatalogTranslator::ENTITY_WEAPON ? 'weapon_name' : 'armor_name',
                (string) $itemRow['item_name'],
                $localeCode,
            ),
            'price' => $price,
            'trade_in_value' => $tradeInValue,
            'power' => (int) $itemRow['power'],
        ];
    }

    /**
     * @param array<string, mixed> $itemRow
     */
    private function equipWeapon(int $characterId, array $itemRow, int $previousDamage): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_equipment
                    SET weapon_id     = :weapon_id,
                        weapon_name   = :weapon_name,
                        weapon_value  = :weapon_value,
                        weapon_damage = :weapon_damage
                  WHERE character_id = :character_id',
            )
            ->execute([
                'weapon_id' => (int) $itemRow['item_id'],
                'weapon_name' => (string) $itemRow['item_name'],
                'weapon_value' => (int) $itemRow['price'],
                'weapon_damage' => (int) $itemRow['power'],
                'character_id' => $characterId,
            ]);

        $this->connection
            ->prepare(
                'UPDATE character_combat_stat
                    SET attack_point = MAX(0, attack_point - :previous_power + :new_power)
                  WHERE character_id = :character_id',
            )
            ->execute([
                'previous_power' => $previousDamage,
                'new_power' => (int) $itemRow['power'],
                'character_id' => $characterId,
            ]);
    }

    /**
     * @param array<string, mixed> $itemRow
     */
    private function equipArmor(int $characterId, array $itemRow, int $previousDefense): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_equipment
                    SET armor_id      = :armor_id,
                        armor_name    = :armor_name,
                        armor_value   = :armor_value,
                        armor_defense = :armor_defense
                  WHERE character_id = :character_id',
            )
            ->execute([
                'armor_id' => (int) $itemRow['item_id'],
                'armor_name' => (string) $itemRow['item_name'],
                'armor_value' => (int) $itemRow['price'],
                'armor_defense' => (int) $itemRow['power'],
                'character_id' => $characterId,
            ]);

        $this->connection
            ->prepare(
                'UPDATE character_combat_stat
                    SET defence_point = MAX(0, defence_point - :previous_power + :new_power)
                  WHERE character_id = :character_id',
            )
            ->execute([
                'previous_power' => $previousDefense,
                'new_power' => (int) $itemRow['power'],
                'character_id' => $characterId,
            ]);
    }

    private function punishTheft(int $characterId, int $experience): void
    {
        $this->connection
            ->prepare('UPDATE character_wealth SET gold = 0 WHERE character_id = :character_id')
            ->execute(['character_id' => $characterId]);

        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET hit_point      = 0,
                        is_alive       = 0,
                        slain_by_name  = :slain_by_name,
                        killed_in_area = \'shop\'
                  WHERE character_id = :character_id',
            )
            ->execute(['slain_by_name' => 'MightyE', 'character_id' => $characterId]);

        $this->connection
            ->prepare(
                'UPDATE character_progression
                    SET experience = :experience
                  WHERE character_id = :character_id',
            )
            ->execute([
                'experience' => (int) \round($experience * self::THEFT_EXPERIENCE_PENALTY_RATE),
                'character_id' => $characterId,
            ]);
    }

    /**
     * @param array<string, mixed> $characterRow
     */
    private function tradeInValue(string $shopType, array $characterRow): int
    {
        $currentValue = $shopType === self::SHOP_WEAPON
            ? (int) $characterRow['weapon_value']
            : (int) $characterRow['armor_value'];

        return (int) \round($currentValue * self::TRADE_IN_RATE);
    }

    private function resolvePurchasableTier(string $shopType, int $dragonKillCount): int
    {
        $tableName = $shopType === self::SHOP_WEAPON ? 'weapon' : 'armor';

        $statement = $this->connection->prepare(
            \sprintf(
                'SELECT MAX(dragon_kill_tier) FROM %s WHERE dragon_kill_tier <= :dragon_kill_count',
                $tableName,
            ),
        );
        $statement->execute(['dragon_kill_count' => $dragonKillCount]);

        $tier = $statement->fetchColumn();

        return $tier === false || $tier === null ? 0 : (int) $tier;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchItemListByTier(string $shopType, int $tier): array
    {
        $sql = $shopType === self::SHOP_WEAPON
            ? 'SELECT weapon_id AS item_id, weapon_name AS item_name, price, damage AS power
                 FROM weapon WHERE dragon_kill_tier = :tier ORDER BY damage ASC'
            : 'SELECT armor_id AS item_id, armor_name AS item_name, price, defense AS power
                 FROM armor WHERE dragon_kill_tier = :tier ORDER BY price ASC';

        $statement = $this->connection->prepare($sql);
        $statement->execute(['tier' => $tier]);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchItem(string $shopType, int $itemId): ?array
    {
        $sql = $shopType === self::SHOP_WEAPON
            ? 'SELECT weapon_id AS item_id, weapon_name AS item_name, price, damage AS power
                 FROM weapon WHERE weapon_id = :item_id'
            : 'SELECT armor_id AS item_id, armor_name AS item_name, price, defense AS power
                 FROM armor WHERE armor_id = :item_id';

        $statement = $this->connection->prepare($sql);
        $statement->execute(['item_id' => $itemId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchShopRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.character_id,
                    character_wealth.gold,
                    character_equipment.weapon_value,
                    character_equipment.weapon_damage,
                    character_equipment.armor_value,
                    character_equipment.armor_defense,
                    character_progression.dragon_kill_count,
                    character_progression.experience
               FROM game_character
               JOIN character_wealth      ON character_wealth.character_id = game_character.character_id
               JOIN character_equipment   ON character_equipment.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
              WHERE game_character.character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return $row;
    }
}
