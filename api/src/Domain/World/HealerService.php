<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Support\LocalizedException;
use PDO;

final class HealerService
{
    private const float GOLINDA_DISCOUNT_RATE = 0.5;

    private const int BASE_FEE_MULTIPLIER = 10;

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId): array
    {
        $row = $this->fetchHealerRow($characterId);
        $fullCost = $this->fullHealCost($row);

        $priceList = [];

        foreach ([100, 90, 80, 70, 60, 50, 40, 30, 20, 10] as $percent) {
            $priceList[] = [
                'percent' => $percent,
                'price' => (int) \round($fullCost * $percent / 100),
            ];
        }

        return [
            'is_golinda' => $this->isGolinda($row),
            'hit_point' => (int) $row['hit_point'],
            'max_hit_point' => (int) $row['max_hit_point'],
            'gold' => (int) $row['gold'],
            'full_heal_cost' => $fullCost,
            'price_list' => $priceList,
            'needs_healing' => (int) $row['hit_point'] < (int) $row['max_hit_point'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buyPotion(int $characterId, int $percent): array
    {
        if ($percent < 10 || $percent > 100) {
            return ['healed' => false, 'message_key' => 'healer.error.invalid-percent'];
        }

        $row = $this->fetchHealerRow($characterId);

        if ((int) $row['hit_point'] >= (int) $row['max_hit_point']) {
            return ['healed' => false, 'message_key' => 'healer.error.already-full'];
        }

        $price = (int) \round($this->fullHealCost($row) * $percent / 100);

        if ((int) $row['gold'] < $price) {
            return [
                'healed' => false,
                'message_key' => 'healer.error.not-enough-gold',
                'price' => $price,
            ];
        }

        $healedHitPoint = (int) \round(
            ((int) $row['max_hit_point'] - (int) $row['hit_point']) * $percent / 100,
        );

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare('UPDATE character_wealth SET gold = gold - :price WHERE character_id = :character_id')
                ->execute(['price' => $price, 'character_id' => $characterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_vital
                        SET hit_point = MIN(max_hit_point, hit_point + :healed_hit_point)
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'healed_hit_point' => $healedHitPoint,
                    'character_id' => $characterId,
                ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'healed' => true,
            'percent' => $percent,
            'price' => $price,
            'healed_hit_point' => $healedHitPoint,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function fullHealCost(array $row): int
    {
        $level = \max(1, (int) $row['level']);
        $logLevel = \log($level);
        $missingHitPoint = \max(0, (int) $row['max_hit_point'] - (int) $row['hit_point']);

        $cost = $logLevel * $missingHitPoint + $logLevel * self::BASE_FEE_MULTIPLIER;

        if ($this->isGolinda($row)) {
            $cost *= self::GOLINDA_DISCOUNT_RATE;
        }

        return (int) \round($cost);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isGolinda(array $row): bool
    {
        $config = \json_decode((string) $row['donation_config_json'], true);

        return \is_array($config) && (int) ($config['healer'] ?? 0) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchHealerRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.level,
                    character_vital.hit_point,
                    character_vital.max_hit_point,
                    character_wealth.gold,
                    account_donation.donation_config_json
               FROM game_character
               JOIN character_vital   ON character_vital.character_id = game_character.character_id
               JOIN character_wealth  ON character_wealth.character_id = game_character.character_id
               JOIN account_donation  ON account_donation.account_id = game_character.account_id
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
