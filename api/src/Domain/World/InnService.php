<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class InnService
{
    public const int LOCATION_INN = 1;

    public const string DRINK_ALE = 'ale';

    public const string DRINK_MEAD = 'mead';

    public const string DRINK_DWARVEN_STOUT = 'dwarven-stout';

    private const int ALE_DRUNKENNESS_GAIN = 33;

    private const int ALE_DRUNKENNESS_LIMIT = 66;

    private const float ALE_HEAL_RATE = 0.1;

    /**
     * @var array<string, array{price_multiplier: float, heal_rate: float, turn_gain: int, turn_chance: int, drunkenness: int}>
     */
    private const DRINK_OPTION = [
        self::DRINK_ALE => [
            'price_multiplier' => 1.0,
            'heal_rate' => self::ALE_HEAL_RATE,
            'turn_gain' => 1,
            'turn_chance' => 3,
            'drunkenness' => self::ALE_DRUNKENNESS_GAIN,
        ],
        self::DRINK_MEAD => [
            'price_multiplier' => 3.0,
            'heal_rate' => 0.25,
            'turn_gain' => 1,
            'turn_chance' => 2,
            'drunkenness' => 40,
        ],
        self::DRINK_DWARVEN_STOUT => [
            'price_multiplier' => 8.0,
            'heal_rate' => 0.5,
            'turn_gain' => 2,
            'turn_chance' => 1,
            'drunkenness' => 50,
        ],
    ];

    private const float ROOM_BASE_MULTIPLIER = 10.0;

    private const string ALE_BUFF_KEY = 'ale-buzz';

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function enter(int $characterId): array
    {
        $row = $this->fetchInnRow($characterId);
        $roomPrice = $this->roomPrice((int) $row['level']);

        return [
            'display_name' => (string) $row['display_name'],
            'gold' => (int) $row['gold'],
            'gold_in_bank' => (int) $row['gold_in_bank'],
            'drunkenness' => (int) $row['drunkenness'],
            'ale_price' => $this->alePrice((int) $row['level']),
            'drink_list' => \array_map(
                fn (string $drinkCode): array => [
                    'drink_code' => $drinkCode,
                    'price' => (int) \round(
                        $this->alePrice((int) $row['level'])
                        * self::DRINK_OPTION[$drinkCode]['price_multiplier'],
                    ),
                    'heal_percent' => (int) \round(self::DRINK_OPTION[$drinkCode]['heal_rate'] * 100),
                    'turn_gain' => self::DRINK_OPTION[$drinkCode]['turn_gain'],
                    'drunkenness' => self::DRINK_OPTION[$drinkCode]['drunkenness'],
                    'affordable' => (int) $row['gold'] >= (int) \round(
                        $this->alePrice((int) $row['level'])
                        * self::DRINK_OPTION[$drinkCode]['price_multiplier'],
                    ),
                ],
                \array_keys(self::DRINK_OPTION),
            ),
            'room_price' => $roomPrice,
            'room_price_from_bank' => $this->roomPriceFromBank($roomPrice),
            'bought_room_today' => (int) $row['bought_room_today'] === 1,
            'can_drink' => (int) $row['drunkenness'] <= self::ALE_DRUNKENNESS_LIMIT,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buyAle(int $characterId, string $drinkCode = self::DRINK_ALE): array
    {
        $option = self::DRINK_OPTION[$drinkCode] ?? null;

        if ($option === null) {
            return ['bought' => false, 'message_key' => 'inn.error.unknown-drink'];
        }

        $row = $this->fetchInnRow($characterId);

        if ((int) $row['drunkenness'] > self::ALE_DRUNKENNESS_LIMIT) {
            return ['bought' => false, 'message_key' => 'inn.error.too-drunk'];
        }

        $drinkPrice = (int) \round($this->alePrice((int) $row['level']) * $option['price_multiplier']);

        if ((int) $row['gold'] < $drinkPrice) {
            return ['bought' => false, 'message_key' => 'inn.error.not-enough-gold'];
        }

        $grantsTurn = \random_int(1, 3) <= $option['turn_chance'];
        $healedHitPoint = 0;
        $gainedTurn = 0;

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare('UPDATE character_wealth SET gold = gold - :ale_price WHERE character_id = :character_id')
                ->execute(['ale_price' => $drinkPrice, 'character_id' => $characterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_daily_allowance
                        SET drunkenness = drunkenness + :drunkenness_gain
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'drunkenness_gain' => $option['drunkenness'],
                    'character_id' => $characterId,
                ]);

            if ($grantsTurn) {
                $gainedTurn = $option['turn_gain'];

                $this->connection
                    ->prepare(
                        'UPDATE character_daily_allowance
                            SET forest_turn = forest_turn + :turn_gain
                          WHERE character_id = :character_id',
                    )
                    ->execute(['turn_gain' => $gainedTurn, 'character_id' => $characterId]);
            } else {
                $healedHitPoint = (int) \round((int) $row['max_hit_point'] * $option['heal_rate']);

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
            }

            $this->applyAleBuff($characterId, (string) $row['buff_list_json']);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'bought' => true,
            'drink_code' => $drinkCode,
            'ale_price' => $drinkPrice,
            'drunkenness' => (int) $row['drunkenness'] + $option['drunkenness'],
            'healed_hit_point' => $healedHitPoint,
            'gained_turn' => $gainedTurn,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rentRoom(int $characterId, bool $paysFromBank): array
    {
        $row = $this->fetchInnRow($characterId);

        if ((int) $row['bought_room_today'] === 1) {
            $this->moveToInn($characterId);

            return ['rented' => true, 'already_paid' => true, 'price' => 0];
        }

        $roomPrice = $this->roomPrice((int) $row['level']);
        $price = $paysFromBank ? $this->roomPriceFromBank($roomPrice) : $roomPrice;
        $available = $paysFromBank ? (int) $row['gold_in_bank'] : (int) $row['gold'];

        if ($available < $price) {
            return ['rented' => false, 'message_key' => 'inn.error.not-enough-gold'];
        }

        $this->connection->beginTransaction();

        try {
            $columnName = $paysFromBank ? 'gold_in_bank' : 'gold';

            $this->connection
                ->prepare(
                    \sprintf(
                        'UPDATE character_wealth SET %s = %s - :price WHERE character_id = :character_id',
                        $columnName,
                        $columnName,
                    ),
                )
                ->execute(['price' => $price, 'character_id' => $characterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_daily_allowance
                        SET bought_room_today = 1
                      WHERE character_id = :character_id',
                )
                ->execute(['character_id' => $characterId]);

            $this->moveToInn($characterId);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'rented' => true,
            'already_paid' => false,
            'price' => $price,
            'paid_from_bank' => $paysFromBank,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function changeSpecialty(int $characterId, int $specialtyCode): array
    {
        if (!\in_array($specialtyCode, [1, 2, 3], true)) {
            return ['changed' => false, 'message_key' => 'inn.error.invalid-specialty'];
        }

        $this->connection
            ->prepare(
                'UPDATE character_specialty
                    SET specialty_code = :specialty_code
                  WHERE character_id = :character_id',
            )
            ->execute([
                'specialty_code' => $specialtyCode,
                'character_id' => $characterId,
            ]);

        return ['changed' => true, 'specialty_code' => $specialtyCode];
    }

    private function roomPrice(int $level): int
    {
        return (int) \round($level * (self::ROOM_BASE_MULTIPLIER + \log(\max(1, $level))));
    }

    private function roomPriceFromBank(int $roomPrice): int
    {
        $fee = $this->gameSettingRepository->getString('innfee', '5%');

        if (\str_contains($fee, '%')) {
            $percent = (float) \rtrim($fee, '%');

            return $roomPrice + (int) \round($roomPrice * $percent / 100);
        }

        return $roomPrice + (int) $fee;
    }

    private function alePrice(int $level): int
    {
        return \max(1, $level);
    }

    private function applyAleBuff(int $characterId, string $buffListJson): void
    {
        $buffList = \json_decode($buffListJson, true);
        $buffList = \is_array($buffList) ? $buffList : [];

        $buffList[self::ALE_BUFF_KEY] = [
            'name_key' => 'inn.buff.ale-buzz',
            'rounds' => 10,
            'atkmod' => 1.25,
            'activate' => 'offense',
        ];

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

    private function moveToInn(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE game_character SET location_code = :location_code WHERE character_id = :character_id',
            )
            ->execute([
                'location_code' => self::LOCATION_INN,
                'character_id' => $characterId,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchInnRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.display_name,
                    game_character.level,
                    character_vital.hit_point,
                    character_vital.max_hit_point,
                    character_combat_stat.buff_list_json,
                    character_wealth.gold,
                    character_wealth.gold_in_bank,
                    character_daily_allowance.drunkenness,
                    character_daily_allowance.bought_room_today
               FROM game_character
               JOIN character_vital           ON character_vital.character_id = game_character.character_id
               JOIN character_combat_stat     ON character_combat_stat.character_id = game_character.character_id
               JOIN character_wealth          ON character_wealth.character_id = game_character.character_id
               JOIN character_daily_allowance ON character_daily_allowance.character_id = game_character.character_id
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
