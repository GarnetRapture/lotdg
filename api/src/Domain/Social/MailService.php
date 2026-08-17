<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LegacyLikePatternBuilder;
use Lotdg\Support\LocalizedException;
use PDO;

final class MailService
{
    private const string REPLY_SUBJECT_PREFIX = 'RE: ';

    private const int RECIPIENT_SEARCH_LIMIT = 25;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly BadWordFilter $badWordFilter,
        private readonly LegacyLikePatternBuilder $likePatternBuilder = new LegacyLikePatternBuilder(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function searchRecipient(string $searchTerm): array
    {
        if (\trim($searchTerm) === '') {
            return ['search_term' => $searchTerm, 'candidate_list' => []];
        }

        $statement = $this->connection->prepare(
            'SELECT account.login_name,
                    game_character.display_name
               FROM account
               JOIN game_character ON game_character.account_id = account.account_id
              WHERE account.is_locked = 0
                AND game_character.display_name LIKE :pattern
              ORDER BY account.login_name ASC
              LIMIT :limit',
        );
        $statement->bindValue('pattern', $this->likePatternBuilder->build($searchTerm));
        $statement->bindValue('limit', self::RECIPIENT_SEARCH_LIMIT, PDO::PARAM_INT);
        $statement->execute();

        return [
            'search_term' => $searchTerm,
            'candidate_list' => \array_map(
                static fn (array $row): array => [
                    'login_name' => (string) $row['login_name'],
                    'display_name' => (string) $row['display_name'],
                ],
                $statement->fetchAll(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareReply(int $accountId, int $mailMessageId): array
    {
        $statement = $this->connection->prepare(
            'SELECT mail_message.subject,
                    mail_message.body,
                    mail_message.is_system_message,
                    sender_account.login_name        AS sender_login_name,
                    sender_character.display_name    AS sender_display_name
               FROM mail_message
               LEFT JOIN account        AS sender_account   ON sender_account.account_id = mail_message.sender_account_id
               LEFT JOIN game_character AS sender_character ON sender_character.account_id = sender_account.account_id
              WHERE mail_message.mail_message_id = :mail_message_id
                AND mail_message.recipient_account_id = :account_id',
        );
        $statement->execute([
            'mail_message_id' => $mailMessageId,
            'account_id' => $accountId,
        ]);

        $row = $statement->fetch();

        if ($row === false) {
            return ['prepared' => false, 'message_key' => 'mail.error.not-found'];
        }

        if ((int) $row['is_system_message'] === 1 || $row['sender_login_name'] === null) {
            return ['prepared' => false, 'message_key' => 'mail.error.reply-to-system'];
        }

        $subject = (string) $row['subject'];

        if (!\str_starts_with($subject, self::REPLY_SUBJECT_PREFIX)) {
            $subject = self::REPLY_SUBJECT_PREFIX . $subject;
        }

        return [
            'prepared' => true,
            'recipient_login_name' => (string) $row['sender_login_name'],
            'recipient_display_name' => (string) ($row['sender_display_name'] ?? ''),
            'subject' => $subject,
            'quoted_body' => (string) $row['body'],
        ];
    }

    /**
     * @param list<int> $mailMessageIdList
     *
     * @return array<string, mixed>
     */
    public function deleteMany(int $accountId, array $mailMessageIdList): array
    {
        $identifierList = \array_values(\array_filter(
            \array_map(static fn (mixed $value): int => (int) $value, $mailMessageIdList),
            static fn (int $value): bool => $value > 0,
        ));

        if ($identifierList === []) {
            return ['deleted_count' => 0, 'message_key' => 'mail.error.nothing-selected'];
        }

        $placeholderList = \implode(',', \array_fill(0, \count($identifierList), '?'));

        $statement = $this->connection->prepare(
            \sprintf(
                'DELETE FROM mail_message
                  WHERE recipient_account_id = ?
                    AND mail_message_id IN (%s)',
                $placeholderList,
            ),
        );
        $statement->execute([$accountId, ...$identifierList]);

        return ['deleted_count' => $statement->rowCount()];
    }

    /**
     * @return array<string, mixed>
     */
    public function listInbox(int $accountId): array
    {
        $this->expireOldMail();

        $statement = $this->connection->prepare(
            'SELECT mail_message.mail_message_id,
                    mail_message.subject,
                    mail_message.is_seen,
                    mail_message.is_system_message,
                    mail_message.sent_at,
                    sender_character.display_name AS sender_display_name
               FROM mail_message
               LEFT JOIN account          AS sender_account   ON sender_account.account_id = mail_message.sender_account_id
               LEFT JOIN game_character   AS sender_character ON sender_character.account_id = sender_account.account_id
              WHERE mail_message.recipient_account_id = :account_id
              ORDER BY mail_message.mail_message_id DESC',
        );
        $statement->execute(['account_id' => $accountId]);

        $messageList = [];
        $unseenCount = 0;

        foreach ($statement->fetchAll() as $row) {
            $isSeen = (int) $row['is_seen'] === 1;

            if (!$isSeen) {
                ++$unseenCount;
            }

            $messageList[] = [
                'mail_message_id' => (int) $row['mail_message_id'],
                'subject' => (string) $row['subject'],
                'sender_display_name' => (string) ($row['sender_display_name'] ?? ''),
                'is_system_message' => (int) $row['is_system_message'] === 1,
                'is_seen' => $isSeen,
                'sent_at' => (string) $row['sent_at'],
            ];
        }

        return [
            'message_list' => $messageList,
            'unseen_count' => $unseenCount,
            'seen_count' => \count($messageList) - $unseenCount,
            'inbox_limit' => $this->gameSettingRepository->getInt('inboxlimit', 50),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function read(int $accountId, int $mailMessageId): array
    {
        $statement = $this->connection->prepare(
            'SELECT mail_message.mail_message_id,
                    mail_message.subject,
                    mail_message.body,
                    mail_message.is_system_message,
                    mail_message.sent_at,
                    sender_character.display_name AS sender_display_name
               FROM mail_message
               LEFT JOIN account        AS sender_account   ON sender_account.account_id = mail_message.sender_account_id
               LEFT JOIN game_character AS sender_character ON sender_character.account_id = sender_account.account_id
              WHERE mail_message.mail_message_id = :mail_message_id
                AND mail_message.recipient_account_id = :account_id',
        );
        $statement->execute([
            'mail_message_id' => $mailMessageId,
            'account_id' => $accountId,
        ]);

        $row = $statement->fetch();

        if ($row === false) {
            return ['found' => false, 'message_key' => 'mail.error.not-found'];
        }

        $this->connection
            ->prepare('UPDATE mail_message SET is_seen = 1 WHERE mail_message_id = :mail_message_id')
            ->execute(['mail_message_id' => $mailMessageId]);

        return [
            'found' => true,
            'mail_message_id' => (int) $row['mail_message_id'],
            'subject' => (string) $row['subject'],
            'body' => (string) $row['body'],
            'sender_display_name' => (string) ($row['sender_display_name'] ?? ''),
            'is_system_message' => (int) $row['is_system_message'] === 1,
            'sent_at' => (string) $row['sent_at'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function send(int $senderAccountId, string $recipientLoginName, string $subject, string $body): array
    {
        $recipientAccountId = $this->resolveAccountIdByLoginName($recipientLoginName);

        if ($recipientAccountId === null) {
            return ['sent' => false, 'message_key' => 'mail.error.recipient-not-found'];
        }

        $sizeLimit = $this->gameSettingRepository->getInt('mailsizelimit', 1024);

        if (\mb_strlen($body) > $sizeLimit) {
            return [
                'sent' => false,
                'message_key' => 'mail.error.body-too-long',
                'size_limit' => $sizeLimit,
            ];
        }

        if ($this->countInbox($recipientAccountId) >= $this->gameSettingRepository->getInt('inboxlimit', 50)) {
            return ['sent' => false, 'message_key' => 'mail.error.recipient-inbox-full'];
        }

        $this->insertMessage(
            $senderAccountId,
            $recipientAccountId,
            $this->badWordFilter->clean(\str_replace(["\n", '`n'], '', $subject)),
            $this->badWordFilter->clean($body),
            false,
        );

        return ['sent' => true, 'recipient_account_id' => $recipientAccountId];
    }

    public function sendSystemMessage(int $recipientAccountId, string $subject, string $body): void
    {
        $this->insertMessage(null, $recipientAccountId, $subject, $body, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(int $accountId, int $mailMessageId): array
    {
        $statement = $this->connection->prepare(
            'DELETE FROM mail_message
              WHERE mail_message_id = :mail_message_id
                AND recipient_account_id = :account_id',
        );
        $statement->execute([
            'mail_message_id' => $mailMessageId,
            'account_id' => $accountId,
        ]);

        return ['deleted' => $statement->rowCount() > 0];
    }

    private function insertMessage(
        ?int $senderAccountId,
        int $recipientAccountId,
        string $subject,
        string $body,
        bool $isSystemMessage,
    ): void {
        $this->connection
            ->prepare(
                'INSERT INTO mail_message
                     (sender_account_id, recipient_account_id, subject, body, is_system_message)
                 VALUES
                     (:sender_account_id, :recipient_account_id, :subject, :body, :is_system_message)',
            )
            ->execute([
                'sender_account_id' => $senderAccountId,
                'recipient_account_id' => $recipientAccountId,
                'subject' => $subject,
                'body' => $body,
                'is_system_message' => $isSystemMessage ? 1 : 0,
            ]);
    }

    private function countInbox(int $accountId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM mail_message WHERE recipient_account_id = :account_id',
        );
        $statement->execute(['account_id' => $accountId]);

        return (int) $statement->fetchColumn();
    }

    private function resolveAccountIdByLoginName(string $loginName): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT account_id FROM account WHERE login_name = :login_name AND is_locked = 0',
        );
        $statement->execute(['login_name' => $loginName]);

        $accountId = $statement->fetchColumn();

        return $accountId === false ? null : (int) $accountId;
    }

    private function expireOldMail(): void
    {
        $expireDay = $this->gameSettingRepository->getInt('oldmail', 45);

        if ($expireDay <= 0) {
            return;
        }

        $this->connection
            ->prepare('DELETE FROM mail_message WHERE sent_at < datetime(\'now\', :expire_offset)')
            ->execute(['expire_offset' => \sprintf('-%d days', $expireDay)]);
    }

    public function requireAccountId(int $characterId): int
    {
        $statement = $this->connection->prepare(
            'SELECT account_id FROM game_character WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $accountId = $statement->fetchColumn();

        if ($accountId === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return (int) $accountId;
    }
}
