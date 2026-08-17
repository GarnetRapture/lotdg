<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class DragonService
{
    public const int REQUIRED_LEVEL = 15;

    private const string DRAGON_NAME = 'The Green Dragon';

    private const int DRAGON_LEVEL = 18;

    private const string DRAGON_WEAPON_NAME = 'Great Flaming Maw';

    private const int DRAGON_BASE_ATTACK = 45;

    private const int DRAGON_BASE_DEFENSE = 25;

    private const int DRAGON_BASE_HEALTH = 300;

    private const int HIT_POINT_BUFF_BASELINE = 150;

    private const int HIT_POINT_PER_BUFF_POINT = 5;

    private const float BUFF_POINT_SCALE = 0.75;

    private const int CHARM_GAIN_PER_DRAGON_KILL = 5;

    private const int STARTING_GOLD_CAP_MULTIPLIER = 6;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly BattleEngine $battleEngine = new BattleEngine(),
        private readonly RankTitleTable $rankTitleTable = new RankTitleTable(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function enterLair(int $characterId): array
    {
        $row = $this->fetchDragonRow($characterId);

        if ((int) $row['level'] < self::REQUIRED_LEVEL) {
            return [
                'entered' => false,
                'message_key' => 'dragon.error.level-too-low',
                'required_level' => self::REQUIRED_LEVEL,
            ];
        }

        $dragonState = $this->buildDragonState(
            (int) $row['dragon_kill_count'],
            (int) $row['max_hit_point'],
            (string) $row['dragon_point_json'],
        );

        $this->storeEnemyState($characterId, $dragonState);
        $this->markDragonSeen($characterId);

        return ['entered' => true, 'dragon' => $dragonState];
    }

    /**
     * @return array<string, mixed>
     */
    public function fightRound(int $characterId): array
    {
        $row = $this->fetchDragonRow($characterId);
        $dragonState = $this->loadEnemyState($characterId);

        if ($dragonState === [] || ($dragonState['is_dragon'] ?? false) !== true) {
            return ['fought' => false, 'message_key' => 'dragon.error.not-in-lair'];
        }

        $player = new BattleCombatant(
            (string) $row['display_name'],
            (int) $row['level'],
            (string) $row['weapon_name'],
            (int) $row['hit_point'],
            (int) $row['attack_point'],
            (int) $row['defence_point'],
        );

        $dragon = new BattleCombatant(
            self::DRAGON_NAME,
            self::DRAGON_LEVEL,
            self::DRAGON_WEAPON_NAME,
            (int) $dragonState['health'],
            (int) $dragonState['attack_point'],
            (int) $dragonState['defense_point'],
        );

        $buffListMap = \json_decode((string) $row['buff_list_json'], true);
        $modifier = BattleModifier::fromBuffList(\is_array($buffListMap) ? $buffListMap : []);

        $roundResult = $this->battleEngine->resolveRound($player, $dragon, $modifier);

        $dragonState['health'] = $dragon->hitPoint;

        if ($roundResult->damageToPlayer > 0) {
            $dragonState['did_damage'] = true;
        }

        $this->persistPlayerHitPoint($characterId, $player->hitPoint);

        if ($roundResult->isPlayerVictorious()) {
            $isFlawless = ($dragonState['did_damage'] ?? false) !== true;
            $this->storeEnemyState($characterId, []);

            return [
                'fought' => true,
                'victory' => true,
                'flawless' => $isFlawless,
                'damage_to_dragon' => $roundResult->damageToEnemy,
                'damage_to_player' => $roundResult->damageToPlayer,
                'player_hit_point' => $roundResult->playerHitPoint,
                'dragon_hit_point' => $roundResult->enemyHitPoint,
            ];
        }

        if ($roundResult->isPlayerDefeated()) {
            $this->applyDefeat($characterId);
            $this->storeEnemyState($characterId, []);

            return [
                'fought' => true,
                'victory' => false,
                'defeat' => true,
                'damage_to_dragon' => $roundResult->damageToEnemy,
                'damage_to_player' => $roundResult->damageToPlayer,
                'player_hit_point' => 0,
                'dragon_hit_point' => $roundResult->enemyHitPoint,
            ];
        }

        $this->storeEnemyState($characterId, $dragonState);

        return [
            'fought' => true,
            'victory' => false,
            'defeat' => false,
            'damage_to_dragon' => $roundResult->damageToEnemy,
            'damage_to_player' => $roundResult->damageToPlayer,
            'critical_attack' => $roundResult->isCriticalAttack,
            'player_hit_point' => $roundResult->playerHitPoint,
            'dragon_hit_point' => $roundResult->enemyHitPoint,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function completeRebirth(int $characterId, bool $isFlawless): array
    {
        $row = $this->fetchDragonRow($characterId);

        $dragonKillCount = (int) $row['dragon_kill_count'] + 1;
        $sexCode = (int) $row['sex_code'];
        $newTitle = $this->rankTitleTable->resolve($dragonKillCount, $sexCode);
        $baseName = $this->stripTitle((string) $row['display_name'], (string) $row['rank_title']);
        $startingGold = $this->gameSettingRepository->getInt('newplayerstartgold', 50);

        $gold = $startingGold + $startingGold * $dragonKillCount;
        $gemGain = 0;
        $goldCap = self::STARTING_GOLD_CAP_MULTIPLIER * $startingGold;

        if ($gold > $goldCap) {
            $gold = $goldCap;
            $gemGain = \max(0, $dragonKillCount - 5);
        }

        if ($isFlawless) {
            $gold += 3 * $startingGold;
            ++$gemGain;
        }

        $retainedMaxHitPoint = (int) $row['max_hit_point'];
        $dragonPointList = $this->decodeDragonPointList((string) $row['dragon_point_json']);
        $attackFromDragonPoint = \count(
            \array_filter($dragonPointList, static fn (mixed $point): bool => $point === 'at'),
        );
        $defenceFromDragonPoint = \count(
            \array_filter($dragonPointList, static fn (mixed $point): bool => $point === 'de'),
        );

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE game_character
                        SET level         = 1,
                            display_name  = :display_name,
                            rank_title    = :rank_title,
                            location_code = 0
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'display_name' => \trim($newTitle . ' ' . $baseName),
                    'rank_title' => $newTitle,
                    'character_id' => $characterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_vital
                        SET hit_point     = :hit_point,
                            max_hit_point = :max_hit_point,
                            is_alive      = 1,
                            spirit_level  = 0
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'hit_point' => $retainedMaxHitPoint,
                    'max_hit_point' => $retainedMaxHitPoint,
                    'character_id' => $characterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_combat_stat
                        SET attack_point       = 1 + :attack_bonus,
                            defence_point      = 1 + :defence_bonus,
                            buff_list_json     = \'{}\',
                            buff_backup_json   = \'{}\',
                            current_enemy_json = \'{}\'
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'attack_bonus' => $attackFromDragonPoint,
                    'defence_bonus' => $defenceFromDragonPoint,
                    'character_id' => $characterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_progression
                        SET experience        = 0,
                            dragon_kill_count = :dragon_kill_count,
                            dragon_point_json = \'[]\',
                            dragon_age_day    = game_age_day,
                            best_dragon_age_day = CASE
                                WHEN best_dragon_age_day = 0 OR game_age_day < best_dragon_age_day
                                THEN game_age_day
                                ELSE best_dragon_age_day
                            END,
                            has_seen_dragon   = 0,
                            seen_master_level = 0
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'dragon_kill_count' => $dragonKillCount,
                    'character_id' => $characterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_equipment
                        SET weapon_id     = NULL,
                            weapon_name   = \'Fists\',
                            weapon_value  = 0,
                            weapon_damage = 0,
                            armor_id      = NULL,
                            armor_name    = \'T-Shirt\',
                            armor_value   = 0,
                            armor_defense = 0
                      WHERE character_id = :character_id',
                )
                ->execute(['character_id' => $characterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold         = :gold,
                            gold_in_bank = 0,
                            gem          = gem + :gem_gain
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'gold' => $gold,
                    'gem_gain' => $gemGain,
                    'character_id' => $characterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_social
                        SET charm = charm + :charm_gain
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'charm_gain' => self::CHARM_GAIN_PER_DRAGON_KILL,
                    'character_id' => $characterId,
                ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'rebirth' => true,
            'dragon_kill_count' => $dragonKillCount,
            'new_title' => $newTitle,
            'display_name' => \trim($newTitle . ' ' . $baseName),
            'gold' => $gold,
            'gem_gain' => $gemGain,
            'retained_max_hit_point' => $retainedMaxHitPoint,
            'charm_gain' => self::CHARM_GAIN_PER_DRAGON_KILL,
            'flawless' => $isFlawless,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDragonState(
        int $dragonKillCount,
        int $maxHitPoint,
        string $dragonPointJson,
    ): array {
        $attackPoint = self::DRAGON_BASE_ATTACK;
        $defensePoint = self::DRAGON_BASE_DEFENSE;
        $health = self::DRAGON_BASE_HEALTH;

        $killFlux = $dragonKillCount * 2;
        $attackFlux = $killFlux > 0 ? \random_int(0, $killFlux) : 0;
        $defenceFlux = $killFlux - $attackFlux > 0 ? \random_int(0, $killFlux - $attackFlux) : 0;
        $healthFlux = ($killFlux - ($attackFlux + $defenceFlux)) * 5;

        $attackPoint += $attackFlux;
        $defensePoint += $defenceFlux;
        $health += $healthFlux;

        $dragonPointList = $this->decodeDragonPointList($dragonPointJson);
        $buffPoint = \count(
            \array_filter(
                $dragonPointList,
                static fn (mixed $point): bool => $point === 'at' || $point === 'de',
            ),
        );
        $buffPoint += (int) (($maxHitPoint - self::HIT_POINT_BUFF_BASELINE) / self::HIT_POINT_PER_BUFF_POINT);
        $buffPoint = (int) \round($buffPoint * self::BUFF_POINT_SCALE);

        if ($buffPoint > 0) {
            $buffAttackFlux = \random_int(0, $buffPoint);
            $buffDefenceFlux = $buffPoint - $buffAttackFlux > 0
                ? \random_int(0, $buffPoint - $buffAttackFlux)
                : 0;

            $attackPoint += $buffAttackFlux;
            $defensePoint += $buffDefenceFlux;
            $health += ($buffPoint - ($buffAttackFlux + $buffDefenceFlux)) * 5;
        }

        return [
            'is_dragon' => true,
            'creature_name' => self::DRAGON_NAME,
            'creature_level' => self::DRAGON_LEVEL,
            'weapon_name' => self::DRAGON_WEAPON_NAME,
            'health' => $health,
            'max_health' => $health,
            'attack_point' => $attackPoint,
            'defense_point' => $defensePoint,
            'did_damage' => false,
        ];
    }

    /**
     * @return list<mixed>
     */
    private function decodeDragonPointList(string $dragonPointJson): array
    {
        $decoded = \json_decode($dragonPointJson, true);

        return \is_array($decoded) ? \array_values($decoded) : [];
    }

    private function stripTitle(string $displayName, string $rankTitle): string
    {
        if ($rankTitle === '') {
            return $displayName;
        }

        $position = \mb_strpos($displayName, $rankTitle);

        if ($position === false) {
            return $displayName;
        }

        return \trim(
            \mb_substr($displayName, 0, $position)
            . \mb_substr($displayName, $position + \mb_strlen($rankTitle)),
        );
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

    private function markDragonSeen(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_progression
                    SET has_seen_dragon = 1
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);
    }

    private function persistPlayerHitPoint(int $characterId, int $hitPoint): void
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

    private function applyDefeat(int $characterId): void
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
                        killed_in_area = \'dragon-lair\'
                  WHERE character_id = :character_id',
            )
            ->execute(['slain_by_name' => self::DRAGON_NAME, 'character_id' => $characterId]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchDragonRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.display_name,
                    game_character.rank_title,
                    game_character.level,
                    game_character.sex_code,
                    character_vital.hit_point,
                    character_vital.max_hit_point,
                    character_combat_stat.attack_point,
                    character_combat_stat.defence_point,
                    character_combat_stat.buff_list_json,
                    character_progression.dragon_kill_count,
                    character_progression.dragon_point_json,
                    character_progression.game_age_day,
                    character_equipment.weapon_name
               FROM game_character
               JOIN character_vital       ON character_vital.character_id = game_character.character_id
               JOIN character_combat_stat ON character_combat_stat.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
               JOIN character_equipment   ON character_equipment.character_id = game_character.character_id
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
