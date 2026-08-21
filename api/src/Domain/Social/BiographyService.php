<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\I18n\CatalogTranslator;
use Lotdg\Support\LocalizedException;
use PDO;

final class BiographyService
{
    private const int NEWS_HISTORY_LIMIT = 100;

    public function __construct(
        private readonly PDO $connection,
        private readonly BadWordFilter $badWordFilter,
        private readonly CatalogTranslator $catalogTranslator,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    private function translateMountName(array $row, string $localeCode): ?string
    {
        if ($row['mount_id'] === null || $row['mount_name'] === null) {
            return null;
        }

        return $this->catalogTranslator->translate(
            CatalogTranslator::ENTITY_MOUNT,
            (int) $row['mount_id'],
            'mount_name',
            (string) $row['mount_name'],
            $localeCode,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function view(int $characterId, string $localeCode): array
    {
        $row = $this->fetchBiographyRow($characterId);

        return [
            'character_id' => (int) $row['character_id'],
            'login_name' => (string) $row['login_name'],
            'display_name' => (string) $row['display_name'],
            'rank_title' => (string) $row['rank_title'],
            'level' => (int) $row['level'],
            'sex_code' => (int) $row['sex_code'],
            'race_code' => (int) $row['race_code'],
            'specialty_code' => (int) $row['specialty_code'],
            'resurrection_count' => (int) $row['resurrection_count'],
            'dragon_kill_count' => (int) $row['dragon_kill_count'],
            'mount_name' => $this->translateMountName($row, $localeCode),
            'biography' => $this->badWordFilter->clean((string) $row['biography']),
            'news_history' => $this->fetchNewsHistory((int) $row['account_id']),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchNewsHistory(int $accountId): array
    {
        $statement = $this->connection->prepare(
            'SELECT news_id, news_text, news_date
               FROM daily_news
              WHERE account_id = :account_id
              ORDER BY news_date DESC, news_id ASC
              LIMIT :limit',
        );
        $statement->bindValue('account_id', $accountId, PDO::PARAM_INT);
        $statement->bindValue('limit', self::NEWS_HISTORY_LIMIT, PDO::PARAM_INT);
        $statement->execute();

        return \array_map(
            static fn (array $row): array => [
                'news_id' => (int) $row['news_id'],
                'news_text' => (string) $row['news_text'],
                'news_date' => (string) $row['news_date'],
            ],
            $statement->fetchAll(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchBiographyRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.character_id,
                    game_character.account_id,
                    game_character.display_name,
                    game_character.rank_title,
                    game_character.level,
                    game_character.sex_code,
                    game_character.race_code,
                    account.login_name,
                    character_specialty.specialty_code,
                    character_vital.resurrection_count,
                    character_progression.dragon_kill_count,
                    character_social.biography,
                    mount.mount_id,
                    mount.mount_name
               FROM game_character
               JOIN account               ON account.account_id = game_character.account_id
               JOIN character_specialty   ON character_specialty.character_id = game_character.character_id
               JOIN character_vital       ON character_vital.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
               JOIN character_social      ON character_social.character_id = game_character.character_id
               JOIN character_equipment   ON character_equipment.character_id = game_character.character_id
               LEFT JOIN mount            ON mount.mount_id = character_equipment.mount_id
              WHERE game_character.character_id = :character_id
                AND account.is_locked = 0',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return $row;
    }
}
