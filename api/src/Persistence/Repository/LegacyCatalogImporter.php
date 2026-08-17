<?php

declare(strict_types=1);

namespace Lotdg\Persistence\Repository;

use Lotdg\Support\LegacySqlValueParser;
use PDO;
use RuntimeException;

final class LegacyCatalogImporter
{
    public function __construct(
        private readonly PDO $connection,
        private readonly LegacySqlValueParser $parser = new LegacySqlValueParser(),
    ) {
    }

    /**
     * @return array<string, int> 테이블별 적재 건수
     */
    public function import(string $legacySqlFilePath): array
    {
        $sql = \file_get_contents($legacySqlFilePath);

        if ($sql === false) {
            throw new RuntimeException(\sprintf('레거시 SQL 을 읽을 수 없습니다: %s', $legacySqlFilePath));
        }

        $sql = $this->toUtf8($sql);

        $this->connection->beginTransaction();

        try {
            $countMap = [
                'weapon' => $this->importWeapon($sql),
                'armor' => $this->importArmor($sql),
                'creature' => $this->importCreature($sql),
                'training_master' => $this->importTrainingMaster($sql),
                'mount' => $this->importMount($sql),
                'riddle' => $this->importRiddle($sql),
                'taunt' => $this->importTaunt($sql),
                'nasty_word' => $this->importNastyWord($sql),
            ];

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return $countMap;
    }

    private function importWeapon(string $sql): int
    {
        $rowList = $this->collectSurvivingRow($sql, 'weapons');

        $this->connection->exec('DELETE FROM weapon');

        $statement = $this->connection->prepare(
            'INSERT INTO weapon (weapon_name, price, damage, dragon_kill_tier)
             VALUES (:weapon_name, :price, :damage, :dragon_kill_tier)',
        );

        foreach ($rowList as $row) {
            $statement->execute([
                'weapon_name' => (string) ($row[1] ?? ''),
                'price' => (int) ($row[2] ?? 0),
                'damage' => (int) ($row[3] ?? 1),
                'dragon_kill_tier' => (int) ($row[4] ?? 0),
            ]);
        }

        return \count($rowList);
    }

    private function importArmor(string $sql): int
    {
        $rowList = $this->collectSurvivingRow($sql, 'armor');

        $this->connection->exec('DELETE FROM armor');

        $statement = $this->connection->prepare(
            'INSERT INTO armor (armor_name, price, defense, dragon_kill_tier)
             VALUES (:armor_name, :price, :defense, :dragon_kill_tier)',
        );

        foreach ($rowList as $row) {
            $statement->execute([
                'armor_name' => (string) ($row[1] ?? ''),
                'price' => (int) ($row[2] ?? 0),
                'defense' => (int) ($row[3] ?? 1),
                'dragon_kill_tier' => (int) ($row[4] ?? 0),
            ]);
        }

        return \count($rowList);
    }

    private function importCreature(string $sql): int
    {
        $rowList = $this->parser->parseInsertStatementList($sql, 'creatures');

        $this->connection->exec('DELETE FROM creature');

        $statement = $this->connection->prepare(
            'INSERT INTO creature
                 (creature_name, creature_level, weapon_name, victory_message, defeat_message,
                  gold_reward, experience_reward, health, attack_point, defense_point,
                  location_code, created_by_name)
             VALUES
                 (:creature_name, :creature_level, :weapon_name, :victory_message, :defeat_message,
                  :gold_reward, :experience_reward, :health, :attack_point, :defense_point,
                  :location_code, :created_by_name)',
        );

        $insertedCount = 0;

        foreach ($rowList as $row) {
            $columnCount = \count($row);
            $locationCode = $columnCount >= 14 ? (int) ($row[13] ?? 0) : 0;
            $createdByName = $columnCount >= 13 ? (string) ($row[12] ?? '') : '';

            $statement->execute([
                'creature_name' => (string) ($row[1] ?? ''),
                'creature_level' => \max(1, (int) ($row[2] ?? 1)),
                'weapon_name' => (string) ($row[3] ?? ''),
                'victory_message' => (string) ($row[4] ?? ''),
                'defeat_message' => (string) ($row[5] ?? ''),
                'gold_reward' => \max(0, (int) ($row[6] ?? 0)),
                'experience_reward' => \max(0, (int) ($row[7] ?? 0)),
                'health' => \max(1, (int) ($row[8] ?? 1)),
                'attack_point' => \max(0, (int) ($row[9] ?? 0)),
                'defense_point' => \max(0, (int) ($row[10] ?? 0)),
                'location_code' => $locationCode === 1 ? 1 : 0,
                'created_by_name' => $createdByName,
            ]);

            ++$insertedCount;
        }

        return $insertedCount;
    }

    private function importTrainingMaster(string $sql): int
    {
        $rowList = $this->parser->parseInsertStatementList($sql, 'masters');

        $this->connection->exec('DELETE FROM training_master');

        $statement = $this->connection->prepare(
            'INSERT INTO training_master
                 (master_name, master_level, weapon_name, victory_message, defeat_message,
                  health, attack_point, defense_point)
             VALUES
                 (:master_name, :master_level, :weapon_name, :victory_message, :defeat_message,
                  :health, :attack_point, :defense_point)',
        );

        foreach ($rowList as $row) {
            $statement->execute([
                'master_name' => (string) ($row[1] ?? ''),
                'master_level' => \max(1, (int) ($row[2] ?? 1)),
                'weapon_name' => (string) ($row[3] ?? ''),
                'victory_message' => (string) ($row[4] ?? ''),
                'defeat_message' => (string) ($row[5] ?? ''),
                'health' => \max(1, (int) ($row[8] ?? 1)),
                'attack_point' => \max(0, (int) ($row[9] ?? 0)),
                'defense_point' => \max(0, (int) ($row[10] ?? 0)),
            ]);
        }

        return \count($rowList);
    }

    private function importMount(string $sql): int
    {
        $rowList = $this->parser->parseInsertStatementList($sql, 'mounts');

        $this->connection->exec('DELETE FROM mount');

        $statement = $this->connection->prepare(
            'INSERT INTO mount
                 (mount_name, mount_description, mount_category, buff_json,
                  cost_gem, cost_gold, is_active, extra_forest_fight, tavern_access_level,
                  new_day_message, recharge_message, partial_recharge_message,
                  mine_can_enter, mine_can_die, mine_can_save,
                  mine_tether_message, mine_death_message, mine_save_message)
             VALUES
                 (:mount_name, :mount_description, :mount_category, :buff_json,
                  :cost_gem, :cost_gold, :is_active, :extra_forest_fight, :tavern_access_level,
                  :new_day_message, :recharge_message, :partial_recharge_message,
                  :mine_can_enter, :mine_can_die, :mine_can_save,
                  :mine_tether_message, :mine_death_message, :mine_save_message)',
        );

        foreach ($rowList as $row) {
            $statement->execute([
                'mount_name' => (string) ($row[1] ?? ''),
                'mount_description' => (string) ($row[2] ?? ''),
                'mount_category' => (string) ($row[3] ?? ''),
                'buff_json' => $this->serializedToJson((string) ($row[4] ?? '')),
                'cost_gem' => \max(0, (int) ($row[5] ?? 0)),
                'cost_gold' => \max(0, (int) ($row[6] ?? 0)),
                'is_active' => (int) ($row[7] ?? 1) === 0 ? 0 : 1,
                'extra_forest_fight' => (int) ($row[8] ?? 0),
                'tavern_access_level' => \max(0, (int) ($row[9] ?? 0)),
                'new_day_message' => (string) ($row[10] ?? ''),
                'recharge_message' => (string) ($row[11] ?? ''),
                'partial_recharge_message' => (string) ($row[12] ?? ''),
                'mine_can_enter' => $this->clampPercent((int) ($row[13] ?? 0)),
                'mine_can_die' => $this->clampPercent((int) ($row[14] ?? 0)),
                'mine_can_save' => $this->clampPercent((int) ($row[15] ?? 0)),
                'mine_tether_message' => (string) ($row[16] ?? ''),
                'mine_death_message' => (string) ($row[17] ?? ''),
                'mine_save_message' => (string) ($row[18] ?? ''),
            ]);
        }

        return \count($rowList);
    }

    private function importRiddle(string $sql): int
    {
        $rowList = $this->parser->parseInsertStatementList($sql, 'riddles');

        $this->connection->exec('DELETE FROM riddle');

        $statement = $this->connection->prepare(
            'INSERT INTO riddle (riddle_text, answer_text) VALUES (:riddle_text, :answer_text)',
        );

        foreach ($rowList as $row) {
            $statement->execute([
                'riddle_text' => (string) ($row[1] ?? ''),
                'answer_text' => (string) ($row[2] ?? ''),
            ]);
        }

        return \count($rowList);
    }

    private function importTaunt(string $sql): int
    {
        $rowList = $this->collectSurvivingRow($sql, 'taunts');

        $this->connection->exec('DELETE FROM taunt');

        $statement = $this->connection->prepare(
            'INSERT INTO taunt (taunt_text, editor_name) VALUES (:taunt_text, :editor_name)',
        );

        foreach ($rowList as $row) {
            $statement->execute([
                'taunt_text' => (string) ($row[1] ?? ''),
                'editor_name' => (string) ($row[2] ?? ''),
            ]);
        }

        return \count($rowList);
    }

    private function importNastyWord(string $sql): int
    {
        $rowList = $this->parser->parseInsertStatementList($sql, 'nastywords');

        $this->connection->exec('DELETE FROM nasty_word');

        $statement = $this->connection->prepare(
            'INSERT OR IGNORE INTO nasty_word (word_pattern) VALUES (:word_pattern)',
        );

        $insertedCount = 0;

        foreach ($rowList as $row) {
            $wordList = \preg_split('/\s+/', \trim((string) ($row[0] ?? '')), -1, \PREG_SPLIT_NO_EMPTY);

            foreach ($wordList === false ? [] : $wordList as $word) {
                $statement->execute(['word_pattern' => $word]);
                ++$insertedCount;
            }
        }

        return $insertedCount;
    }

    /**
     * @return list<list<string|null>>
     */
    private function collectSurvivingRow(string $sql, string $legacyTableName): array
    {
        $deleteOffsetList = $this->parser->findDeleteOffsetList($sql, $legacyTableName);

        if ($deleteOffsetList === []) {
            return $this->parser->parseInsertStatementList($sql, $legacyTableName);
        }

        $lastDeleteOffset = \max($deleteOffsetList);
        $rowList = $this->parser->parseInsertStatementList($sql, $legacyTableName);
        $survivingRowList = [];

        foreach ($rowList as $occurrenceIndex => $row) {
            $insertOffset = $this->parser->findInsertOffset($sql, $legacyTableName, $occurrenceIndex);

            if ($insertOffset !== null && $insertOffset > $lastDeleteOffset) {
                $survivingRowList[] = $row;
            }
        }

        return $survivingRowList;
    }

    private function clampPercent(int $value): int
    {
        return \max(0, \min(100, $value));
    }

    private function serializedToJson(string $serializedValue): string
    {
        if ($serializedValue === '') {
            return '{}';
        }

        $decoded = @\unserialize($serializedValue, ['allowed_classes' => false]);

        if (!\is_array($decoded)) {
            return '{}';
        }

        $encoded = \json_encode($decoded, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }

    private function toUtf8(string $sql): string
    {
        if (\mb_check_encoding($sql, 'UTF-8')) {
            return $sql;
        }

        $converted = \mb_convert_encoding($sql, 'UTF-8', 'CP949');

        return \is_string($converted) ? $converted : $sql;
    }
}
