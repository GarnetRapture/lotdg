<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class WebVoteService
{
    public const int GEM_REWARD = 1;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId): array
    {
        $row = $this->fetchVoteRow($characterId);

        return [
            'enabled' => $this->gameSettingRepository->getInt('topwebid', 0) !== 0,
            'top_web_id' => $this->gameSettingRepository->getInt('topwebid', 0),
            'last_web_vote_date' => $row['last_web_vote_date'] === null
                ? null
                : (string) $row['last_web_vote_date'],
            'current_week' => $this->currentWeek(),
            'can_claim' => $this->canClaim($row['last_web_vote_date']),
            'gem_reward' => self::GEM_REWARD,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function claim(int $characterId): array
    {
        if ($this->gameSettingRepository->getInt('topwebid', 0) === 0) {
            return ['claimed' => false, 'message_key' => 'webvote.error.disabled'];
        }

        $row = $this->fetchVoteRow($characterId);

        if (!$this->canClaim($row['last_web_vote_date'])) {
            return ['claimed' => false, 'message_key' => 'webvote.error.already-claimed'];
        }

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_daily_allowance
                        SET last_web_vote_date = :vote_date
                      WHERE character_id = :character_id',
                )
                ->execute([
                    'vote_date' => \date('Y-m-d'),
                    'character_id' => $characterId,
                ]);

            $this->connection
                ->prepare(
                    'UPDATE character_wealth SET gem = gem + :gem WHERE character_id = :character_id',
                )
                ->execute(['gem' => self::GEM_REWARD, 'character_id' => $characterId]);

            $this->connection
                ->prepare(
                    'INSERT INTO debug_log (actor_account_id, target_account_id, message)
                     VALUES (:actor_account_id, :target_account_id, :message)',
                )
                ->execute([
                    'actor_account_id' => (int) $row['account_id'],
                    'target_account_id' => (int) $row['account_id'],
                    'message' => 'gained 1 gem for topwebgames',
                ]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return ['claimed' => true, 'gem_gained' => self::GEM_REWARD];
    }

    private function canClaim(mixed $lastVoteDate): bool
    {
        if (!\is_string($lastVoteDate) || $lastVoteDate === '') {
            return true;
        }

        $timestamp = \strtotime($lastVoteDate);

        if ($timestamp === false) {
            return true;
        }

        return \date('Y-W', $timestamp) < $this->currentWeek();
    }

    private function currentWeek(): string
    {
        return \date('Y-W');
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchVoteRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.account_id,
                    character_daily_allowance.last_web_vote_date
               FROM game_character
               JOIN character_daily_allowance
                    ON character_daily_allowance.character_id = game_character.character_id
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
