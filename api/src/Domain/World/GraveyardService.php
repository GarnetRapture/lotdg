<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Persistence\Repository\CreatureRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class GraveyardService
{
    public const int RESURRECTION_FAVOR_COST = 100;

    public const int HAUNT_FAVOR_COST = 50;

    private const int SOUL_POINT_BASE = 50;

    private const int SOUL_POINT_PER_LEVEL = 5;

    private const int LOW_LEVEL_SHIFT_THRESHOLD = 5;

    private const float UNDEAD_DEFENSE_SCALE = 0.7;

    private const int SPIRIT_COMBAT_BASE = 10;

    private const float SPIRIT_COMBAT_PER_LEVEL = 1.5;

    public function __construct(
        private readonly PDO $connection,
        private readonly CreatureRepository $creatureRepository,
        private readonly BattleEngine $battleEngine = new BattleEngine(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId): array
    {
        $row = $this->fetchGraveyardRow($characterId);
        $maximumSoulPoint = $this->maximumSoulPoint((int) $row['level']);

        return [
            'is_alive' => (int) $row['is_alive'] === 1,
            'soul_point' => (int) $row['soul_point'],
            'maximum_soul_point' => $maximumSoulPoint,
            'grave_fight' => (int) $row['grave_fight'],
            'death_power' => (int) $row['death_power'],
            'restore_favor_cost' => $this->restoreFavorCost(
                (int) $row['soul_point'],
                $maximumSoulPoint,
            ),
            'can_resurrect' => (int) $row['death_power'] >= self::RESURRECTION_FAVOR_COST
                && (int) $row['soul_point'] > 0
                && (int) $row['is_alive'] === 0,
            'can_haunt' => (int) $row['death_power'] >= self::HAUNT_FAVOR_COST,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchUndead(int $characterId): array
    {
        $row = $this->fetchGraveyardRow($characterId);

        if ((int) $row['is_alive'] === 1) {
            return ['encountered' => false, 'message_key' => 'graveyard.error.still-alive'];
        }

        if ((int) $row['grave_fight'] <= 0) {
            return ['encountered' => false, 'message_key' => 'graveyard.error.no-grave-fight-left'];
        }

        $level = (int) $row['level'];
        $creatureRow = $this->creatureRepository->findRandomByLevel($level, ForestService::LOCATION_GRAVEYARD)
            ?? $this->creatureRepository->findNearestByLevel($level, ForestService::LOCATION_GRAVEYARD);

        if ($creatureRow === null) {
            throw new LocalizedException('system-message', 'error.creature-catalog-empty');
        }

        $shift = $level < self::LOW_LEVEL_SHIFT_THRESHOLD ? -1 : 0;
        $attackPoint = 9 + $shift + (int) (($level - 1) * 1.5);
        $defensePoint = (int) ((9 + $shift + (($level - 1) * 1.5)) * self::UNDEAD_DEFENSE_SCALE);
        $health = $this->maximumSoulPoint($level);
        $favorReward = \random_int(
            10 + (int) \round($level / 3),
            20 + (int) \round($level / 3),
        );

        $enemyState = [
            'is_undead' => true,
            'creature_id' => (int) $creatureRow['creature_id'],
            'creature_name' => (string) $creatureRow['creature_name'],
            'creature_level' => $level,
            'weapon_name' => (string) $creatureRow['weapon_name'],
            'health' => $health,
            'max_health' => $health,
            'attack_point' => $attackPoint,
            'defense_point' => $defensePoint,
            'favor_reward' => $favorReward,
            'victory_message' => (string) $creatureRow['victory_message'],
        ];

        $this->consumeGraveFight($characterId);
        $this->storeEnemyState($characterId, $enemyState);

        return ['encountered' => true, 'enemy' => $enemyState];
    }

    /**
     * @return array<string, mixed>
     */
    public function fightRound(int $characterId): array
    {
        $row = $this->fetchGraveyardRow($characterId);
        $enemyState = $this->loadEnemyState($characterId);

        if ($enemyState === [] || ($enemyState['is_undead'] ?? false) !== true) {
            return ['fought' => false, 'message_key' => 'graveyard.error.no-enemy'];
        }

        $level = (int) $row['level'];
        $spiritCombatPoint = self::SPIRIT_COMBAT_BASE
            + (int) \round(($level - 1) * self::SPIRIT_COMBAT_PER_LEVEL);

        $spirit = new BattleCombatant(
            (string) $row['display_name'],
            $level,
            'Spirit',
            (int) $row['soul_point'],
            $spiritCombatPoint,
            $spiritCombatPoint,
        );

        $undead = new BattleCombatant(
            (string) $enemyState['creature_name'],
            (int) $enemyState['creature_level'],
            (string) $enemyState['weapon_name'],
            (int) $enemyState['health'],
            (int) $enemyState['attack_point'],
            (int) $enemyState['defense_point'],
        );

        $roundResult = $this->battleEngine->resolveRound($spirit, $undead, new BattleModifier());

        $enemyState['health'] = $undead->hitPoint;
        $this->persistSoulPoint($characterId, \max(0, $spirit->hitPoint));

        if ($roundResult->isPlayerVictorious()) {
            $favorReward = (int) $enemyState['favor_reward'];
            $this->grantFavor($characterId, $favorReward);
            $this->storeEnemyState($characterId, []);

            return [
                'fought' => true,
                'victory' => true,
                'favor_gained' => $favorReward,
                'soul_point' => \max(0, $spirit->hitPoint),
                'enemy_name' => (string) $enemyState['creature_name'],
                'enemy_message' => (string) $enemyState['victory_message'],
            ];
        }

        if ($roundResult->isPlayerDefeated()) {
            $this->exhaustGraveFight($characterId);
            $this->storeEnemyState($characterId, []);

            return [
                'fought' => true,
                'victory' => false,
                'defeat' => true,
                'soul_point' => 0,
                'enemy_name' => (string) $enemyState['creature_name'],
            ];
        }

        $this->storeEnemyState($characterId, $enemyState);

        return [
            'fought' => true,
            'victory' => false,
            'defeat' => false,
            'damage_to_enemy' => $roundResult->damageToEnemy,
            'damage_to_spirit' => $roundResult->damageToPlayer,
            'soul_point' => \max(0, $spirit->hitPoint),
            'enemy_hit_point' => $roundResult->enemyHitPoint,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resurrect(int $characterId): array
    {
        $row = $this->fetchGraveyardRow($characterId);

        if ((int) $row['is_alive'] === 1) {
            return ['resurrected' => false, 'message_key' => 'graveyard.error.already-alive'];
        }

        if ((int) $row['soul_point'] <= 0) {
            return ['resurrected' => false, 'message_key' => 'graveyard.error.soul-destroyed'];
        }

        if ((int) $row['death_power'] < self::RESURRECTION_FAVOR_COST) {
            return [
                'resurrected' => false,
                'message_key' => 'graveyard.error.not-enough-favor',
                'required_favor' => self::RESURRECTION_FAVOR_COST,
            ];
        }

        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET is_alive           = 1,
                        hit_point          = max_hit_point,
                        death_power        = death_power - :favor_cost,
                        resurrection_count = resurrection_count + 1,
                        slain_by_name      = \'\',
                        killed_in_area     = \'\'
                  WHERE character_id = :character_id',
            )
            ->execute([
                'favor_cost' => self::RESURRECTION_FAVOR_COST,
                'character_id' => $characterId,
            ]);

        return [
            'resurrected' => true,
            'favor_spent' => self::RESURRECTION_FAVOR_COST,
            'hit_point' => (int) $row['max_hit_point'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function restoreSoul(int $characterId): array
    {
        $row = $this->fetchGraveyardRow($characterId);
        $maximumSoulPoint = $this->maximumSoulPoint((int) $row['level']);

        if ((int) $row['soul_point'] >= $maximumSoulPoint) {
            return ['restored' => false, 'message_key' => 'graveyard.error.soul-already-full'];
        }

        $favorCost = $this->restoreFavorCost((int) $row['soul_point'], $maximumSoulPoint);

        if ((int) $row['death_power'] < $favorCost) {
            return [
                'restored' => false,
                'message_key' => 'graveyard.error.not-enough-favor',
                'required_favor' => $favorCost,
            ];
        }

        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET soul_point  = :soul_point,
                        death_power = death_power - :favor_cost
                  WHERE character_id = :character_id',
            )
            ->execute([
                'soul_point' => $maximumSoulPoint,
                'favor_cost' => $favorCost,
                'character_id' => $characterId,
            ]);

        return [
            'restored' => true,
            'soul_point' => $maximumSoulPoint,
            'favor_spent' => $favorCost,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function haunt(int $characterId, int $targetCharacterId): array
    {
        $row = $this->fetchGraveyardRow($characterId);

        if ((int) $row['death_power'] < self::HAUNT_FAVOR_COST) {
            return [
                'haunted' => false,
                'message_key' => 'graveyard.error.not-enough-favor',
                'required_favor' => self::HAUNT_FAVOR_COST,
            ];
        }

        $targetRow = $this->fetchHauntTargetRow($targetCharacterId);

        if ($targetRow === null) {
            return ['haunted' => false, 'message_key' => 'graveyard.error.target-not-found'];
        }

        if ((string) $targetRow['haunted_by_name'] !== '') {
            return ['haunted' => false, 'message_key' => 'graveyard.error.target-already-haunted'];
        }

        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET death_power = death_power - :favor_cost
                  WHERE character_id = :character_id',
            )
            ->execute([
                'favor_cost' => self::HAUNT_FAVOR_COST,
                'character_id' => $characterId,
            ]);

        $targetRoll = \random_int(0, (int) $targetRow['level']);
        $selfRoll = \random_int(0, (int) $row['level']);

        if ($selfRoll <= $targetRoll) {
            return [
                'haunted' => false,
                'message_key' => 'graveyard.result.haunt-failed',
                'favor_spent' => self::HAUNT_FAVOR_COST,
                'target_display_name' => (string) $targetRow['display_name'],
            ];
        }

        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET haunted_by_name = :haunted_by_name
                  WHERE character_id = :character_id',
            )
            ->execute([
                'haunted_by_name' => (string) $row['display_name'],
                'character_id' => $targetCharacterId,
            ]);

        return [
            'haunted' => true,
            'favor_spent' => self::HAUNT_FAVOR_COST,
            'target_display_name' => (string) $targetRow['display_name'],
        ];
    }

    private function maximumSoulPoint(int $level): int
    {
        return $level * self::SOUL_POINT_PER_LEVEL + self::SOUL_POINT_BASE;
    }

    private function restoreFavorCost(int $soulPoint, int $maximumSoulPoint): int
    {
        if ($maximumSoulPoint <= 0) {
            return 0;
        }

        return (int) \round(10 * ($maximumSoulPoint - $soulPoint) / $maximumSoulPoint);
    }

    private function consumeGraveFight(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET grave_fight = MAX(0, grave_fight - 1)
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);
    }

    private function exhaustGraveFight(int $characterId): void
    {
        $this->connection
            ->prepare('UPDATE character_vital SET grave_fight = 0 WHERE character_id = :character_id')
            ->execute(['character_id' => $characterId]);
    }

    private function persistSoulPoint(int $characterId, int $soulPoint): void
    {
        $this->connection
            ->prepare('UPDATE character_vital SET soul_point = :soul_point WHERE character_id = :character_id')
            ->execute(['soul_point' => $soulPoint, 'character_id' => $characterId]);
    }

    private function grantFavor(int $characterId, int $favorReward): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET death_power = death_power + :favor_reward
                  WHERE character_id = :character_id',
            )
            ->execute(['favor_reward' => $favorReward, 'character_id' => $characterId]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadEnemyState(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT current_enemy_json FROM character_combat_stat WHERE character_id = :character_id',
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
     * @param array<string, mixed> $enemyState
     */
    private function storeEnemyState(int $characterId, array $enemyState): void
    {
        $encoded = \json_encode($enemyState, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        $this->connection
            ->prepare(
                'UPDATE character_combat_stat
                    SET current_enemy_json = :current_enemy_json
                  WHERE character_id = :character_id',
            )
            ->execute([
                'current_enemy_json' => $encoded === false ? '{}' : $encoded,
                'character_id' => $characterId,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchGraveyardRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.display_name,
                    game_character.level,
                    character_vital.is_alive,
                    character_vital.soul_point,
                    character_vital.grave_fight,
                    character_vital.death_power,
                    character_vital.max_hit_point
               FROM game_character
               JOIN character_vital ON character_vital.character_id = game_character.character_id
              WHERE game_character.character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchHauntTargetRow(int $targetCharacterId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.display_name,
                    game_character.level,
                    character_vital.haunted_by_name
               FROM game_character
               JOIN character_vital ON character_vital.character_id = game_character.character_id
              WHERE game_character.character_id = :character_id',
        );
        $statement->execute(['character_id' => $targetCharacterId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
