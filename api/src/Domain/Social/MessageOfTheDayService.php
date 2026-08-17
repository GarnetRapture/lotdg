<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use PDO;

final class MessageOfTheDayService
{
    public const int TYPE_NOTICE = 0;

    public const int TYPE_POLL = 1;

    private const int FETCH_LIMIT = 20;

    private const int ALWAYS_VISIBLE_COUNT = 5;

    private const int POLL_CHOICE_LIMIT = 6;

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function listAll(int $accountId, int $limit = self::FETCH_LIMIT): array
    {
        $lastSeenAt = $this->fetchLastSeenAt($accountId);

        $statement = $this->connection->prepare(
            'SELECT motd_id, title, body, motd_type, posted_at
               FROM message_of_the_day
              ORDER BY posted_at DESC, motd_id DESC
              LIMIT :limit',
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        $noticeList = [];
        $index = 0;

        foreach ($statement->fetchAll() as $row) {
            $isUnseen = $lastSeenAt === null || (string) $row['posted_at'] > $lastSeenAt;

            if (!$isUnseen && $index >= self::ALWAYS_VISIBLE_COUNT) {
                ++$index;

                continue;
            }

            ++$index;
            $motdId = (int) $row['motd_id'];
            $isPoll = (int) $row['motd_type'] !== self::TYPE_NOTICE;
            $decodedBody = $isPoll ? $this->decodePollBody((string) $row['body']) : null;

            $noticeList[] = [
                'motd_id' => $motdId,
                'title' => (string) $row['title'],
                'body' => $decodedBody === null ? (string) $row['body'] : $decodedBody['body'],
                'motd_type' => (int) $row['motd_type'],
                'posted_at' => (string) $row['posted_at'],
                'is_unseen' => $isUnseen,
                'choice_list' => $decodedBody === null ? [] : $decodedBody['choice_list'],
                'poll_result' => $isPoll ? $this->summarizePoll($motdId, $accountId) : null,
            ];
        }

        return [
            'notice_list' => $noticeList,
            'has_unseen' => $this->hasUnseen($accountId),
        ];
    }

    public function publish(string $title, string $body): int
    {
        return $this->insert($title, $body, self::TYPE_NOTICE);
    }

    /**
     * @param list<string> $choiceList
     */
    public function publishPoll(string $title, string $body, array $choiceList): int
    {
        $trimmedChoiceList = [];

        foreach (\array_slice($choiceList, 0, self::POLL_CHOICE_LIMIT) as $choiceText) {
            if (\trim($choiceText) !== '') {
                $trimmedChoiceList[] = \trim($choiceText);
            }
        }

        $encoded = \json_encode(
            ['body' => $body, 'choice_list' => $trimmedChoiceList],
            \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES,
        );

        return $this->insert($title, $encoded === false ? $body : $encoded, self::TYPE_POLL);
    }

    public function remove(int $motdId): bool
    {
        $statement = $this->connection->prepare(
            'DELETE FROM message_of_the_day WHERE motd_id = :motd_id',
        );
        $statement->execute(['motd_id' => $motdId]);

        return $statement->rowCount() > 0;
    }

    private function insert(string $title, string $body, int $motdType): int
    {
        $this->connection
            ->prepare(
                'INSERT INTO message_of_the_day (title, body, motd_type)
                 VALUES (:title, :body, :motd_type)',
            )
            ->execute([
                'title' => $title,
                'body' => $body,
                'motd_type' => $motdType,
            ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @return array{body: string, choice_list: list<string>}|null
     */
    private function decodePollBody(string $body): ?array
    {
        $decoded = \json_decode($body, true);

        if (!\is_array($decoded) || !isset($decoded['body'])) {
            return ['body' => $body, 'choice_list' => []];
        }

        $choiceList = [];

        foreach (\is_array($decoded['choice_list'] ?? null) ? $decoded['choice_list'] : [] as $choiceText) {
            $choiceList[] = (string) $choiceText;
        }

        return ['body' => (string) $decoded['body'], 'choice_list' => $choiceList];
    }

    private function fetchLastSeenAt(int $accountId): ?string
    {
        $statement = $this->connection->prepare(
            'SELECT MAX(character_daily_allowance.last_motd_seen_at)
               FROM game_character
               JOIN character_daily_allowance
                    ON character_daily_allowance.character_id = game_character.character_id
              WHERE game_character.account_id = :account_id',
        );
        $statement->execute(['account_id' => $accountId]);

        $lastSeenAt = $statement->fetchColumn();

        return \is_string($lastSeenAt) && $lastSeenAt !== '' ? $lastSeenAt : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function vote(int $motdId, int $accountId, int $choiceIndex): array
    {
        $statement = $this->connection->prepare(
            'SELECT motd_type, body FROM message_of_the_day WHERE motd_id = :motd_id',
        );
        $statement->execute(['motd_id' => $motdId]);

        $row = $statement->fetch();

        if ($row === false) {
            return ['voted' => false, 'message_key' => 'motd.error.not-found'];
        }

        if ((int) $row['motd_type'] === self::TYPE_NOTICE) {
            return ['voted' => false, 'message_key' => 'motd.error.not-a-poll'];
        }

        $decodedBody = $this->decodePollBody((string) $row['body']);

        if ($choiceIndex < 0 || $choiceIndex >= \count($decodedBody['choice_list'])) {
            return ['voted' => false, 'message_key' => 'motd.error.invalid-choice'];
        }

        $this->connection
            ->prepare(
                'INSERT INTO poll_result (motd_id, account_id, choice_index)
                 VALUES (:motd_id, :account_id, :choice_index)
                 ON CONFLICT(motd_id, account_id)
                 DO UPDATE SET choice_index = excluded.choice_index',
            )
            ->execute([
                'motd_id' => $motdId,
                'account_id' => $accountId,
                'choice_index' => $choiceIndex,
            ]);

        return [
            'voted' => true,
            'poll_result' => $this->summarizePoll($motdId, $accountId),
        ];
    }

    public function markSeen(int $accountId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET last_motd_seen_at = datetime(\'now\')
                  WHERE character_id IN (
                        SELECT character_id FROM game_character WHERE account_id = :account_id
                  )',
            )
            ->execute(['account_id' => $accountId]);
    }

    private function hasUnseen(int $accountId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1
               FROM message_of_the_day
               JOIN game_character            ON game_character.account_id = :account_id
               JOIN character_daily_allowance ON character_daily_allowance.character_id = game_character.character_id
              WHERE character_daily_allowance.last_motd_seen_at IS NULL
                 OR message_of_the_day.posted_at > character_daily_allowance.last_motd_seen_at
              LIMIT 1',
        );
        $statement->execute(['account_id' => $accountId]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizePoll(int $motdId, int $accountId): array
    {
        $statement = $this->connection->prepare(
            'SELECT choice_index, COUNT(*) AS vote_count
               FROM poll_result
              WHERE motd_id = :motd_id
              GROUP BY choice_index
              ORDER BY choice_index ASC',
        );
        $statement->execute(['motd_id' => $motdId]);

        $countByChoice = [];
        $totalVote = 0;

        foreach ($statement->fetchAll() as $row) {
            $voteCount = (int) $row['vote_count'];
            $countByChoice[(string) (int) $row['choice_index']] = $voteCount;
            $totalVote += $voteCount;
        }

        $ownStatement = $this->connection->prepare(
            'SELECT choice_index FROM poll_result WHERE motd_id = :motd_id AND account_id = :account_id',
        );
        $ownStatement->execute(['motd_id' => $motdId, 'account_id' => $accountId]);

        $ownChoice = $ownStatement->fetchColumn();

        return [
            'count_by_choice' => (object) $countByChoice,
            'total_vote' => $totalVote,
            'own_choice' => $ownChoice === false ? null : (int) $ownChoice,
        ];
    }
}
