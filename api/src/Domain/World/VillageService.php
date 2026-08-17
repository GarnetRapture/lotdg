<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Domain\Account\LevelProgressionTable;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class VillageService
{
    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly GameClock $gameClock,
        private readonly LevelProgressionTable $levelProgressionTable = new LevelProgressionTable(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function enter(int $characterId): array
    {
        $row = $this->fetchVillageRow($characterId);

        if ((int) $row['is_alive'] !== 1) {
            return [
                'entered' => false,
                'redirect' => 'graveyard',
                'message_key' => 'village.error.character-dead',
            ];
        }

        return [
            'entered' => true,
            'display_name' => (string) $row['display_name'],
            'level' => (int) $row['level'],
            'game_time' => $this->gameClock->formatGameTime(),
            'real_seconds_until_new_day' => $this->gameClock->realSecondsUntilNextGameDay(),
            'latest_news' => $this->fetchLatestNews(),
            'auto_master_challenge' => $this->resolveAutoMasterChallenge($row),
            'pvp_enabled' => $this->gameSettingRepository->getBool('pvp', true),
            'destination_list' => $this->buildDestinationList($row),
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function resolveAutoMasterChallenge(array $row): array
    {
        if (!$this->gameSettingRepository->getBool('automaster', true)) {
            return ['triggered' => false];
        }

        if ((int) $row['seen_master_level'] === 1) {
            return ['triggered' => false];
        }

        $level = (int) $row['level'];

        if ($level >= LevelProgressionTable::MAXIMUM_LEVEL) {
            return ['triggered' => false];
        }

        $nextLevelRequirement = $this->levelProgressionTable->requiredExperience(
            $level + 1,
            (int) $row['dragon_kill_count'],
        );

        if ((int) $row['experience'] <= $nextLevelRequirement) {
            return ['triggered' => false];
        }

        return [
            'triggered' => true,
            'reason_key' => 'village.auto-master.truant',
            'next_level_requirement' => $nextLevelRequirement,
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<array<string, mixed>>
     */
    private function buildDestinationList(array $row): array
    {
        $destinationList = [
            ['group_key' => 'village.group.combat', 'code' => 'forest', 'label_key' => 'village.destination.forest'],
            ['group_key' => 'village.group.combat', 'code' => 'training', 'label_key' => 'village.destination.training'],
            ['group_key' => 'village.group.combat', 'code' => 'hall-of-fame', 'label_key' => 'village.destination.hall-of-fame'],
            ['group_key' => 'village.group.commerce', 'code' => 'weapon-shop', 'label_key' => 'village.destination.weapon-shop'],
            ['group_key' => 'village.group.commerce', 'code' => 'armor-shop', 'label_key' => 'village.destination.armor-shop'],
            ['group_key' => 'village.group.commerce', 'code' => 'gem-trader', 'label_key' => 'village.destination.gem-trader'],
            ['group_key' => 'village.group.commerce', 'code' => 'bank', 'label_key' => 'village.destination.bank'],
            ['group_key' => 'village.group.commerce', 'code' => 'gypsy', 'label_key' => 'village.destination.gypsy'],
            ['group_key' => 'village.group.other', 'code' => 'inn', 'label_key' => 'village.destination.inn'],
            ['group_key' => 'village.group.other', 'code' => 'stables', 'label_key' => 'village.destination.stables'],
            ['group_key' => 'village.group.other', 'code' => 'gardens', 'label_key' => 'village.destination.gardens'],
            ['group_key' => 'village.group.other', 'code' => 'mysterious-rock', 'label_key' => 'village.destination.mysterious-rock'],
            ['group_key' => 'village.group.system', 'code' => 'news', 'label_key' => 'village.destination.news'],
            ['group_key' => 'village.group.system', 'code' => 'preferences', 'label_key' => 'village.destination.preferences'],
            ['group_key' => 'village.group.system', 'code' => 'warrior-list', 'label_key' => 'village.destination.warrior-list'],
            ['group_key' => 'village.group.system', 'code' => 'logout', 'label_key' => 'village.destination.logout'],
        ];

        if ($this->gameSettingRepository->getBool('pvp', true)) {
            $destinationList[] = [
                'group_key' => 'village.group.combat',
                'code' => 'player-versus-player',
                'label_key' => 'village.destination.player-versus-player',
            ];
        }

        if ((int) $row['superuser_level'] >= 2) {
            $destinationList[] = [
                'group_key' => 'village.group.system',
                'code' => 'administration',
                'label_key' => 'village.destination.administration',
            ];
        }

        return $destinationList;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchLatestNews(): ?array
    {
        $statement = $this->connection->query(
            'SELECT news_id, news_text, news_date FROM daily_news ORDER BY news_id DESC LIMIT 1',
        );

        if ($statement === false) {
            return null;
        }

        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return [
            'news_id' => (int) $row['news_id'],
            'news_text' => (string) $row['news_text'],
            'news_date' => (string) $row['news_date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchVillageRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.display_name,
                    game_character.level,
                    character_vital.is_alive,
                    character_progression.experience,
                    character_progression.dragon_kill_count,
                    character_progression.seen_master_level,
                    account_privilege.superuser_level
               FROM game_character
               JOIN character_vital       ON character_vital.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
               JOIN account_privilege     ON account_privilege.account_id = game_character.account_id
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
