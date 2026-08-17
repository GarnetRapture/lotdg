<?php

declare(strict_types=1);

namespace Lotdg\Domain\Catalog;

use Lotdg\I18n\CatalogTranslator;
use Lotdg\Support\LocalizedException;
use PDO;

final class MountStableService
{
    private const float RESALE_RATE = 0.5;

    public function __construct(
        private readonly PDO $connection,
        private readonly CatalogTranslator $catalogTranslator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function browse(int $characterId, string $localeCode): array
    {
        $row = $this->fetchStableRow($characterId);
        $currentMount = $this->fetchMount((int) $row['mount_id']);

        $statement = $this->connection->query(
            'SELECT mount_id, mount_name, mount_description, mount_category,
                    cost_gold, cost_gem, extra_forest_fight, tavern_access_level
               FROM mount
              WHERE is_active = 1
              ORDER BY mount_category ASC, cost_gem ASC, cost_gold ASC',
        );

        $mountList = $statement === false ? [] : $statement->fetchAll();
        $mountList = $this->catalogTranslator->translateRowList(
            $mountList,
            CatalogTranslator::ENTITY_MOUNT,
            'mount_id',
            'mount_name',
            $localeCode,
        );
        $mountList = $this->catalogTranslator->translateRowList(
            $mountList,
            CatalogTranslator::ENTITY_MOUNT,
            'mount_id',
            'mount_description',
            $localeCode,
        );

        return [
            'gold' => (int) $row['gold'],
            'gem' => (int) $row['gem'],
            'current_mount' => $currentMount === null ? null : [
                'mount_id' => (int) $currentMount['mount_id'],
                'mount_name' => $this->translateMountName($currentMount, $localeCode),
                'resale_gold' => $this->resaleValue((int) $currentMount['cost_gold']),
                'resale_gem' => $this->resaleValue((int) $currentMount['cost_gem']),
            ],
            'mount_list' => $mountList,
        ];
    }

    /**
     * @param array<string, mixed> $mountRow
     */
    private function translateMountName(array $mountRow, string $localeCode): string
    {
        return $this->catalogTranslator->translate(
            CatalogTranslator::ENTITY_MOUNT,
            (int) $mountRow['mount_id'],
            'mount_name',
            (string) $mountRow['mount_name'],
            $localeCode,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buy(int $characterId, int $mountId, string $localeCode): array
    {
        $row = $this->fetchStableRow($characterId);
        $target = $this->fetchMount($mountId);

        if ($target === null || (int) $target['is_active'] !== 1) {
            return ['bought' => false, 'message_key' => 'stable.error.mount-not-found'];
        }

        $currentMount = $this->fetchMount((int) $row['mount_id']);
        $resaleGold = $currentMount === null ? 0 : $this->resaleValue((int) $currentMount['cost_gold']);
        $resaleGem = $currentMount === null ? 0 : $this->resaleValue((int) $currentMount['cost_gem']);

        $costGold = (int) $target['cost_gold'];
        $costGem = (int) $target['cost_gem'];

        if ((int) $row['gold'] + $resaleGold < $costGold || (int) $row['gem'] + $resaleGem < $costGem) {
            return [
                'bought' => false,
                'message_key' => 'stable.error.not-enough-payment',
                'cost_gold' => $costGold,
                'cost_gem' => $costGem,
            ];
        }

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold = gold + :gold_delta,
                            gem  = gem + :gem_delta
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'gold_delta' => $resaleGold - $costGold,
                    'gem_delta' => $resaleGem - $costGem,
                    'character_id' => $characterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_equipment SET mount_id = :mount_id WHERE character_id = :character_id',
                )
                ->execute(['mount_id' => $mountId, 'character_id' => $characterId]);

            $this->applyMountBuff($characterId, (string) $target['buff_json']);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'bought' => true,
            'mount_name' => $this->translateMountName($target, $localeCode),
            'cost_gold' => $costGold,
            'cost_gem' => $costGem,
            'trade_in_gold' => $resaleGold,
            'trade_in_gem' => $resaleGem,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sell(int $characterId, string $localeCode): array
    {
        $row = $this->fetchStableRow($characterId);
        $currentMount = $this->fetchMount((int) $row['mount_id']);

        if ($currentMount === null) {
            return ['sold' => false, 'message_key' => 'stable.error.no-mount'];
        }

        $resaleGold = $this->resaleValue((int) $currentMount['cost_gold']);
        $resaleGem = $this->resaleValue((int) $currentMount['cost_gem']);

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold = gold + :resale_gold,
                            gem  = gem + :resale_gem
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'resale_gold' => $resaleGold,
                    'resale_gem' => $resaleGem,
                    'character_id' => $characterId,
                ]);

            $this->connection
                ->prepare('UPDATE character_equipment SET mount_id = 0 WHERE character_id = :character_id')
                ->execute(['character_id' => $characterId]);

            $this->removeMountBuff($characterId);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'sold' => true,
            'mount_name' => $this->translateMountName($currentMount, $localeCode),
            'resale_gold' => $resaleGold,
            'resale_gem' => $resaleGem,
        ];
    }

    private function resaleValue(int $cost): int
    {
        return (int) \round($cost * self::RESALE_RATE);
    }

    private function applyMountBuff(int $characterId, string $buffJson): void
    {
        $mountBuff = \json_decode($buffJson, true);
        $buffList = $this->loadBuffList($characterId);

        if (\is_array($mountBuff) && $mountBuff !== []) {
            $buffList['mount'] = $mountBuff;
        } else {
            unset($buffList['mount']);
        }

        $this->storeBuffList($characterId, $buffList);
    }

    private function removeMountBuff(int $characterId): void
    {
        $buffList = $this->loadBuffList($characterId);
        unset($buffList['mount']);

        $this->storeBuffList($characterId, $buffList);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadBuffList(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT buff_list_json FROM character_combat_stat WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $encoded = $statement->fetchColumn();

        if (!\is_string($encoded)) {
            return [];
        }

        $decoded = \json_decode($encoded, true);

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $buffList
     */
    private function storeBuffList(int $characterId, array $buffList): void
    {
        $encoded = \json_encode($buffList, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        $this->connection
            ->prepare(
                'UPDATE character_combat_stat
                    SET buff_list_json = :buff_list_json
                  WHERE character_id = :character_id',
            )
            ->execute([
                'buff_list_json' => $encoded === false ? '{}' : $encoded,
                'character_id' => $characterId,
            ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchMount(int $mountId): ?array
    {
        if ($mountId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare('SELECT * FROM mount WHERE mount_id = :mount_id');
        $statement->execute(['mount_id' => $mountId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchStableRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT character_wealth.gold,
                    character_wealth.gem,
                    character_equipment.mount_id
               FROM game_character
               JOIN character_wealth    ON character_wealth.character_id = game_character.character_id
               JOIN character_equipment ON character_equipment.character_id = game_character.character_id
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
