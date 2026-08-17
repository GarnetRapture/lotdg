<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Domain\Social\NewsService;
use Lotdg\Support\LocalizedException;
use PDO;

final class OuthouseService
{
    public const string TOILET_PRIVATE = 'private';

    public const string TOILET_PUBLIC = 'public';

    private const int PRIVATE_COST = 5;

    private const int WASH_REWARD_GOLD = 3;

    private const int NO_WASH_PENALTY_GOLD = 1;

    private const int MINIMUM_GOLD_TO_LOSE = 1;

    private const int GOOD_HABIT_MINIMUM = 1;

    private const int GOOD_HABIT_MAXIMUM = 10;

    private const int GOOD_HABIT_THRESHOLD = 6;

    private const int BAD_HABIT_MINIMUM = 1;

    private const int BAD_HABIT_MAXIMUM = 4;

    private const int BAD_HABIT_THRESHOLD = 2;

    private const int GEM_CHANCE_PERCENT = 25;

    private const float DRUNKENNESS_DECAY = 0.9;

    public function __construct(
        private readonly PDO $connection,
        private readonly NewsService $newsService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId): array
    {
        $row = $this->fetchOuthouseRow($characterId);

        return [
            'used_today' => (int) $row['used_outhouse_today'] === 1,
            'gold' => (int) $row['gold'],
            'private_cost' => self::PRIVATE_COST,
            'can_pay' => (int) $row['gold'] >= self::PRIVATE_COST,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function useToilet(int $characterId, string $toiletType): array
    {
        $row = $this->fetchOuthouseRow($characterId);

        if ((int) $row['used_outhouse_today'] === 1) {
            return ['used' => false, 'message_key' => 'outhouse.error.already-used-today'];
        }

        if ($toiletType === self::TOILET_PRIVATE) {
            if ((int) $row['gold'] < self::PRIVATE_COST) {
                return ['used' => false, 'message_key' => 'outhouse.error.not-enough-gold'];
            }

            $this->connection
                ->prepare('UPDATE character_wealth SET gold = gold - :cost WHERE character_id = :character_id')
                ->execute(['cost' => self::PRIVATE_COST, 'character_id' => $characterId]);
        } elseif ($toiletType !== self::TOILET_PUBLIC) {
            return ['used' => false, 'message_key' => 'outhouse.error.unknown-toilet'];
        }

        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET used_outhouse_today = 1
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return [
            'used' => true,
            'toilet_type' => $toiletType,
            'paid' => $toiletType === self::TOILET_PRIVATE ? self::PRIVATE_COST : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function washHands(int $characterId, string $toiletType): array
    {
        $row = $this->fetchOuthouseRow($characterId);
        $goodHabitRoll = \random_int(self::GOOD_HABIT_MINIMUM, self::GOOD_HABIT_MAXIMUM);

        if ($goodHabitRoll <= self::GOOD_HABIT_THRESHOLD) {
            return ['washed' => true, 'rewarded' => false];
        }

        if ($toiletType === self::TOILET_PUBLIC) {
            if (\random_int(1, 3) !== 1) {
                return ['washed' => true, 'rewarded' => false];
            }

            $this->connection
                ->prepare('UPDATE character_wealth SET gold = gold + :gold WHERE character_id = :character_id')
                ->execute(['gold' => self::WASH_REWARD_GOLD, 'character_id' => $characterId]);

            return [
                'washed' => true,
                'rewarded' => true,
                'gold_gained' => self::WASH_REWARD_GOLD,
                'gem_gained' => 0,
            ];
        }

        $gemGained = \random_int(1, 100) <= self::GEM_CHANCE_PERCENT ? 1 : 0;
        $drunkenness = (int) $row['drunkenness'];
        $newDrunkenness = $drunkenness > 0
            ? (int) \round($drunkenness * self::DRUNKENNESS_DECAY)
            : $drunkenness;

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold = gold + :gold,
                            gem  = gem + :gem
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'gold' => self::WASH_REWARD_GOLD,
                    'gem' => $gemGained,
                    'character_id' => $characterId,
                ]);

            if ($newDrunkenness !== $drunkenness) {
                $this->connection
                    ->prepare(
                        'UPDATE character_daily_allowance
                            SET drunkenness = :drunkenness
                          WHERE character_id = :character_id',
                    )
                    ->execute([
                        'drunkenness' => $newDrunkenness,
                        'character_id' => $characterId,
                    ]);
            }

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'washed' => true,
            'rewarded' => true,
            'gold_gained' => self::WASH_REWARD_GOLD,
            'gem_gained' => $gemGained,
            'drunkenness' => $newDrunkenness,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function skipWashing(int $characterId): array
    {
        $row = $this->fetchOuthouseRow($characterId);
        $badHabitRoll = \random_int(self::BAD_HABIT_MINIMUM, self::BAD_HABIT_MAXIMUM);

        if ($badHabitRoll < self::BAD_HABIT_THRESHOLD) {
            return ['skipped' => true, 'punished' => false];
        }

        $goldLost = 0;

        if ((int) $row['gold'] >= self::MINIMUM_GOLD_TO_LOSE) {
            $goldLost = self::NO_WASH_PENALTY_GOLD;

            $this->connection
                ->prepare('UPDATE character_wealth SET gold = MAX(0, gold - :gold) WHERE character_id = :character_id')
                ->execute(['gold' => $goldLost, 'character_id' => $characterId]);
        }

        $this->newsService->publish(
            \sprintf('`2%s`2 outhouse.news.toilet-paper', (string) $row['display_name']),
            (int) $row['account_id'],
        );

        return ['skipped' => true, 'punished' => true, 'gold_lost' => $goldLost];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchOuthouseRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.account_id,
                    game_character.display_name,
                    game_character.sex_code,
                    character_wealth.gold,
                    character_daily_allowance.used_outhouse_today,
                    character_daily_allowance.drunkenness
               FROM game_character
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
