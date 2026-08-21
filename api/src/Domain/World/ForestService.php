<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\I18n\CatalogTranslator;
use Lotdg\Persistence\Repository\CreatureRepository;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class ForestService
{
    public const int LOCATION_FOREST = 0;

    public const int LOCATION_GRAVEYARD = 1;

    public const string SEARCH_TYPE_NORMAL = 'normal';

    public const string SEARCH_TYPE_SLUM = 'slum';

    public const string SEARCH_TYPE_THRILL = 'thrill';

    private const int SPECIAL_EVENT_DENOMINATOR = 7;

    private const int GEM_DROP_DENOMINATOR = 25;

    private const int RACE_DWARF = 4;

    private const float DWARF_GOLD_MULTIPLIER = 1.5;

    private const float DRAGON_POINT_SCALE = 0.25;

    private const int HIT_POINT_PER_DRAGON_POINT = 5;

    private const float DEFEAT_EXPERIENCE_RETENTION = 0.9;

    private const float DRUNKENNESS_DECAY = 0.9;

    public function __construct(
        private readonly PDO $connection,
        private readonly CreatureRepository $creatureRepository,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly CatalogTranslator $catalogTranslator,
        private readonly BattleEngine $battleEngine = new BattleEngine(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function beginEncounter(int $characterId, string $searchType, string $localeCode): array
    {
        $characterRow = $this->fetchCharacterCombatRow($characterId);

        if ($characterRow === null) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        if ((int) $characterRow['forest_turn'] <= 0) {
            return ['encountered' => false, 'message_key' => 'forest.error.no-turn-left'];
        }

        $this->decayDrunkenness($characterId, (int) $characterRow['drunkenness']);

        if (\random_int(0, self::SPECIAL_EVENT_DENOMINATOR - 1) === 0) {
            return ['encountered' => false, 'special_event' => true, 'message_key' => 'forest.special-event'];
        }

        $creatureLevel = $this->rollTargetLevel((int) $characterRow['level'], $searchType);
        $locationCode = (int) $characterRow['location_code'] === self::LOCATION_GRAVEYARD
            ? self::LOCATION_GRAVEYARD
            : self::LOCATION_FOREST;

        $creatureRow = $this->creatureRepository->findRandomByLevel($creatureLevel, $locationCode)
            ?? $this->creatureRepository->findNearestByLevel($creatureLevel, $locationCode);

        if ($creatureRow === null) {
            throw new LocalizedException('system-message', 'error.creature-catalog-empty');
        }

        $experienceReward = $this->applyExperienceFlux((int) $creatureRow['experience_reward']);
        $dragonPoint = $this->resolveDragonPointBonus(
            (string) $characterRow['dragon_point_json'],
            (int) $characterRow['max_hit_point'],
            (int) $characterRow['level'],
        );

        $attackFlux = $dragonPoint > 0 ? \random_int(0, $dragonPoint) : 0;
        $defenceFlux = $dragonPoint - $attackFlux > 0 ? \random_int(0, $dragonPoint - $attackFlux) : 0;
        $healthFlux = ($dragonPoint - ($attackFlux + $defenceFlux)) * self::HIT_POINT_PER_DRAGON_POINT;

        $goldReward = (int) $creatureRow['gold_reward'];

        if ((int) $characterRow['race_code'] === self::RACE_DWARF) {
            $goldReward = (int) \round($goldReward * self::DWARF_GOLD_MULTIPLIER);
        }

        $creatureId = (int) $creatureRow['creature_id'];

        $enemyState = [
            'creature_id' => $creatureId,
            'creature_name' => $this->translateCreatureField(
                $creatureId,
                'creature_name',
                (string) $creatureRow['creature_name'],
                $localeCode,
            ),
            'source_creature_name' => (string) $creatureRow['creature_name'],
            'creature_level' => (int) $creatureRow['creature_level'],
            'weapon_name' => $this->translateCreatureField(
                $creatureId,
                'weapon_name',
                (string) $creatureRow['weapon_name'],
                $localeCode,
            ),
            'health' => (int) $creatureRow['health'] + $healthFlux,
            'max_health' => (int) $creatureRow['health'] + $healthFlux,
            'attack_point' => (int) $creatureRow['attack_point'] + $attackFlux,
            'defense_point' => (int) $creatureRow['defense_point'] + $defenceFlux,
            'gold_reward' => $goldReward,
            'experience_reward' => $experienceReward,
            'victory_message' => $this->translateCreatureField(
                $creatureId,
                'victory_message',
                (string) $creatureRow['victory_message'],
                $localeCode,
            ),
            'defeat_message' => $this->translateCreatureField(
                $creatureId,
                'defeat_message',
                (string) $creatureRow['defeat_message'],
                $localeCode,
            ),
            'did_damage' => false,
            'player_start_hit_point' => (int) $characterRow['hit_point'],
        ];

        $this->storeEnemyState($characterId, $enemyState);
        $this->consumeForestTurn($characterId);

        return [
            'encountered' => true,
            'enemy_first_strike' => $this->battleEngine->rollsEnemyFirstStrike(),
            'enemy' => $enemyState,
        ];
    }

    private function translateCreatureField(
        int $creatureId,
        string $fieldCode,
        string $originalText,
        string $localeCode,
    ): string {
        return $this->catalogTranslator->translate(
            CatalogTranslator::ENTITY_CREATURE,
            $creatureId,
            $fieldCode,
            $originalText,
            $localeCode,
        );
    }

    private function rollTargetLevel(int $characterLevel, string $searchType): int
    {
        $positiveShift = 0;
        $negativeShift = 0;

        if (\random_int(0, 2) === 1) {
            $positiveShift = \random_int(1, 5) === 1 ? 1 : 0;
            $negativeShift = \random_int(1, 3) === 1 ? 1 : 0;
        }

        if ($searchType === self::SEARCH_TYPE_SLUM) {
            ++$negativeShift;
        }

        if ($searchType === self::SEARCH_TYPE_THRILL) {
            ++$positiveShift;
        }

        return \max(1, $characterLevel + $positiveShift - $negativeShift);
    }

    private function applyExperienceFlux(int $experienceReward): int
    {
        $flux = (int) \round($experienceReward / 10);

        if ($flux <= 0) {
            return $experienceReward;
        }

        return \max(0, $experienceReward + \random_int(-$flux, $flux));
    }

    private function resolveDragonPointBonus(
        string $dragonPointJson,
        int $maxHitPoint,
        int $level,
    ): int {
        $decoded = \json_decode($dragonPointJson, true);
        $pointList = \is_array($decoded) ? $decoded : [];

        $dragonPoint = \count(
            \array_filter(
                $pointList,
                static fn (mixed $point): bool => $point === 'at' || $point === 'de',
            ),
        );

        $dragonPoint += (int) (($maxHitPoint - $level * 10) / self::HIT_POINT_PER_DRAGON_POINT);

        return \max(0, (int) \round($dragonPoint * self::DRAGON_POINT_SCALE));
    }

    private function decayDrunkenness(int $characterId, int $drunkenness): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET drunkenness = :drunkenness
                  WHERE character_id = :character_id',
            )
            ->execute([
                'drunkenness' => (int) \round($drunkenness * self::DRUNKENNESS_DECAY),
                'character_id' => $characterId,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fightRound(int $characterId): array
    {
        $characterRow = $this->fetchCharacterCombatRow($characterId);

        if ($characterRow === null) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        $enemyState = $this->loadEnemyState($characterId);

        if ($enemyState === []) {
            return ['fought' => false, 'message_key' => 'battle.error.no-enemy'];
        }

        $player = new BattleCombatant(
            (string) $characterRow['display_name'],
            (int) $characterRow['level'],
            (string) $characterRow['weapon_name'],
            (int) $characterRow['hit_point'],
            (int) $characterRow['attack_point'],
            (int) $characterRow['defence_point'],
        );

        $enemy = new BattleCombatant(
            (string) $enemyState['creature_name'],
            (int) $enemyState['creature_level'],
            (string) $enemyState['weapon_name'],
            (int) $enemyState['health'],
            (int) $enemyState['attack_point'],
            (int) $enemyState['defense_point'],
        );

        $buffListMap = \json_decode((string) $characterRow['buff_list_json'], true);
        $modifier = BattleModifier::fromBuffList(\is_array($buffListMap) ? $buffListMap : []);

        $roundResult = $this->battleEngine->resolveRound($player, $enemy, $modifier);

        $enemyState['health'] = $enemy->hitPoint;

        if ($roundResult->damageToPlayer > 0) {
            $enemyState['did_damage'] = true;
        }

        $this->persistPlayerHitPoint($characterId, $player->hitPoint);

        $reward = null;

        if ($roundResult->isPlayerVictorious()) {
            $reward = $this->grantVictoryReward(
                $characterId,
                $enemyState,
                (int) $characterRow['level'],
            );
            $this->storeEnemyState($characterId, []);
        } elseif ($roundResult->isPlayerDefeated()) {
            $this->applyDefeatPenalty(
                $characterId,
                (string) ($enemyState['source_creature_name'] ?? $enemyState['creature_name']),
                (int) $characterRow['experience'],
            );
            $this->storeEnemyState($characterId, []);
        } else {
            $this->storeEnemyState($characterId, $enemyState);
        }

        return [
            'fought' => true,
            'damage_to_enemy' => $roundResult->damageToEnemy,
            'damage_to_player' => $roundResult->damageToPlayer,
            'critical_attack' => $roundResult->isCriticalAttack,
            'player_hit_point' => $roundResult->playerHitPoint,
            'enemy_hit_point' => $roundResult->enemyHitPoint,
            'victory' => $roundResult->isPlayerVictorious(),
            'defeat' => $roundResult->isPlayerDefeated(),
            'reward' => $reward,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchCharacterCombatRow(int $characterId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.character_id,
                    game_character.display_name,
                    game_character.level,
                    game_character.location_code,
                    game_character.race_code,
                    character_vital.hit_point,
                    character_vital.max_hit_point,
                    character_vital.is_alive,
                    character_combat_stat.attack_point,
                    character_combat_stat.defence_point,
                    character_combat_stat.buff_list_json,
                    character_combat_stat.current_enemy_json,
                    character_progression.experience,
                    character_progression.dragon_point_json,
                    character_equipment.weapon_name,
                    character_daily_allowance.forest_turn,
                    character_daily_allowance.drunkenness
               FROM game_character
               JOIN character_vital           ON character_vital.character_id = game_character.character_id
               JOIN character_combat_stat     ON character_combat_stat.character_id = game_character.character_id
               JOIN character_progression     ON character_progression.character_id = game_character.character_id
               JOIN character_equipment       ON character_equipment.character_id = game_character.character_id
               JOIN character_daily_allowance ON character_daily_allowance.character_id = game_character.character_id
              WHERE game_character.character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
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

    private function consumeForestTurn(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn = MAX(0, forest_turn - 1)
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

    /**
     * @param array<string, mixed> $enemyState
     *
     * @return array{gold: int, experience: int, experience_bonus: int, gem: int, turn_refund: int}
     */
    private function grantVictoryReward(int $characterId, array $enemyState, int $characterLevel): array
    {
        $maximumGold = (int) ($enemyState['gold_reward'] ?? 0);

        if ($this->gameSettingRepository->getBool('dropmingold', false)) {
            $goldReward = $maximumGold > 0
                ? \random_int((int) ($maximumGold / 4), (int) (3 * $maximumGold / 4))
                : 0;
        } else {
            $goldReward = $maximumGold > 0 ? \random_int(0, $maximumGold) : 0;
        }

        $baseExperience = (int) ($enemyState['experience_reward'] ?? 0);
        $creatureLevel = (int) ($enemyState['creature_level'] ?? $characterLevel);
        $experienceBonus = (int) \round(
            $baseExperience * (1 + 0.25 * ($creatureLevel - $characterLevel)),
        ) - $baseExperience;
        $experienceReward = \max(0, $baseExperience + $experienceBonus);

        $gemReward = \random_int(1, self::GEM_DROP_DENOMINATOR) === 1 ? 1 : 0;

        $turnRefund = 0;

        if (($enemyState['did_damage'] ?? false) !== true) {
            $lowSlumLevel = $this->gameSettingRepository->getInt('lowslumlevel', 4);
            $turnRefund = ($characterLevel >= $lowSlumLevel || $characterLevel <= $creatureLevel) ? 2 : 1;
        }

        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gold = gold + :gold,
                        gem  = gem + :gem
                  WHERE character_id = :character_id',
            )
            ->execute([
                'gold' => $goldReward,
                'gem' => $gemReward,
                'character_id' => $characterId,
            ]);

        $this->connection
            ->prepare(
                'UPDATE character_progression
                    SET experience = experience + :experience
                  WHERE character_id = :character_id',
            )
            ->execute(['experience' => $experienceReward, 'character_id' => $characterId]);

        if ($turnRefund > 0) {
            $this->connection
                ->prepare(
                    'UPDATE character_daily_allowance
                        SET forest_turn = forest_turn + :turn_refund
                      WHERE character_id = :character_id',
                )
                ->execute(['turn_refund' => $turnRefund, 'character_id' => $characterId]);
        }

        return [
            'gold' => $goldReward,
            'experience' => $experienceReward,
            'experience_bonus' => $experienceBonus,
            'gem' => $gemReward,
            'turn_refund' => $turnRefund,
        ];
    }

    /**/
    private function applyDefeatPenalty(int $characterId, string $slainByName, int $experience): void
    {
        $this->connection
            ->prepare('UPDATE character_wealth SET gold = 0 WHERE character_id = :character_id')
            ->execute(['character_id' => $characterId]);

        $this->connection
            ->prepare(
                'UPDATE character_progression
                    SET experience = :experience
                  WHERE character_id = :character_id',
            )
            ->execute([
                'experience' => (int) \round($experience * self::DEFEAT_EXPERIENCE_RETENTION),
                'character_id' => $characterId,
            ]);

        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET hit_point     = 0,
                        is_alive      = 0,
                        slain_by_name = :slain_by_name,
                        killed_in_area = \'forest\'
                  WHERE character_id = :character_id',
            )
            ->execute(['slain_by_name' => $slainByName, 'character_id' => $characterId]);
    }
}
