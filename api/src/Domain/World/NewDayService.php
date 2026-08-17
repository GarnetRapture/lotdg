<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class NewDayService
{
    private const int RACE_HUMAN = 3;

    private const int SPECIALTY_DARK_ARTS = 1;

    private const int SPECIALTY_MYSTICAL_POWER = 2;

    private const int SPECIALTY_THIEVERY = 3;

    private const int HANGOVER_DRUNKENNESS_THRESHOLD = 66;

    private const int SOUL_POINT_BASE = 50;

    private const int SOUL_POINT_PER_LEVEL = 5;

    private const int RESURRECTION_SPIRIT = -6;

    private const int RESURRECTION_DEATH_POWER_COST = 100;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(int $characterId, bool $isResurrection = false): array
    {
        $row = $this->fetchNewDayRow($characterId);

        $turnsPerDay = $this->gameSettingRepository->getInt('turns', 10);
        $spirit = $isResurrection ? self::RESURRECTION_SPIRIT : $this->rollSpirit();
        $dragonPointForestFight = $this->countDragonPointForestFight(
            (string) $row['dragon_point_json'],
        );

        $forestTurn = $turnsPerDay + $spirit + $dragonPointForestFight;
        $turnNoteList = [];

        if ((int) $row['drunkenness'] > self::HANGOVER_DRUNKENNESS_THRESHOLD) {
            --$forestTurn;
            $turnNoteList[] = 'newday.note.hangover';
        }

        if ((int) $row['race_code'] === self::RACE_HUMAN) {
            ++$forestTurn;
            $turnNoteList[] = 'newday.note.human-bonus';
        }

        $mountForestFight = $this->resolveMountForestFight((int) $row['mount_id']);

        if ($mountForestFight > 0) {
            $forestTurn += $mountForestFight;
            $turnNoteList[] = 'newday.note.mount-bonus';
        }

        if ((string) $row['haunted_by_name'] !== '') {
            --$forestTurn;
            $turnNoteList[] = 'newday.note.haunted';
        }

        $forestTurn = \max(0, $forestTurn);

        $interestRate = $this->resolveInterestRate(
            (int) $row['forest_turn'],
            (int) $row['gold_in_bank'],
        );
        $interestGold = (int) ($row['gold_in_bank'] * ($interestRate - 1));

        $specialtyBonus = $this->gameSettingRepository->getInt('specialtybonus', 1);
        $specialtyCode = (int) $row['specialty_code'];

        $this->connection->beginTransaction();

        try {
            $this->applyVital($characterId, (int) $row['max_hit_point'], $spirit);
            $this->applyDailyAllowance($characterId, $forestTurn);
            $this->applyProgression($characterId);
            $this->applySpecialtyUse($characterId, $row, $specialtyCode, $specialtyBonus);
            $this->applyWealth($characterId, $interestGold);
            $this->applySocial($characterId);

            if (!$isResurrection) {
                $this->applyDeathResource($characterId, (int) $row['level']);
            } else {
                $this->applyResurrectionCost($characterId);
            }

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'new_day' => true,
            'resurrection' => $isResurrection,
            'game_age_day' => (int) $row['game_age_day'] + 1,
            'spirit_level' => $spirit,
            'forest_turn' => $forestTurn,
            'turn_note_key_list' => $turnNoteList,
            'interest_rate_percent' => (int) \round(($interestRate - 1) * 100),
            'interest_gold' => $interestGold,
            'hit_point' => (int) $row['max_hit_point'],
            'player_fight' => $this->gameSettingRepository->getInt('pvpday', 3),
            'specialty_use' => $this->calculateSpecialtyUse($row, $specialtyCode, $specialtyBonus),
        ];
    }

    private function rollSpirit(): int
    {
        return \random_int(-1, 1) + \random_int(-1, 1);
    }

    private function resolveInterestRate(int $remainingForestTurn, int $goldInBank): float
    {
        $fightsForInterest = $this->gameSettingRepository->getInt('fightsforinterest', 4);

        if ($remainingForestTurn > $fightsForInterest && $goldInBank >= 0) {
            return 1.0;
        }

        $minimumInterest = $this->gameSettingRepository->getInt('mininterest', 1);
        $maximumInterest = $this->gameSettingRepository->getInt('maxinterest', 3);

        if ($minimumInterest > $maximumInterest) {
            [$minimumInterest, $maximumInterest] = [$maximumInterest, $minimumInterest];
        }

        return \random_int($minimumInterest * 100 + 100, $maximumInterest * 100 + 100) / 10000 + 1;
    }

    private function countDragonPointForestFight(string $dragonPointJson): int
    {
        $decoded = \json_decode($dragonPointJson, true);

        if (!\is_array($decoded)) {
            return 0;
        }

        return \count(\array_filter($decoded, static fn (mixed $point): bool => $point === 'ff'));
    }

    private function resolveMountForestFight(int $mountId): int
    {
        if ($mountId <= 0) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'SELECT extra_forest_fight FROM mount WHERE mount_id = :mount_id',
        );
        $statement->execute(['mount_id' => $mountId]);

        $extraForestFight = $statement->fetchColumn();

        return $extraForestFight === false ? 0 : (int) $extraForestFight;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, int>
     */
    private function calculateSpecialtyUse(array $row, int $specialtyCode, int $specialtyBonus): array
    {
        return [
            'dark_arts_use' => (int) ((int) $row['dark_arts_rank'] / 3)
                + ($specialtyCode === self::SPECIALTY_DARK_ARTS ? $specialtyBonus : 0),
            'mystical_power_use' => (int) ((int) $row['mystical_power_rank'] / 3)
                + ($specialtyCode === self::SPECIALTY_MYSTICAL_POWER ? $specialtyBonus : 0),
            'thievery_use' => (int) ((int) $row['thievery_rank'] / 3)
                + ($specialtyCode === self::SPECIALTY_THIEVERY ? $specialtyBonus : 0),
        ];
    }

    private function applyVital(int $characterId, int $maxHitPoint, int $spirit): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET hit_point       = :hit_point,
                        is_alive        = 1,
                        spirit_level    = :spirit_level,
                        haunted_by_name = \'\',
                        slain_by_name   = \'\',
                        killed_in_area  = \'\'
                  WHERE character_id = :character_id',
            )
            ->execute([
                'hit_point' => $maxHitPoint,
                'spirit_level' => $this->normalizeSpirit($spirit),
                'character_id' => $characterId,
            ]);
    }

    private function normalizeSpirit(int $spirit): int
    {
        return \in_array($spirit, [-6, -2, -1, 0, 1, 2], true) ? $spirit : 0;
    }

    private function applyDailyAllowance(int $characterId, int $forestTurn): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn         = :forest_turn,
                        player_fight        = :player_fight,
                        drunkenness         = 0,
                        bought_room_today   = 0,
                        used_outhouse_today = 0
                  WHERE character_id = :character_id',
            )
            ->execute([
                'forest_turn' => $forestTurn,
                'player_fight' => $this->gameSettingRepository->getInt('pvpday', 3),
                'character_id' => $characterId,
            ]);
    }

    private function applyProgression(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_progression
                    SET game_age_day      = game_age_day + 1,
                        seen_master_level = 0,
                        has_seen_dragon   = 0,
                        has_seen_bard     = 0,
                        has_seen_lover    = 0
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function applySpecialtyUse(
        int $characterId,
        array $row,
        int $specialtyCode,
        int $specialtyBonus,
    ): void {
        $useMap = $this->calculateSpecialtyUse($row, $specialtyCode, $specialtyBonus);

        $this->connection
            ->prepare(
                'UPDATE character_specialty
                    SET dark_arts_use      = :dark_arts_use,
                        mystical_power_use = :mystical_power_use,
                        thievery_use       = :thievery_use
                  WHERE character_id = :character_id',
            )
            ->execute([
                'dark_arts_use' => $useMap['dark_arts_use'],
                'mystical_power_use' => $useMap['mystical_power_use'],
                'thievery_use' => $useMap['thievery_use'],
                'character_id' => $characterId,
            ]);
    }

    private function applyWealth(int $characterId, int $interestGold): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gold_in_bank      = gold_in_bank + :interest_gold,
                        transferred_today = 0,
                        received_today    = 0,
                        bounty_set_today  = 0
                  WHERE character_id = :character_id',
            )
            ->execute(['interest_gold' => $interestGold, 'character_id' => $characterId]);
    }

    private function applySocial(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_social
                    SET comments_seen_at = datetime(\'now\')
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);
    }

    private function applyDeathResource(int $characterId, int $level): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET soul_point  = :soul_point,
                        grave_fight = :grave_fight
                  WHERE character_id = :character_id',
            )
            ->execute([
                'soul_point' => self::SOUL_POINT_BASE + self::SOUL_POINT_PER_LEVEL * $level,
                'grave_fight' => $this->gameSettingRepository->getInt('gravefightsperday', 10),
                'character_id' => $characterId,
            ]);
    }

    private function applyResurrectionCost(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET death_power        = MAX(0, death_power - :death_power_cost),
                        resurrection_count = resurrection_count + 1
                  WHERE character_id = :character_id',
            )
            ->execute([
                'death_power_cost' => self::RESURRECTION_DEATH_POWER_COST,
                'character_id' => $characterId,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchNewDayRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.level,
                    game_character.race_code,
                    character_vital.max_hit_point,
                    character_vital.haunted_by_name,
                    character_progression.game_age_day,
                    character_progression.dragon_point_json,
                    character_specialty.specialty_code,
                    character_specialty.dark_arts_rank,
                    character_specialty.mystical_power_rank,
                    character_specialty.thievery_rank,
                    character_equipment.mount_id,
                    character_wealth.gold_in_bank,
                    character_daily_allowance.forest_turn,
                    character_daily_allowance.drunkenness
               FROM game_character
               JOIN character_vital           ON character_vital.character_id = game_character.character_id
               JOIN character_progression     ON character_progression.character_id = game_character.character_id
               JOIN character_specialty       ON character_specialty.character_id = game_character.character_id
               JOIN character_equipment       ON character_equipment.character_id = game_character.character_id
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
