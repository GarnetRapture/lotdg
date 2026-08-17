<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use PDO;

final class PetitionService
{
    public const int STATUS_UNSEEN = 0;

    public const int STATUS_SEEN = 1;

    public const int STATUS_CLOSED = 2;

    public const string COMMENTARY_SECTION_PREFIX = 'pet-';

    private const int CLOSED_RETENTION_DAY = 7;

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function view(int $petitionId): array
    {
        $statement = $this->connection->prepare(
            'SELECT petition.petition_id,
                    petition.status_code,
                    petition.body,
                    petition.page_info_json,
                    petition.submitted_at,
                    account.login_name,
                    game_character.display_name
               FROM petition
               LEFT JOIN account        ON account.account_id = petition.author_account_id
               LEFT JOIN game_character ON game_character.account_id = account.account_id
              WHERE petition.petition_id = :petition_id',
        );
        $statement->execute(['petition_id' => $petitionId]);

        $row = $statement->fetch();

        if ($row === false) {
            return ['found' => false, 'message_key' => 'petition.error.not-found'];
        }

        if ((int) $row['status_code'] === self::STATUS_UNSEEN) {
            $this->updateStatus($petitionId, self::STATUS_SEEN);
        }

        return [
            'found' => true,
            'petition_id' => (int) $row['petition_id'],
            'status_code' => (int) $row['status_code'] === self::STATUS_UNSEEN
                ? self::STATUS_SEEN
                : (int) $row['status_code'],
            'body' => (string) $row['body'],
            'page_info_json' => (string) $row['page_info_json'],
            'login_name' => (string) ($row['login_name'] ?? ''),
            'display_name' => (string) ($row['display_name'] ?? ''),
            'submitted_at' => (string) $row['submitted_at'],
            'commentary_section_code' => self::COMMENTARY_SECTION_PREFIX . $petitionId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(int $petitionId): array
    {
        $this->connection->beginTransaction();

        try {
            $statement = $this->connection->prepare(
                'DELETE FROM petition WHERE petition_id = :petition_id',
            );
            $statement->execute(['petition_id' => $petitionId]);

            $this->connection
                ->prepare('DELETE FROM commentary WHERE section_code = :section_code')
                ->execute(['section_code' => self::COMMENTARY_SECTION_PREFIX . $petitionId]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return ['removed' => $statement->rowCount() > 0];
    }

    private function purgeClosedPetition(): void
    {
        $statement = $this->connection->prepare(
            'SELECT petition_id
               FROM petition
              WHERE status_code = :status_code
                AND submitted_at < datetime(\'now\', :expire_offset)',
        );
        $statement->execute([
            'status_code' => self::STATUS_CLOSED,
            'expire_offset' => \sprintf('-%d days', self::CLOSED_RETENTION_DAY),
        ]);

        foreach ($statement->fetchAll() as $row) {
            $this->remove((int) $row['petition_id']);
        }
    }

    /**
     * @param array<string, mixed> $pageInfo
     *
     * @return array<string, mixed>
     */
    public function submit(?int $accountId, string $body, array $pageInfo = []): array
    {
        $trimmedBody = \trim($body);

        if ($trimmedBody === '') {
            return ['submitted' => false, 'message_key' => 'petition.error.empty-body'];
        }

        $encodedPageInfo = \json_encode($pageInfo, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        $this->connection
            ->prepare(
                'INSERT INTO petition (author_account_id, body, page_info_json)
                 VALUES (:author_account_id, :body, :page_info_json)',
            )
            ->execute([
                'author_account_id' => $accountId,
                'body' => $trimmedBody,
                'page_info_json' => $encodedPageInfo === false ? '{}' : $encodedPageInfo,
            ]);

        return ['submitted' => true, 'petition_id' => (int) $this->connection->lastInsertId()];
    }

    /**
     * @return array<string, mixed>
     */
    public function listByStatus(?int $statusCode = null): array
    {
        $this->purgeClosedPetition();

        $sql = 'SELECT petition.petition_id,
                       petition.status_code,
                       petition.body,
                       petition.submitted_at,
                       game_character.display_name,
                       (SELECT COUNT(*)
                          FROM commentary
                         WHERE commentary.section_code = \'' . self::COMMENTARY_SECTION_PREFIX . '\' || petition.petition_id
                       ) AS comment_count
                  FROM petition
                  LEFT JOIN account        ON account.account_id = petition.author_account_id
                  LEFT JOIN game_character ON game_character.account_id = account.account_id';

        if ($statusCode !== null) {
            $sql .= ' WHERE petition.status_code = :status_code';
        }

        $sql .= ' ORDER BY petition.status_code ASC, petition.submitted_at ASC';

        $statement = $this->connection->prepare($sql);
        $statement->execute($statusCode === null ? [] : ['status_code' => $statusCode]);

        return [
            'petition_list' => \array_map(
                static fn (array $row): array => [
                    'petition_id' => (int) $row['petition_id'],
                    'status_code' => (int) $row['status_code'],
                    'body' => (string) $row['body'],
                    'display_name' => (string) ($row['display_name'] ?? ''),
                    'submitted_at' => (string) $row['submitted_at'],
                    'comment_count' => (int) $row['comment_count'],
                ],
                $statement->fetchAll(),
            ),
            'status_summary' => $this->summarizeStatus(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateStatus(int $petitionId, int $statusCode): array
    {
        if (!\in_array($statusCode, [self::STATUS_UNSEEN, self::STATUS_SEEN, self::STATUS_CLOSED], true)) {
            return ['updated' => false, 'message_key' => 'petition.error.invalid-status'];
        }

        $statement = $this->connection->prepare(
            'UPDATE petition SET status_code = :status_code WHERE petition_id = :petition_id',
        );
        $statement->execute([
            'status_code' => $statusCode,
            'petition_id' => $petitionId,
        ]);

        return ['updated' => $statement->rowCount() > 0, 'status_code' => $statusCode];
    }

    /**
     * @return array<string, int>
     */
    public function summarizeStatus(): array
    {
        $statement = $this->connection->query(
            'SELECT status_code, COUNT(*) AS petition_count FROM petition GROUP BY status_code',
        );

        $summary = ['unseen' => 0, 'seen' => 0, 'closed' => 0];

        foreach ($statement === false ? [] : $statement->fetchAll() as $row) {
            $key = match ((int) $row['status_code']) {
                self::STATUS_SEEN => 'seen',
                self::STATUS_CLOSED => 'closed',
                default => 'unseen',
            };

            $summary[$key] = (int) $row['petition_count'];
        }

        return $summary;
    }
}
