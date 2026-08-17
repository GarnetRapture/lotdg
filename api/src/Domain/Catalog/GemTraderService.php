<?php

declare(strict_types=1);

namespace Lotdg\Domain\Catalog;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class GemTraderService
{
    public const int MAXIMUM_LEVEL = 15;

    private const int SELL_PRICE_PER_GEM = 3000;

    private const int STOCK_LIMIT = 50;

    /**
     * @var array<int, array{gem: int, gold: int}>
     */
    private const PURCHASE_OPTION = [
        1 => ['gem' => 1, 'gold' => 6500],
        2 => ['gem' => 3, 'gold' => 125000],
        3 => ['gem' => 5, 'gold' => 18500],
    ];

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId): array
    {
        $row = $this->fetchTraderRow($characterId);
        $stock = $this->gameSettingRepository->getInt('selledgems', 0);

        $optionList = [];

        foreach (self::PURCHASE_OPTION as $optionCode => $option) {
            $optionList[] = [
                'option_code' => $optionCode,
                'gem' => $option['gem'],
                'gold' => $option['gold'],
                'available' => $stock >= $option['gem'] && (int) $row['gold'] >= $option['gold'],
            ];
        }

        return [
            'available' => (int) $row['level'] < self::MAXIMUM_LEVEL,
            'gold' => (int) $row['gold'],
            'gem' => (int) $row['gem'],
            'stock' => $stock,
            'sell_price_per_gem' => self::SELL_PRICE_PER_GEM,
            'purchase_option_list' => $optionList,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buy(int $characterId, int $optionCode): array
    {
        $option = self::PURCHASE_OPTION[$optionCode] ?? null;

        if ($option === null) {
            return ['bought' => false, 'message_key' => 'gem-trader.error.invalid-option'];
        }

        $row = $this->fetchTraderRow($characterId);

        if ((int) $row['level'] >= self::MAXIMUM_LEVEL) {
            return ['bought' => false, 'message_key' => 'gem-trader.error.level-too-high'];
        }

        if ((int) $row['gold'] < $option['gold']) {
            return ['bought' => false, 'message_key' => 'gem-trader.error.not-enough-gold'];
        }

        $stock = $this->gameSettingRepository->getInt('selledgems', 0);

        if ($stock < $option['gem']) {
            return ['bought' => false, 'message_key' => 'gem-trader.error.out-of-stock'];
        }

        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gold = gold - :gold,
                        gem  = gem + :gem
                  WHERE character_id = :character_id',
            )
            ->execute([
                'gold' => $option['gold'],
                'gem' => $option['gem'],
                'character_id' => $characterId,
            ]);

        $this->gameSettingRepository->put(
            'selledgems',
            (string) \max(0, $stock - $option['gem']),
        );

        return [
            'bought' => true,
            'gem' => $option['gem'],
            'gold' => $option['gold'],
            'stock' => \max(0, $stock - $option['gem']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sell(int $characterId): array
    {
        $row = $this->fetchTraderRow($characterId);

        if ((int) $row['level'] >= self::MAXIMUM_LEVEL) {
            return ['sold' => false, 'message_key' => 'gem-trader.error.level-too-high'];
        }

        if ((int) $row['gem'] < 1) {
            return ['sold' => false, 'message_key' => 'gem-trader.error.no-gem'];
        }

        $stock = $this->gameSettingRepository->getInt('selledgems', 0);

        if ($stock >= self::STOCK_LIMIT) {
            return ['sold' => false, 'message_key' => 'gem-trader.error.stock-full'];
        }

        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gold = gold + :gold,
                        gem  = gem - 1
                  WHERE character_id = :character_id',
            )
            ->execute([
                'gold' => self::SELL_PRICE_PER_GEM,
                'character_id' => $characterId,
            ]);

        $this->gameSettingRepository->put('selledgems', (string) ($stock + 1));

        return [
            'sold' => true,
            'gold' => self::SELL_PRICE_PER_GEM,
            'stock' => $stock + 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchTraderRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.level,
                    character_wealth.gold,
                    character_wealth.gem
               FROM game_character
               JOIN character_wealth ON character_wealth.character_id = game_character.character_id
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
