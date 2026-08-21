<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Domain\Account\LevelProgressionTable;
use Lotdg\I18n\CatalogTranslator;
use Lotdg\Persistence\Repository\CreatureRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class TrainingService
{
    public function __construct(
        private readonly PDO $connection,
        private readonly CreatureRepository $creatureRepository,
        private readonly CatalogTranslator $catalogTranslator,
        private readonly BattleEngine $battleEngine = new BattleEngine(),
        private readonly LevelProgressionTable $levelProgressionTable = new LevelProgressionTable(),
    ) {
    }

    /**
     * @param array<string, mixed> $masterRow
     */
    private function translateMasterField(
        array $masterRow,
        string $fieldCode,
        string $localeCode,
    ): string {
        return $this->catalogTranslator->translate(
            CatalogTranslator::ENTITY_TRAINING_MASTER,
            (int) $masterRow['master_id'],
            $fieldCode,
            (string) $masterRow[$fieldCode],
            $localeCode,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId, string $localeCode): array
    {
        $characterRow = $this->fetchTrainingRow($characterId);
        $level = (int) $characterRow['level'];
        $dragonKillCount = (int) $characterRow['dragon_kill_count'];

        if (!$this->levelProgressionTable->hasMaster($level)) {
            return ['has_master' => false, 'level' => $level];
        }

        $masterRow = $this->creatureRepository->findTrainingMasterByLevel($level, $dragonKillCount);

        if ($masterRow === null) {
            return ['has_master' => false, 'level' => $level];
        }

        $requiredExperience = $this->levelProgressionTable->requiredExperience($level, $dragonKillCount);
        $experience = (int) $characterRow['experience'];

        return [
            'has_master' => true,
            'level' => $level,
            'master_name' => $this->translateMasterField($masterRow, 'master_name', $localeCode),
            'master_weapon_name' => $this->translateMasterField($masterRow, 'weapon_name', $localeCode),
            'required_experience' => $requiredExperience,
            'current_experience' => $experience,
            'missing_experience' => \max(0, $requiredExperience - $experience),
            'can_challenge' => $experience >= $requiredExperience
                && (int) $characterRow['seen_master_level'] === 0,
            'already_challenged_today' => (int) $characterRow['seen_master_level'] !== 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function challenge(int $characterId, string $localeCode): array
    {
        $characterRow = $this->fetchTrainingRow($characterId);
        $level = (int) $characterRow['level'];
        $dragonKillCount = (int) $characterRow['dragon_kill_count'];

        if ((int) $characterRow['seen_master_level'] !== 0) {
            return ['challenged' => false, 'message_key' => 'training.error.already-challenged'];
        }

        if (!$this->levelProgressionTable->hasMaster($level)) {
            return ['challenged' => false, 'message_key' => 'training.error.no-master'];
        }

        $masterRow = $this->creatureRepository->findTrainingMasterByLevel($level, $dragonKillCount);

        if ($masterRow === null) {
            return ['challenged' => false, 'message_key' => 'training.error.no-master'];
        }

        $requiredExperience = $this->levelProgressionTable->requiredExperience($level, $dragonKillCount);

        if ((int) $characterRow['experience'] < $requiredExperience) {
            $this->markMasterSeen($characterId, true);

            return [
                'challenged' => false,
                'message_key' => 'training.error.not-enough-experience',
                'required_experience' => $requiredExperience,
                'missing_experience' => $requiredExperience - (int) $characterRow['experience'],
            ];
        }

        $master = $this->buildMasterCombatant($masterRow, $dragonKillCount, $localeCode);

        $player = new BattleCombatant(
            (string) $characterRow['display_name'],
            $level,
            (string) $characterRow['weapon_name'],
            (int) $characterRow['hit_point'],
            (int) $characterRow['attack_point'],
            (int) $characterRow['defence_point'],
        );

        $modifier = new BattleModifier();
        $roundLog = [];

        while ($player->isAlive() && $master->isAlive()) {
            $roundResult = $this->battleEngine->resolveRound($player, $master, $modifier);

            $roundLog[] = [
                'damage_to_master' => $roundResult->damageToEnemy,
                'damage_to_player' => $roundResult->damageToPlayer,
                'critical_attack' => $roundResult->isCriticalAttack,
                'player_hit_point' => $roundResult->playerHitPoint,
                'master_hit_point' => $roundResult->enemyHitPoint,
            ];
        }

        $isVictory = $player->isAlive();

        if ($isVictory) {
            $advancement = $this->applyLevelUp($characterId, $characterRow);

            return [
                'challenged' => true,
                'victory' => true,
                'master_name' => $this->translateMasterField($masterRow, 'master_name', $localeCode),
                'master_message' => $this->translateMasterField(
                    $masterRow,
                    'victory_message',
                    $localeCode,
                ),
                'round_log' => $roundLog,
                'advancement' => $advancement,
            ];
        }

        $this->restoreAfterDefeat($characterId, (int) $characterRow['max_hit_point']);

        return [
            'challenged' => true,
            'victory' => false,
            'master_name' => $this->translateMasterField($masterRow, 'master_name', $localeCode),
            'master_message' => $this->translateMasterField(
                $masterRow,
                'defeat_message',
                $localeCode,
            ),
            'round_log' => $roundLog,
        ];
    }

    /**
     * @param array<string, mixed> $masterRow
     */
    private function buildMasterCombatant(
        array $masterRow,
        int $dragonKillCount,
        string $localeCode,
    ): BattleCombatant {
        $attackFlux = $dragonKillCount > 0 ? \random_int(0, $dragonKillCount) : 0;
        $defenceFlux = $dragonKillCount - $attackFlux > 0
            ? \random_int(0, $dragonKillCount - $attackFlux)
            : 0;
        $healthFlux = (int) \round(($dragonKillCount - ($attackFlux + $defenceFlux)) * 0.7);

        return new BattleCombatant(
            $this->translateMasterField($masterRow, 'master_name', $localeCode),
            (int) $masterRow['master_level'],
            $this->translateMasterField($masterRow, 'weapon_name', $localeCode),
            (int) $masterRow['health'] + $healthFlux,
            (int) $masterRow['attack_point'] + $attackFlux,
            (int) $masterRow['defense_point'] + $defenceFlux,
        );
    }

    /**
     * @param array<string, mixed> $characterRow
     *
     * @return array<string, int>
     */
    private function applyLevelUp(int $characterId, array $characterRow): array
    {
        $newLevel = (int) $characterRow['level'] + 1;
        $newMaxHitPoint = (int) $characterRow['max_hit_point'] + LevelProgressionTable::HIT_POINT_GAIN_PER_LEVEL;

        $this->connection
            ->prepare('UPDATE game_character SET level = :level WHERE character_id = :character_id')
            ->execute(['level' => $newLevel, 'character_id' => $characterId]);

        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET max_hit_point = :max_hit_point,
                        hit_point     = :hit_point,
                        soul_point    = soul_point + :soul_point_gain,
                        is_alive      = 1
                  WHERE character_id = :character_id',
            )
            ->execute([
                'max_hit_point' => $newMaxHitPoint,
                'hit_point' => $newMaxHitPoint,
                'soul_point_gain' => LevelProgressionTable::SOUL_POINT_GAIN_PER_LEVEL,
                'character_id' => $characterId,
            ]);

        $this->connection
            ->prepare(
                'UPDATE character_combat_stat
                    SET attack_point  = attack_point + :attack_gain,
                        defence_point = defence_point + :defence_gain
                  WHERE character_id = :character_id',
            )
            ->execute([
                'attack_gain' => LevelProgressionTable::ATTACK_GAIN_PER_LEVEL,
                'defence_gain' => LevelProgressionTable::DEFENCE_GAIN_PER_LEVEL,
                'character_id' => $characterId,
            ]);

        $this->markMasterSeen($characterId, false);
        $this->incrementSpecialty($characterId);

        return [
            'level' => $newLevel,
            'max_hit_point' => $newMaxHitPoint,
            'attack_gain' => LevelProgressionTable::ATTACK_GAIN_PER_LEVEL,
            'defence_gain' => LevelProgressionTable::DEFENCE_GAIN_PER_LEVEL,
            'soul_point_gain' => LevelProgressionTable::SOUL_POINT_GAIN_PER_LEVEL,
        ];
    }

    private function restoreAfterDefeat(int $characterId, int $maxHitPoint): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET hit_point = :hit_point,
                        is_alive  = 1
                  WHERE character_id = :character_id',
            )
            ->execute(['hit_point' => $maxHitPoint, 'character_id' => $characterId]);

        $this->markMasterSeen($characterId, true);
    }

    private function markMasterSeen(int $characterId, bool $hasSeen): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_progression
                    SET seen_master_level = :seen_master_level
                  WHERE character_id = :character_id',
            )
            ->execute([
                'seen_master_level' => $hasSeen ? 1 : 0,
                'character_id' => $characterId,
            ]);
    }

    private function incrementSpecialty(int $characterId): void
    {
        $statement = $this->connection->prepare(
            'SELECT specialty_code, dark_arts_rank, mystical_power_rank, thievery_rank
               FROM character_specialty
              WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false || (int) $row['specialty_code'] === 0) {
            return;
        }

        $rankColumnName = match ((int) $row['specialty_code']) {
            1 => 'dark_arts_rank',
            2 => 'mystical_power_rank',
            default => 'thievery_rank',
        };

        $useColumnName = match ((int) $row['specialty_code']) {
            1 => 'dark_arts_use',
            2 => 'mystical_power_use',
            default => 'thievery_use',
        };

        $this->connection
            ->prepare(
                \sprintf(
                    'UPDATE character_specialty SET %s = %s + 1 WHERE character_id = :character_id',
                    $rankColumnName,
                    $rankColumnName,
                ),
            )
            ->execute(['character_id' => $characterId]);

        $newRank = (int) $row[$rankColumnName] + 1;

        if ($newRank % 3 !== 0) {
            return;
        }

        $this->connection
            ->prepare(
                \sprintf(
                    'UPDATE character_specialty SET %s = %s + 1 WHERE character_id = :character_id',
                    $useColumnName,
                    $useColumnName,
                ),
            )
            ->execute(['character_id' => $characterId]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchTrainingRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.display_name,
                    game_character.level,
                    character_vital.hit_point,
                    character_vital.max_hit_point,
                    character_combat_stat.attack_point,
                    character_combat_stat.defence_point,
                    character_progression.experience,
                    character_progression.dragon_kill_count,
                    character_progression.seen_master_level,
                    character_equipment.weapon_name
               FROM game_character
               JOIN character_vital        ON character_vital.character_id = game_character.character_id
               JOIN character_combat_stat  ON character_combat_stat.character_id = game_character.character_id
               JOIN character_progression  ON character_progression.character_id = game_character.character_id
               JOIN character_equipment    ON character_equipment.character_id = game_character.character_id
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
