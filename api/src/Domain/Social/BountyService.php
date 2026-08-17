<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LegacyLikePatternBuilder;
use Lotdg\Support\LocalizedException;
use PDO;

final class BountyService
{
    private const int SEARCH_RESULT_LIMIT = 25;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly LegacyLikePatternBuilder $likePatternBuilder = new LegacyLikePatternBuilder(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function searchTarget(int $characterId, string $searchTerm): array
    {
        if (\trim($searchTerm) === '') {
            return ['search_term' => $searchTerm, 'candidate_list' => []];
        }

        $statement = $this->connection->prepare(
            'SELECT game_character.character_id,
                    game_character.display_name,
                    game_character.level,
                    character_wealth.bounty_on_self,
                    character_progression.game_age_day,
                    character_progression.dragon_kill_count,
                    character_progression.experience,
                    character_social.pvp_immunity_lost
               FROM game_character
               JOIN account               ON account.account_id = game_character.account_id
               JOIN character_wealth      ON character_wealth.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
               JOIN character_social      ON character_social.character_id = game_character.character_id
              WHERE account.is_locked = 0
                AND game_character.character_id <> :character_id
                AND game_character.display_name LIKE :pattern
              ORDER BY game_character.level DESC, game_character.display_name ASC
              LIMIT :limit',
        );
        $statement->bindValue('character_id', $characterId, PDO::PARAM_INT);
        $statement->bindValue('pattern', $this->likePatternBuilder->build($searchTerm));
        $statement->bindValue('limit', self::SEARCH_RESULT_LIMIT, PDO::PARAM_INT);
        $statement->execute();

        $maximumPerLevel = $this->gameSettingRepository->getInt('bountymax', 400);
        $minimumPerLevel = $this->gameSettingRepository->getInt('bountymin', 50);

        return [
            'search_term' => $searchTerm,
            'candidate_list' => \array_map(
                fn (array $row): array => [
                    'character_id' => (int) $row['character_id'],
                    'display_name' => (string) $row['display_name'],
                    'level' => (int) $row['level'],
                    'current_bounty' => (int) $row['bounty_on_self'],
                    'minimum_bounty' => $minimumPerLevel * (int) $row['level'],
                    'remaining_bounty' => \max(
                        0,
                        $maximumPerLevel * (int) $row['level'] - (int) $row['bounty_on_self'],
                    ),
                    'eligible' => $this->isEligibleTarget($row),
                ],
                $statement->fetchAll(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId): array
    {
        $row = $this->fetchBountyRow($characterId);

        return [
            'own_bounty' => (int) $row['bounty_on_self'],
            'bounty_set_today' => (int) $row['bounty_set_today'],
            'maximum_bounty_per_day' => $this->gameSettingRepository->getInt('maxbounties', 5),
            'listing_fee_percent' => $this->listingFeePercent(),
            'minimum_per_level' => $this->gameSettingRepository->getInt('bountymin', 50),
            'maximum_per_level' => $this->gameSettingRepository->getInt('bountymax', 400),
            'minimum_target_level' => $this->gameSettingRepository->getInt('bountylevel', 3),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function listBounty(): array
    {
        $statement = $this->connection->query(
            'SELECT game_character.character_id,
                    game_character.display_name,
                    game_character.level,
                    game_character.sex_code,
                    game_character.location_code,
                    character_vital.is_alive,
                    character_wealth.bounty_on_self,
                    account.is_logged_in,
                    account.last_seen_at
               FROM game_character
               JOIN account          ON account.account_id = game_character.account_id
               JOIN character_vital  ON character_vital.character_id = game_character.character_id
               JOIN character_wealth ON character_wealth.character_id = game_character.character_id
              WHERE character_wealth.bounty_on_self > 0
                AND account.is_locked = 0
              ORDER BY character_wealth.bounty_on_self DESC',
        );

        return [
            'bounty_list' => \array_map(
                static fn (array $row): array => [
                    'character_id' => (int) $row['character_id'],
                    'display_name' => (string) $row['display_name'],
                    'level' => (int) $row['level'],
                    'sex_code' => (int) $row['sex_code'],
                    'location_code' => (int) $row['location_code'],
                    'is_alive' => (int) $row['is_alive'] === 1,
                    'bounty' => (int) $row['bounty_on_self'],
                    'is_logged_in' => (int) $row['is_logged_in'] === 1,
                    'last_seen_at' => $row['last_seen_at'] === null ? null : (string) $row['last_seen_at'],
                ],
                $statement === false ? [] : $statement->fetchAll(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function placeBounty(int $characterId, int $targetCharacterId, int $amount): array
    {
        $row = $this->fetchBountyRow($characterId);

        if ((int) $row['bounty_set_today'] >= $this->gameSettingRepository->getInt('maxbounties', 5)) {
            return ['placed' => false, 'message_key' => 'bounty.error.daily-limit-reached'];
        }

        if ($characterId === $targetCharacterId) {
            return ['placed' => false, 'message_key' => 'bounty.error.self-target'];
        }

        $target = $this->fetchTargetRow($targetCharacterId);

        if ($target === null) {
            return ['placed' => false, 'message_key' => 'bounty.error.target-not-found'];
        }

        if (!$this->isEligibleTarget($target)) {
            return ['placed' => false, 'message_key' => 'bounty.error.target-not-eligible'];
        }

        $requestedAmount = \abs($amount);
        $minimum = $this->gameSettingRepository->getInt('bountymin', 50) * (int) $target['level'];
        $maximum = $this->gameSettingRepository->getInt('bountymax', 400) * (int) $target['level'];
        $totalCost = (int) \round($requestedAmount * (1 + $this->listingFeePercent() / 100));

        if ($requestedAmount < $minimum) {
            return [
                'placed' => false,
                'message_key' => 'bounty.error.amount-too-small',
                'minimum' => $minimum,
            ];
        }

        if ((int) $row['gold'] < $totalCost) {
            return [
                'placed' => false,
                'message_key' => 'bounty.error.not-enough-gold',
                'total_cost' => $totalCost,
            ];
        }

        if ($requestedAmount + (int) $target['bounty_on_self'] > $maximum) {
            return [
                'placed' => false,
                'message_key' => 'bounty.error.exceeds-target-maximum',
                'maximum' => $maximum,
                'current_bounty' => (int) $target['bounty_on_self'],
            ];
        }

        $this->connection->beginTransaction();

        try {
            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold             = gold - :total_cost,
                            bounty_set_today = bounty_set_today + 1
                      WHERE character_id = :character_id',
                )
                ->execute(['total_cost' => $totalCost, 'character_id' => $characterId]);

            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET bounty_on_self = bounty_on_self + :amount
                      WHERE character_id = :character_id',
                )
                ->execute(['amount' => $requestedAmount, 'character_id' => $targetCharacterId]);

            $this->connection->commit();
        } catch (\Throwable $throwable) {
            $this->connection->rollBack();

            throw $throwable;
        }

        return [
            'placed' => true,
            'target_display_name' => (string) $target['display_name'],
            'amount' => $requestedAmount,
            'listing_fee' => $totalCost - $requestedAmount,
            'total_cost' => $totalCost,
        ];
    }

    private function listingFeePercent(): int
    {
        $fee = $this->gameSettingRepository->getInt('bountyfee', 10);

        if ($fee < 0 || $fee > 100) {
            $this->gameSettingRepository->put('bountyfee', '10');

            return 10;
        }

        return $fee;
    }

    /**
     * @param array<string, mixed> $target
     */
    private function isEligibleTarget(array $target): bool
    {
        if ((int) $target['level'] < $this->gameSettingRepository->getInt('bountylevel', 3)) {
            return false;
        }

        $isImmune = (int) $target['game_age_day'] < $this->gameSettingRepository->getInt('pvpimmunity', 5)
            && (int) $target['dragon_kill_count'] === 0
            && (int) $target['pvp_immunity_lost'] === 0
            && (int) $target['experience'] < $this->gameSettingRepository->getInt('pvpminexp', 1500);

        return !$isImmune;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchTargetRow(int $targetCharacterId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.display_name,
                    game_character.level,
                    character_wealth.bounty_on_self,
                    character_progression.game_age_day,
                    character_progression.dragon_kill_count,
                    character_progression.experience,
                    character_social.pvp_immunity_lost
               FROM game_character
               JOIN account               ON account.account_id = game_character.account_id
               JOIN character_wealth      ON character_wealth.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
               JOIN character_social      ON character_social.character_id = game_character.character_id
              WHERE game_character.character_id = :character_id
                AND account.is_locked = 0',
        );
        $statement->execute(['character_id' => $targetCharacterId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchBountyRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT character_wealth.gold,
                    character_wealth.bounty_on_self,
                    character_wealth.bounty_set_today
               FROM game_character
               JOIN character_wealth ON character_wealth.character_id = game_character.character_id
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
