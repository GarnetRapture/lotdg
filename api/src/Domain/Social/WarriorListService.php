<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LegacyLikePatternBuilder;
use PDO;

final class WarriorListService
{
    public const int PLAYERS_PER_PAGE = 50;

    private const int SEARCH_RESULT_LIMIT = 100;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly LegacyLikePatternBuilder $likePatternBuilder = new LegacyLikePatternBuilder(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function listOnline(): array
    {
        $loginTimeout = $this->gameSettingRepository->getInt('LOGINTIMEOUT', 900);

        $statement = $this->connection->prepare(
            $this->baseSelect()
            . ' AND account.is_logged_in = 1
                AND account.last_seen_at > datetime(\'now\', :login_timeout)'
            . $this->baseOrder(),
        );
        $statement->execute(['login_timeout' => \sprintf('-%d seconds', $loginTimeout)]);

        return [
            'mode' => 'online',
            'total_player_count' => $this->countPlayer(),
            'warrior_list' => $this->decorate($statement->fetchAll(), $loginTimeout),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listPage(int $page): array
    {
        $loginTimeout = $this->gameSettingRepository->getInt('LOGINTIMEOUT', 900);
        $totalPlayerCount = $this->countPlayer();
        $pageIndex = \max(0, $page - 1);
        $offset = $pageIndex * self::PLAYERS_PER_PAGE;

        $statement = $this->connection->prepare(
            $this->baseSelect() . $this->baseOrder() . ' LIMIT :limit OFFSET :offset',
        );
        $statement->bindValue('limit', self::PLAYERS_PER_PAGE, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'mode' => 'page',
            'page' => $pageIndex + 1,
            'page_count' => (int) \ceil($totalPlayerCount / self::PLAYERS_PER_PAGE),
            'range_from' => $totalPlayerCount === 0 ? 0 : $offset + 1,
            'range_to' => \min($offset + self::PLAYERS_PER_PAGE, $totalPlayerCount),
            'total_player_count' => $totalPlayerCount,
            'warrior_list' => $this->decorate($statement->fetchAll(), $loginTimeout),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $searchTerm): array
    {
        $loginTimeout = $this->gameSettingRepository->getInt('LOGINTIMEOUT', 900);
        $pattern = $this->likePatternBuilder->build($searchTerm);

        $statement = $this->connection->prepare(
            $this->baseSelect() . ' AND game_character.display_name LIKE :pattern'
            . $this->baseOrder() . ' LIMIT :limit',
        );
        $statement->bindValue('pattern', $pattern);
        $statement->bindValue('limit', self::SEARCH_RESULT_LIMIT + 1, PDO::PARAM_INT);
        $statement->execute();

        $rowList = $statement->fetchAll();
        $isTruncated = \count($rowList) > self::SEARCH_RESULT_LIMIT;

        if ($isTruncated) {
            $rowList = \array_slice($rowList, 0, self::SEARCH_RESULT_LIMIT);
        }

        return [
            'mode' => 'search',
            'search_term' => $searchTerm,
            'truncated' => $isTruncated,
            'total_player_count' => $this->countPlayer(),
            'warrior_list' => $this->decorate($rowList, $loginTimeout),
        ];
    }

    private function baseSelect(): string
    {
        return 'SELECT account.login_name,
                       account.is_logged_in,
                       account.last_seen_at,
                       game_character.character_id,
                       game_character.display_name,
                       game_character.level,
                       game_character.sex_code,
                       game_character.location_code,
                       character_vital.is_alive,
                       character_progression.dragon_kill_count
                  FROM game_character
                  JOIN account               ON account.account_id = game_character.account_id
                  JOIN character_vital       ON character_vital.character_id = game_character.character_id
                  JOIN character_progression ON character_progression.character_id = game_character.character_id
                 WHERE account.is_locked = 0';
    }

    private function baseOrder(): string
    {
        return ' ORDER BY game_character.level DESC,
                          character_progression.dragon_kill_count DESC,
                          account.login_name ASC';
    }

    private function countPlayer(): int
    {
        $statement = $this->connection->query('SELECT COUNT(*) FROM account WHERE is_locked = 0');

        return $statement === false ? 0 : (int) $statement->fetchColumn();
    }

    /**
     * @param list<array<string, mixed>> $rowList
     *
     * @return list<array<string, mixed>>
     */
    private function decorate(array $rowList, int $loginTimeout): array
    {
        $now = \time();

        return \array_map(
            static function (array $row) use ($loginTimeout, $now): array {
                $lastSeenAt = $row['last_seen_at'] === null
                    ? null
                    : \strtotime((string) $row['last_seen_at']);

                $isOnline = (int) $row['is_logged_in'] === 1
                    && $lastSeenAt !== false
                    && $lastSeenAt !== null
                    && $now - $lastSeenAt < $loginTimeout;

                return [
                    'character_id' => (int) $row['character_id'],
                    'login_name' => (string) $row['login_name'],
                    'display_name' => (string) $row['display_name'],
                    'level' => (int) $row['level'],
                    'sex_code' => (int) $row['sex_code'],
                    'is_alive' => (int) $row['is_alive'] === 1,
                    'location_code' => (int) $row['location_code'],
                    'is_online' => $isOnline,
                    'days_since_last_seen' => $lastSeenAt === null || $lastSeenAt === false
                        ? null
                        : (int) \floor(($now - $lastSeenAt) / 86400),
                ];
            },
            $rowList,
        );
    }
}
