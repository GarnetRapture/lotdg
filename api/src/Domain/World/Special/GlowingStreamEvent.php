<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Domain\Social\NewsService;
use Lotdg\Support\LocalizedException;
use PDO;

final class GlowingStreamEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'glowingstream';

    public const string OUTCOME_DEATH = 'death';

    public const string OUTCOME_NEAR_DEATH = 'near-death';

    public const string OUTCOME_FULL_HEAL_AND_TURN = 'full-heal-and-turn';

    public const string OUTCOME_GEM = 'gem';

    public const string OUTCOME_TURN = 'turn';

    public const string OUTCOME_FULL_HEAL = 'full-heal';

    private const float NEAR_DEATH_HIT_POINT_RATE = 0.1;

    public function __construct(
        private readonly PDO $connection,
        private readonly SpecialEventState $eventState,
        private readonly NewsService $newsService,
    ) {
    }

    public function eventCode(): string
    {
        return self::EVENT_CODE;
    }

    /**
     * @return array<string, mixed>
     */
    public function start(int $characterId): array
    {
        $this->eventState->store($characterId, self::EVENT_CODE, ['stage' => 'awaiting-choice']);

        return ['stage' => 'awaiting-choice'];
    }

    /**
     * @return array<string, mixed>
     */
    public function decline(int $characterId): array
    {
        $this->eventState->clear($characterId);

        return ['stage' => 'declined'];
    }

    /**
     * @return array<string, mixed>
     */
    public function drink(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-choice') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $row = $this->fetchCharacterRow($characterId);
        $this->eventState->clear($characterId);

        return match (\random_int(1, 10)) {
            1 => $this->applyDeath($characterId, $row),
            2 => $this->applyNearDeath($characterId, $row),
            3 => $this->applyFullHealAndTurn($characterId),
            4 => $this->applyGem($characterId),
            5, 6, 7 => $this->applyTurn($characterId),
            default => $this->applyFullHeal($characterId),
        };
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function applyDeath(int $characterId, array $row): array
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET is_alive       = 0,
                        hit_point      = 0,
                        killed_in_area = :killed_in_area
                  WHERE character_id = :character_id',
            )
            ->execute(['killed_in_area' => 'forest', 'character_id' => $characterId]);

        $this->newsService->publish(
            \sprintf('`2%s`0 special.glowing-stream.news.death', (string) $row['display_name']),
            (int) $row['account_id'],
        );

        return ['stage' => 'resolved', 'outcome' => self::OUTCOME_DEATH];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function applyNearDeath(int $characterId, array $row): array
    {
        $reducedHitPoint = \max(1, (int) \round((int) $row['hit_point'] * self::NEAR_DEATH_HIT_POINT_RATE));

        $this->connection
            ->prepare(
                'UPDATE character_vital SET hit_point = :hit_point WHERE character_id = :character_id',
            )
            ->execute(['hit_point' => $reducedHitPoint, 'character_id' => $characterId]);

        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn = MAX(0, forest_turn - 1)
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return [
            'stage' => 'resolved',
            'outcome' => self::OUTCOME_NEAR_DEATH,
            'hit_point' => $reducedHitPoint,
            'turn_lost' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyFullHealAndTurn(int $characterId): array
    {
        $this->restoreFullHitPoint($characterId);
        $this->grantTurn($characterId, 1);

        return [
            'stage' => 'resolved',
            'outcome' => self::OUTCOME_FULL_HEAL_AND_TURN,
            'turn_gained' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyGem(int $characterId): array
    {
        $this->connection
            ->prepare('UPDATE character_wealth SET gem = gem + 1 WHERE character_id = :character_id')
            ->execute(['character_id' => $characterId]);

        return ['stage' => 'resolved', 'outcome' => self::OUTCOME_GEM, 'gem_gained' => 1];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyTurn(int $characterId): array
    {
        $this->grantTurn($characterId, 1);

        return ['stage' => 'resolved', 'outcome' => self::OUTCOME_TURN, 'turn_gained' => 1];
    }

    /**
     * @return array<string, mixed>
     */
    private function applyFullHeal(int $characterId): array
    {
        $this->restoreFullHitPoint($characterId);

        return ['stage' => 'resolved', 'outcome' => self::OUTCOME_FULL_HEAL];
    }

    private function restoreFullHitPoint(int $characterId): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET hit_point = max_hit_point
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);
    }

    private function grantTurn(int $characterId, int $turnCount): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn = forest_turn + :turn_count
                  WHERE character_id = :character_id',
            )
            ->execute(['turn_count' => $turnCount, 'character_id' => $characterId]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCharacterRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.account_id,
                    game_character.display_name,
                    character_vital.hit_point,
                    character_vital.max_hit_point
               FROM game_character
               JOIN character_vital ON character_vital.character_id = game_character.character_id
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
