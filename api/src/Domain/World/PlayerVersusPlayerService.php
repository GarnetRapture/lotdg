<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class PlayerVersusPlayerService
{
    private const int LEVEL_RANGE_BELOW = 1;

    private const int LEVEL_RANGE_ABOVE = 2;

    private const int LEVEL_DIFFERENCE_LIMIT = 2;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly BattleEngine $battleEngine = new BattleEngine(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function listTargets(int $characterId): array
    {
        $attacker = $this->fetchCombatRow($characterId);
        $immunityDay = $this->gameSettingRepository->getInt('pvpimmunity', 5);
        $immunityExperience = $this->gameSettingRepository->getInt('pvpminexp', 1500);
        $loginTimeout = $this->gameSettingRepository->getInt('LOGINTIMEOUT', 900);
        $pvpTimeout = $this->gameSettingRepository->getInt('pvptimeout', 600);

        $statement = $this->connection->prepare(
            'SELECT game_character.character_id,
                    game_character.display_name,
                    game_character.level,
                    character_social.player_kill_count,
                    character_social.pvp_flag_at
               FROM game_character
               JOIN account               ON account.account_id = game_character.account_id
               JOIN character_vital       ON character_vital.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
               JOIN character_social      ON character_social.character_id = game_character.character_id
              WHERE account.is_locked = 0
                AND game_character.character_id <> :character_id
                AND game_character.level BETWEEN :level_floor AND :level_ceiling
                AND game_character.location_code = 0
                AND character_vital.is_alive = 1
                AND (
                        character_progression.game_age_day > :immunity_day
                     OR character_progression.dragon_kill_count > 0
                     OR character_social.pvp_immunity_lost > 0
                     OR character_progression.experience > :immunity_experience
                    )
                AND (
                        account.is_logged_in = 0
                     OR account.last_seen_at IS NULL
                     OR account.last_seen_at < datetime(\'now\', :login_timeout)
                    )
              ORDER BY game_character.level DESC',
        );
        $statement->execute([
            'character_id' => $characterId,
            'level_floor' => (int) $attacker['level'] - self::LEVEL_RANGE_BELOW,
            'level_ceiling' => (int) $attacker['level'] + self::LEVEL_RANGE_ABOVE,
            'immunity_day' => $immunityDay,
            'immunity_experience' => $immunityExperience,
            'login_timeout' => \sprintf('-%d seconds', $loginTimeout),
        ]);

        $targetList = [];

        foreach ($statement->fetchAll() as $row) {
            $isRecentlyAttacked = $row['pvp_flag_at'] !== null
                && \strtotime((string) $row['pvp_flag_at']) > \time() - $pvpTimeout;

            $targetList[] = [
                'character_id' => (int) $row['character_id'],
                'display_name' => (string) $row['display_name'],
                'level' => (int) $row['level'],
                'player_kill_count' => (int) $row['player_kill_count'],
                'attackable' => !$isRecentlyAttacked,
            ];
        }

        return [
            'player_fight' => (int) $attacker['player_fight'],
            'target_list' => $targetList,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attack(int $attackerCharacterId, int $defenderCharacterId): array
    {
        $attacker = $this->fetchCombatRow($attackerCharacterId);
        $defender = $this->fetchCombatRow($defenderCharacterId);

        if ($attackerCharacterId === $defenderCharacterId) {
            return ['attacked' => false, 'message_key' => 'pvp.error.self-attack'];
        }

        if ((int) $attacker['player_fight'] <= 0) {
            return ['attacked' => false, 'message_key' => 'pvp.error.no-fight-left'];
        }

        if (\abs((int) $attacker['level'] - (int) $defender['level']) > self::LEVEL_DIFFERENCE_LIMIT) {
            return ['attacked' => false, 'message_key' => 'pvp.error.level-difference'];
        }

        if ((int) $defender['is_alive'] !== 1) {
            return ['attacked' => false, 'message_key' => 'pvp.error.target-already-dead'];
        }

        $pvpTimeout = $this->gameSettingRepository->getInt('pvptimeout', 600);

        if (
            $defender['pvp_flag_at'] !== null
            && \strtotime((string) $defender['pvp_flag_at']) > \time() - $pvpTimeout
        ) {
            return ['attacked' => false, 'message_key' => 'pvp.error.target-recently-attacked'];
        }

        $this->markPvpFlag($defenderCharacterId);
        $this->consumePlayerFight($attackerCharacterId);
        $this->markImmunityLost($attackerCharacterId);

        $attackerCombatant = new BattleCombatant(
            (string) $attacker['display_name'],
            (int) $attacker['level'],
            (string) $attacker['weapon_name'],
            (int) $attacker['hit_point'],
            (int) $attacker['attack_point'],
            (int) $attacker['defence_point'],
        );

        $defenderCombatant = new BattleCombatant(
            (string) $defender['display_name'],
            (int) $defender['level'],
            (string) $defender['weapon_name'],
            (int) $defender['max_hit_point'],
            (int) $defender['attack_point'],
            (int) $defender['defence_point'],
        );

        $modifier = new BattleModifier();
        $roundLog = [];

        while ($attackerCombatant->isAlive() && $defenderCombatant->isAlive()) {
            $roundResult = $this->battleEngine->resolveRound(
                $attackerCombatant,
                $defenderCombatant,
                $modifier,
                true,
            );

            $roundLog[] = [
                'damage_to_defender' => $roundResult->damageToEnemy,
                'damage_to_attacker' => $roundResult->damageToPlayer,
                'attacker_hit_point' => $roundResult->playerHitPoint,
                'defender_hit_point' => $roundResult->enemyHitPoint,
            ];
        }

        $this->persistHitPoint($attackerCharacterId, $attackerCombatant->hitPoint);

        if ($attackerCombatant->isAlive()) {
            return $this->applyAttackerVictory(
                $attackerCharacterId,
                $defenderCharacterId,
                $attacker,
                $defender,
                $roundLog,
            );
        }

        return $this->applyAttackerDefeat(
            $attackerCharacterId,
            $defenderCharacterId,
            $attacker,
            $roundLog,
        );
    }

    /**
     * @param array<string, mixed>    $attacker
     * @param array<string, mixed>    $defender
     * @param list<array<string,int>> $roundLog
     *
     * @return array<string, mixed>
     */
    private function applyAttackerVictory(
        int $attackerCharacterId,
        int $defenderCharacterId,
        array $attacker,
        array $defender,
        array $roundLog,
    ): array {
        $attackerGainRate = $this->gameSettingRepository->getInt('pvpattgain', 10);
        $defenderLoseRate = $this->gameSettingRepository->getInt('pvpdeflose', 5);

        $baseExperience = (int) \round($attackerGainRate * (int) $defender['experience'] / 100);
        $levelDifference = (int) $defender['level'] - (int) $attacker['level'];
        $experienceBonus = (int) \round($baseExperience * (1 + 0.1 * $levelDifference)) - $baseExperience;
        $experienceGain = $baseExperience + $experienceBonus;

        $lootGold = \min((int) $defender['gold'], (int) $defender['gold']);
        $bountyGold = (int) $defender['bounty_on_self'];
        $defenderExperienceLoss = (int) \round((int) $defender['experience'] * $defenderLoseRate / 100);

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold = gold + :loot_gold + :bounty_gold
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'loot_gold' => $lootGold,
                    'bounty_gold' => $bountyGold,
                    'character_id' => $attackerCharacterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_progression
                        SET experience = experience + :experience_gain
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'experience_gain' => \max(0, $experienceGain),
                    'character_id' => $attackerCharacterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_social
                        SET player_kill_count = player_kill_count + 1
                      WHERE character_id = :character_id',
                )
                ->execute(['character_id' => $attackerCharacterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold           = MAX(0, gold - :loot_gold),
                            bounty_on_self = 0
                      WHERE character_id = :character_id',
                )
                ->execute(['loot_gold' => $lootGold, 'character_id' => $defenderCharacterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_progression
                        SET experience = MAX(0, experience - :experience_loss)
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'experience_loss' => $defenderExperienceLoss,
                    'character_id' => $defenderCharacterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_vital
                        SET hit_point      = 0,
                            is_alive       = 0,
                            slain_by_name  = :slain_by_name,
                            killed_in_area = \'fields\'
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'slain_by_name' => (string) $attacker['display_name'],
                    'character_id' => $defenderCharacterId,
                ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'attacked' => true,
            'victory' => true,
            'defender_display_name' => (string) $defender['display_name'],
            'gold_looted' => $lootGold,
            'bounty_collected' => $bountyGold,
            'experience_gained' => \max(0, $experienceGain),
            'experience_bonus' => $experienceBonus,
            'defender_experience_lost' => $defenderExperienceLoss,
            'round_log' => $roundLog,
        ];
    }

    /**
     * @param array<string, mixed>    $attacker
     * @param list<array<string,int>> $roundLog
     *
     * @return array<string, mixed>
     */
    private function applyAttackerDefeat(
        int $attackerCharacterId,
        int $defenderCharacterId,
        array $attacker,
        array $roundLog,
    ): array {
        $defenderGainRate = $this->gameSettingRepository->getInt('pvpdefgain', 10);
        $attackerLoseRate = $this->gameSettingRepository->getInt('pvpattlose', 15);

        $attackerGold = (int) $attacker['gold'];
        $defenderExperienceGain = (int) \round((int) $attacker['experience'] * $defenderGainRate / 100);
        $attackerRemainingExperience = (int) \round(
            (int) $attacker['experience'] * (100 - $attackerLoseRate) / 100,
        );

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold = gold + :gold_gain
                      WHERE character_id = :character_id',
                )
                ->execute(['gold_gain' => $attackerGold, 'character_id' => $defenderCharacterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_progression
                        SET experience = experience + :experience_gain
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'experience_gain' => $defenderExperienceGain,
                    'character_id' => $defenderCharacterId,
                ]);

            $this->connection
                ->prepare('UPDATE character_wealth SET gold = 0 WHERE character_id = :character_id')
                ->execute(['character_id' => $attackerCharacterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_progression
                        SET experience = :experience
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'experience' => $attackerRemainingExperience,
                    'character_id' => $attackerCharacterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_vital
                        SET hit_point      = 0,
                            is_alive       = 0,
                            slain_by_name  = :slain_by_name,
                            killed_in_area = \'fields\'
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'slain_by_name' => (string) $this->fetchCombatRow($defenderCharacterId)['display_name'],
                    'character_id' => $attackerCharacterId,
                ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'attacked' => true,
            'victory' => false,
            'gold_lost' => $attackerGold,
            'experience_lost_rate' => $attackerLoseRate,
            'defender_experience_gained' => $defenderExperienceGain,
            'round_log' => $roundLog,
        ];
    }

    private function markPvpFlag(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_social SET pvp_flag_at = datetime(\'now\') WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);
    }

    private function markImmunityLost(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_social SET pvp_immunity_lost = 1 WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);
    }

    private function consumePlayerFight(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET player_fight = MAX(0, player_fight - 1)
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);
    }

    private function persistHitPoint(int $characterId, int $hitPoint): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET hit_point = :hit_point,
                        is_alive  = CASE WHEN :hit_point_check > 0 THEN 1 ELSE 0 END
                  WHERE character_id = :character_id',
            )
            ->execute([
                'hit_point' => $hitPoint,
                'hit_point_check' => $hitPoint,
                'character_id' => $characterId,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCombatRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.character_id,
                    game_character.display_name,
                    game_character.level,
                    character_vital.hit_point,
                    character_vital.max_hit_point,
                    character_vital.is_alive,
                    character_combat_stat.attack_point,
                    character_combat_stat.defence_point,
                    character_progression.experience,
                    character_equipment.weapon_name,
                    character_wealth.gold,
                    character_wealth.bounty_on_self,
                    character_social.pvp_flag_at,
                    character_daily_allowance.player_fight
               FROM game_character
               JOIN character_vital           ON character_vital.character_id = game_character.character_id
               JOIN character_combat_stat     ON character_combat_stat.character_id = game_character.character_id
               JOIN character_progression     ON character_progression.character_id = game_character.character_id
               JOIN character_equipment       ON character_equipment.character_id = game_character.character_id
               JOIN character_wealth          ON character_wealth.character_id = game_character.character_id
               JOIN character_social          ON character_social.character_id = game_character.character_id
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
