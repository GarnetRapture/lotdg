<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\Persistence\Repository\GameSettingRepository;
use PDO;

final class CommentaryService
{
    public const int MAXIMUM_COMMENT_LENGTH = 200;

    private const int LONG_WORD_SPLIT_LENGTH = 45;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly BadWordFilter $badWordFilter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function listBySection(
        int $characterId,
        string $sectionCode,
        int $limit,
        int $page,
        ?int $beforeCommentaryId = null,
    ): array {
        $this->expireOldCommentary();

        $usesCursor = $beforeCommentaryId !== null && $beforeCommentaryId > 0;

        $statement = $this->connection->prepare(
            'SELECT commentary.commentary_id,
                    commentary.comment_text,
                    commentary.posted_at,
                    game_character.character_id,
                    game_character.display_name
               FROM commentary
               LEFT JOIN account        ON account.account_id = commentary.author_account_id
               LEFT JOIN game_character ON game_character.account_id = account.account_id
              WHERE commentary.section_code = :section_code
                AND (account.is_locked IS NULL OR account.is_locked = 0)'
            . ($usesCursor ? ' AND commentary.commentary_id < :before_commentary_id' : '')
            . ' ORDER BY commentary.commentary_id DESC
              LIMIT :limit'
            . ($usesCursor ? '' : ' OFFSET :offset'),
        );
        $statement->bindValue('section_code', $sectionCode);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);

        if ($usesCursor) {
            $statement->bindValue('before_commentary_id', $beforeCommentaryId, PDO::PARAM_INT);
        } else {
            $statement->bindValue('offset', $page * $limit, PDO::PARAM_INT);
        }

        $statement->execute();

        $commentList = [];

        foreach (\array_reverse($statement->fetchAll()) as $row) {
            $commentList[] = [
                'commentary_id' => (int) $row['commentary_id'],
                'character_id' => $row['character_id'] === null ? null : (int) $row['character_id'],
                'display_name' => (string) ($row['display_name'] ?? ''),
                'comment_text' => (string) $row['comment_text'],
                'posted_at' => (string) $row['posted_at'],
            ];
        }

        return [
            'section_code' => $sectionCode,
            'page' => $page,
            'oldest_commentary_id' => $commentList === [] ? null : $commentList[0]['commentary_id'],
            'newest_commentary_id' => $commentList === []
                ? null
                : $commentList[\count($commentList) - 1]['commentary_id'],
            'has_older' => $this->hasOlderThan(
                $sectionCode,
                $commentList === [] ? 0 : $commentList[0]['commentary_id'],
            ),
            'comment_list' => $commentList,
            'post_quota_remaining' => $this->remainingPostQuota($characterId, $sectionCode, $limit),
        ];
    }

    private function hasOlderThan(string $sectionCode, int $commentaryId): bool
    {
        if ($commentaryId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT 1
               FROM commentary
              WHERE section_code = :section_code
                AND commentary_id < :commentary_id
              LIMIT 1',
        );
        $statement->execute([
            'section_code' => $sectionCode,
            'commentary_id' => $commentaryId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return array<string, mixed>
     */
    public function post(
        int $characterId,
        string $sectionCode,
        string $rawComment,
        int $limit = 10,
    ): array {
        $comment = \trim(\str_replace('`n', '', $rawComment));

        if ($comment === '') {
            return ['posted' => false, 'message_key' => 'commentary.error.empty'];
        }

        if ($this->remainingPostQuota($characterId, $sectionCode, $limit) <= 0) {
            return ['posted' => false, 'message_key' => 'commentary.error.quota-exhausted'];
        }

        $comment = $this->badWordFilter->clean($comment);
        $comment = $this->limitColorCode($comment);
        $comment = $this->splitLongWord($comment);
        $comment = \mb_substr($comment, 0, self::MAXIMUM_COMMENT_LENGTH);

        $accountId = $this->resolveAccountId($characterId);

        if ($this->isDuplicateOfLatest($sectionCode, $accountId, $comment)) {
            return ['posted' => false, 'message_key' => 'commentary.error.duplicate'];
        }

        $this->connection
            ->prepare(
                'INSERT INTO commentary (section_code, author_account_id, comment_text)
                 VALUES (:section_code, :author_account_id, :comment_text)',
            )
            ->execute([
                'section_code' => $sectionCode,
                'author_account_id' => $accountId,
                'comment_text' => $comment,
            ]);

        return [
            'posted' => true,
            'comment_text' => $comment,
            'post_quota_remaining' => $this->remainingPostQuota($characterId, $sectionCode, $limit),
        ];
    }

    private function remainingPostQuota(int $characterId, string $sectionCode, int $limit): int
    {
        $accountId = $this->resolveAccountId($characterId);

        if ($accountId === null) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(*)
               FROM commentary
              WHERE section_code = :section_code
                AND author_account_id = :author_account_id
                AND DATE(posted_at) = DATE(\'now\')',
        );
        $statement->execute([
            'section_code' => $sectionCode,
            'author_account_id' => $accountId,
        ]);

        $postedToday = (int) $statement->fetchColumn();
        $quota = (int) \round($limit / 2);

        return \max(0, $quota - $postedToday);
    }

    private function resolveAccountId(int $characterId): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT account_id FROM game_character WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $accountId = $statement->fetchColumn();

        return $accountId === false ? null : (int) $accountId;
    }

    private function isDuplicateOfLatest(string $sectionCode, ?int $accountId, string $comment): bool
    {
        $statement = $this->connection->prepare(
            'SELECT comment_text, author_account_id
               FROM commentary
              WHERE section_code = :section_code
              ORDER BY commentary_id DESC
              LIMIT 1',
        );
        $statement->execute(['section_code' => $sectionCode]);

        $row = $statement->fetch();

        if ($row === false) {
            return false;
        }

        return (string) $row['comment_text'] === $comment
            && (int) $row['author_account_id'] === (int) $accountId;
    }

    private function limitColorCode(string $comment): string
    {
        $maximumColorCount = $this->gameSettingRepository->getInt('maxcolors', 10);
        $colorCount = 0;
        $length = \strlen($comment);

        for ($index = 0; $index < $length; ++$index) {
            if ($comment[$index] !== '`') {
                continue;
            }

            ++$colorCount;

            if ($colorCount >= $maximumColorCount) {
                return \substr($comment, 0, $index)
                    . \preg_replace('/`./', '', \substr($comment, $index));
            }

            ++$index;
        }

        return $comment;
    }

    private function splitLongWord(string $comment): string
    {
        $result = \preg_replace(
            \sprintf('/(\S{%d})(\S)/u', self::LONG_WORD_SPLIT_LENGTH),
            '$1 $2',
            $comment,
        );

        return $result ?? $comment;
    }

    private function expireOldCommentary(): void
    {
        $expireDay = $this->gameSettingRepository->getInt('expirecontent', 180);

        if ($expireDay <= 0) {
            return;
        }

        $this->connection
            ->prepare(
                'DELETE FROM commentary WHERE posted_at < datetime(\'now\', :expire_offset)',
            )
            ->execute(['expire_offset' => \sprintf('-%d days', $expireDay)]);
    }
}
