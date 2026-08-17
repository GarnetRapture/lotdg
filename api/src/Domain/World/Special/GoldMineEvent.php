<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Domain\Social\NewsService;
use Lotdg\Support\LocalizedException;
use PDO;

final class GoldMineEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'goldmine';

    public const string OUTCOME_NOTHING = 'nothing';

    public const string OUTCOME_GOLD = 'gold';

    public const string OUTCOME_GEM = 'gem';

    public const string OUTCOME_TREASURE = 'treasure';

    public const string OUTCOME_CAVE_IN_SURVIVED = 'cave-in-survived';

    public const string OUTCOME_CAVE_IN_DEATH = 'cave-in-death';

    private const int RACE_DWARF = 4;

    private const int TETHER_DENOMINATOR = 10;

    private const int NON_DWARF_SURVIVE_DENOMINATOR = 10;

    private const int DWARF_DEATH_DENOMINATOR = 20;

    private const float DEATH_EXPERIENCE_RATE = 0.6;

    public function __construct(
        private readonly PDO $connection,
        private readonly SpecialEventState $eventState,
        private readonly NewsService $newsService,
    ) {
    }

    public function eventCode(): string
    {
        return self::EVENT_CODE;
    }

    /**
     * @return array<string, mixed>
     */
    public function start(int $characterId): array
    {
        $this->eventState->store($characterId, self::EVENT_CODE, ['stage' => 'awaiting-choice']);

        return ['stage' => 'awaiting-choice'];
    }

    /**
     * @return array<string, mixed>
     */
    public function decline(int $characterId): array
    {
        $this->eventState->clear($characterId);

        return ['stage' => 'declined'];
    }

    /**
     * @return array<string, mixed>
     */
    public function mine(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-choice') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $row = $this->fetchCharacterRow($characterId);

        if ((int) $row['forest_turn'] <= 0) {
            $this->eventState->clear($characterId);

            return ['stage' => 'resolved', 'outcome' => 'too-tired'];
        }

        $mountEnters = $this->resolveMountEntry($row);
        $this->eventState->clear($characterId);

        $roll = \random_int(1, 20);
        $level = \max(1, (int) $row['level']);

        if ($roll <= 5) {
            $this->adjustTurn($characterId, -1);

            return $this->resolved(self::OUTCOME_NOTHING, $mountEnters, ['turn_lost' => 1]);
        }

        if ($roll <= 10) {
            $goldFound = \random_int($level * 5, $level * 20);
            $this->adjustWealth($characterId, $goldFound, 0);
            $this->adjustTurn($characterId, -1);

            return $this->resolved(
                self::OUTCOME_GOLD,
                $mountEnters,
                ['gold_gained' => $goldFound, 'turn_lost' => 1],
            );
        }

        if ($roll <= 15) {
            $gemFound = \random_int(1, \intdiv($level, 7) + 1);
            $this->adjustWealth($characterId, 0, $gemFound);
            $this->adjustTurn($characterId, -1);

            return $this->resolved(
                self::OUTCOME_GEM,
                $mountEnters,
                ['gem_gained' => $gemFound, 'turn_lost' => 1],
            );
        }

        if ($roll <= 18) {
            $goldFound = \random_int($level * 10, $level * 40);
            $gemFound = \random_int(1, \intdiv($level, 3) + 1);
            $this->adjustWealth($characterId, $goldFound, $gemFound);
            $this->adjustTurn($characterId, -1);

            return $this->resolved(
                self::OUTCOME_TREASURE,
                $mountEnters,
                ['gold_gained' => $goldFound, 'gem_gained' => $gemFound, 'turn_lost' => 1],
            );
        }

        return $this->resolveCaveIn($characterId, $row, $mountEnters);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function resolveCaveIn(int $characterId, array $row, bool $mountEnters): array
    {
        $isDwarf = (int) $row['race_code'] === self::RACE_DWARF;

        $isDead = $isDwarf
            ? \random_int(1, self::DWARF_DEATH_DENOMINATOR) === 1
            : \random_int(1, self::NON_DWARF_SURVIVE_DENOMINATOR) !== 1;

        $mountSaved = false;
        $mountDied = false;

        if ($isDead && $mountEnters) {
            $saveChance = (int) $row['mine_can_save'];

            if ($saveChance > 0 && \random_int(1, 100) < $saveChance) {
                $isDead = false;
                $mountSaved = true;
            }
        }

        if ($isDead && $mountEnters) {
            $dieChance = (int) $row['mine_can_die'];
            $mountDied = $dieChance > 0 && \random_int(1, 100) < $dieChance;
        }

        if (!$isDead) {
            $this->connection
                ->prepare(
                    'UPDATE character_daily_allowance SET forest_turn = 0 WHERE character_id = :character_id',
                )
                ->execute(['character_id' => $characterId]);

            return $this->resolved(
                self::OUTCOME_CAVE_IN_SURVIVED,
                $mountEnters,
                [
                    'is_dwarf' => $isDwarf,
                    'mount_saved' => $mountSaved,
                    'turn_lost_all' => true,
                ],
            );
        }

        $experienceGained = (int) \round((int) $row['experience'] * self::DEATH_EXPERIENCE_RATE);

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_vital
                        SET is_alive       = 0,
                            hit_point      = 0,
                            killed_in_area = :killed_in_area
                      WHERE character_id = :character_id',
                )
                ->execute(['killed_in_area' => 'mine', 'character_id' => $characterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_progression
                        SET experience = experience + :experience
                      WHERE character_id = :character_id',
                )
                ->execute(['experience' => $experienceGained, 'character_id' => $characterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_wealth SET gold = 0, gem = 0 WHERE character_id = :character_id',
                )
                ->execute(['character_id' => $characterId]);

            if ($mountDied) {
                $this->connection
                    ->prepare(
                        'UPDATE character_equipment SET mount_id = 0 WHERE character_id = :character_id',
                    )
                    ->execute(['character_id' => $characterId]);

                $this->removeMountBuff($characterId, (string) $row['buff_list_json']);
            }

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        $this->newsService->publish(
            \sprintf('`2%s`0 special.gold-mine.news.buried', (string) $row['display_name']),
            (int) $row['account_id'],
        );

        return $this->resolved(
            self::OUTCOME_CAVE_IN_DEATH,
            $mountEnters,
            [
                'is_dwarf' => $isDwarf,
                'mount_died' => $mountDied,
                'experience_gained' => $experienceGained,
            ],
        );
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function resolved(string $outcome, bool $mountEnters, array $extra): array
    {
        return [
            'stage' => 'resolved',
            'outcome' => $outcome,
            'mount_entered' => $mountEnters,
        ] + $extra;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveMountEntry(array $row): bool
    {
        if ($row['mount_name'] === null) {
            return false;
        }

        if (\random_int(1, self::TETHER_DENOMINATOR) === 1) {
            return false;
        }

        return \random_int(1, 100) <= (int) $row['mine_can_enter'];
    }

    private function adjustWealth(int $characterId, int $goldDelta, int $gemDelta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gold = MAX(0, gold + :gold_delta),
                        gem  = MAX(0, gem + :gem_delta)
                  WHERE character_id = :character_id',
            )
            ->execute([
                'gold_delta' => $goldDelta,
                'gem_delta' => $gemDelta,
                'character_id' => $characterId,
            ]);
    }

    private function adjustTurn(int $characterId, int $delta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn = MAX(0, forest_turn + :delta)
                  WHERE character_id = :character_id',
            )
            ->execute(['delta' => $delta, 'character_id' => $characterId]);
    }

    private function removeMountBuff(int $characterId, string $buffListJson): void
    {
        $buffList = \json_decode($buffListJson, true);
        $buffList = \is_array($buffList) ? $buffList : [];
        unset($buffList['mount']);

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
     * @return array<string, mixed>
     */
    private function fetchCharacterRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.account_id,
                    game_character.display_name,
                    game_character.level,
                    game_character.race_code,
                    character_progression.experience,
                    character_daily_allowance.forest_turn,
                    character_combat_stat.buff_list_json,
                    mount.mount_name,
                    COALESCE(mount.mine_can_enter, 0) AS mine_can_enter,
                    COALESCE(mount.mine_can_die, 0)   AS mine_can_die,
                    COALESCE(mount.mine_can_save, 0)  AS mine_can_save
               FROM game_character
               JOIN character_progression     ON character_progression.character_id = game_character.character_id
               JOIN character_daily_allowance ON character_daily_allowance.character_id = game_character.character_id
               JOIN character_combat_stat     ON character_combat_stat.character_id = game_character.character_id
               JOIN character_equipment       ON character_equipment.character_id = game_character.character_id
               LEFT JOIN mount                ON mount.mount_id = character_equipment.mount_id
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
