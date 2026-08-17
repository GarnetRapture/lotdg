<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\Domain\World\GameClock;
use Lotdg\Persistence\Repository\GameSettingRepository;
use PDO;

final class NewsService
{
    public const int NEWS_PER_PAGE = 50;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly GameClock $gameClock,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function listToday(int $limit = self::NEWS_PER_PAGE): array
    {
        return $this->listByDayOffset(0, 1, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function listByDayOffset(int $dayOffset, int $page = 1, int $limit = self::NEWS_PER_PAGE): array
    {
        $this->expireOldNews();

        $newsDate = \date(
            'Y-m-d',
            $this->gameClock->currentGameTimestamp() - \max(0, $dayOffset) * 86400,
        );
        $pageIndex = \max(0, $page - 1);
        $offset = $pageIndex * $limit;
        $totalCount = $this->countByDate($newsDate);

        $statement = $this->connection->prepare(
            'SELECT daily_news.news_id,
                    daily_news.news_text,
                    daily_news.news_date,
                    game_character.character_id,
                    game_character.display_name
               FROM daily_news
               LEFT JOIN account        ON account.account_id = daily_news.account_id
               LEFT JOIN game_character ON game_character.account_id = account.account_id
              WHERE daily_news.news_date = :news_date
              ORDER BY daily_news.news_id DESC
              LIMIT :limit OFFSET :offset',
        );
        $statement->bindValue('news_date', $newsDate);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'news_date' => $newsDate,
            'day_offset' => \max(0, $dayOffset),
            'page' => $pageIndex + 1,
            'page_count' => $totalCount === 0 ? 1 : (int) \ceil($totalCount / $limit),
            'range_from' => $totalCount === 0 ? 0 : $offset + 1,
            'range_to' => \min($offset + $limit, $totalCount),
            'total_count' => $totalCount,
            'news_list' => \array_map(
                static fn (array $row): array => [
                    'news_id' => (int) $row['news_id'],
                    'news_text' => (string) $row['news_text'],
                    'character_id' => $row['character_id'] === null ? null : (int) $row['character_id'],
                    'display_name' => (string) ($row['display_name'] ?? ''),
                ],
                $statement->fetchAll(),
            ),
        ];
    }

    public function remove(int $newsId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM daily_news WHERE news_id = :news_id');
        $statement->execute(['news_id' => $newsId]);

        return $statement->rowCount() > 0;
    }

    private function countByDate(string $newsDate): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM daily_news WHERE news_date = :news_date',
        );
        $statement->execute(['news_date' => $newsDate]);

        return (int) $statement->fetchColumn();
    }

    public function publish(string $newsText, ?int $accountId = null): int
    {
        $this->connection
            ->prepare(
                'INSERT INTO daily_news (news_text, news_date, account_id)
                 VALUES (:news_text, :news_date, :account_id)',
            )
            ->execute([
                'news_text' => $newsText,
                'news_date' => $this->gameClock->gameDateString(),
                'account_id' => $accountId,
            ]);

        return (int) $this->connection->lastInsertId();
    }

    private function expireOldNews(): void
    {
        $expireDay = $this->gameSettingRepository->getInt('expirecontent', 180);

        if ($expireDay <= 0) {
            return;
        }

        $expireBefore = \date(
            'Y-m-d',
            $this->gameClock->currentGameTimestamp() - $expireDay * 86400,
        );

        $this->connection
            ->prepare('DELETE FROM daily_news WHERE news_date < :expire_before')
            ->execute(['expire_before' => $expireBefore]);
    }
}
