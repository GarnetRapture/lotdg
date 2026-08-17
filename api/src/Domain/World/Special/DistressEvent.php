<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Domain\Social\NewsService;
use Lotdg\Support\LocalizedException;
use PDO;

final class DistressEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'distress';

    public const int LOCATION_WYVERN_KEEP = 1;

    public const int LOCATION_CASTLE_SLAAG = 2;

    public const int LOCATION_DRACO_DUNGEON = 3;

    public const string OUTCOME_RESCUE = 'rescue';

    public const string OUTCOME_TROLL = 'troll';

    public const string OUTCOME_HAG = 'hag';

    public const string OUTCOME_FOP = 'fop';

    public const string OUTCOME_DEATH = 'death';

    public const string OUTCOME_ESCAPE = 'escape';

    public const string OUTCOME_FINED = 'fined';

    private const int RACE_TROLL = 1;

    private const float ESCAPE_HIT_POINT_RATE = 0.1;

    private const float EXPERIENCE_BONUS_RATE = 1.1;

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

        return [
            'stage' => 'awaiting-choice',
            'location_code_list' => [
                self::LOCATION_WYVERN_KEEP,
                self::LOCATION_CASTLE_SLAAG,
                self::LOCATION_DRACO_DUNGEON,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function ignore(int $characterId): array
    {
        $this->eventState->clear($characterId);

        return ['stage' => 'ignored'];
    }

    /**
     * @return array<string, mixed>
     */
    public function visit(int $characterId, int $locationCode): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-choice') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        if (!\in_array($locationCode, [
            self::LOCATION_WYVERN_KEEP,
            self::LOCATION_CASTLE_SLAAG,
            self::LOCATION_DRACO_DUNGEON,
        ], true)) {
            return ['stage' => 'invalid', 'message_key' => 'special.distress.error.unknown-location'];
        }

        $row = $this->fetchCharacterRow($characterId);
        $this->eventState->clear($characterId);

        $roll = \random_int(1, 10);

        if ($roll <= 4) {
            return $this->applyRescue($characterId, $row, $locationCode);
        }

        return match ($roll) {
            5 => $this->applyTroll($characterId, $row, $locationCode),
            6 => $this->applyHag($characterId, $locationCode),
            7 => $this->resolved(self::OUTCOME_FOP, $locationCode, []),
            8 => $this->applyDeath($characterId, $row, $locationCode),
            9 => $this->applyEscape($characterId, $row, $locationCode),
            default => $this->applyFine($characterId, $locationCode),
        };
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function applyRescue(int $characterId, array $row, int $locationCode): array
    {
        return match (\random_int(1, 5)) {
            1 => $this->grantGem($characterId, $locationCode),
            2 => $this->grantGold($characterId, $row, $locationCode),
            3 => $this->grantExperience($characterId, $row, $locationCode),
            4 => $this->grantCharm($characterId, $locationCode),
            default => $this->grantTurnAndHeal($characterId, $locationCode),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function grantGem(int $characterId, int $locationCode): array
    {
        $gemGained = \random_int(1, 2);
        $this->adjustWealth($characterId, 0, $gemGained);

        return $this->resolved(self::OUTCOME_RESCUE, $locationCode, [
            'reward' => 'gem',
            'gem_gained' => $gemGained,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function grantGold(int $characterId, array $row, int $locationCode): array
    {
        $goldGained = \random_int(1, \max(1, (int) $row['level'] * 30));
        $this->adjustWealth($characterId, $goldGained, 0);

        return $this->resolved(self::OUTCOME_RESCUE, $locationCode, [
            'reward' => 'gold',
            'gold_gained' => $goldGained,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function grantExperience(int $characterId, array $row, int $locationCode): array
    {
        $newExperience = (int) \round((int) $row['experience'] * self::EXPERIENCE_BONUS_RATE);

        $this->connection
            ->prepare(
                'UPDATE character_progression SET experience = :experience WHERE character_id = :character_id',
            )
            ->execute(['experience' => $newExperience, 'character_id' => $characterId]);

        return $this->resolved(self::OUTCOME_RESCUE, $locationCode, [
            'reward' => 'experience',
            'experience_gained' => $newExperience - (int) $row['experience'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function grantCharm(int $characterId, int $locationCode): array
    {
        $this->adjustCharm($characterId, 2);

        return $this->resolved(self::OUTCOME_RESCUE, $locationCode, [
            'reward' => 'charm',
            'charm_gained' => 2,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function grantTurnAndHeal(int $characterId, int $locationCode): array
    {
        $this->adjustTurn($characterId, 1);

        $this->connection
            ->prepare(
                'UPDATE character_vital SET hit_point = max_hit_point WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return $this->resolved(self::OUTCOME_RESCUE, $locationCode, [
            'reward' => 'turn-and-heal',
            'turn_gained' => 1,
            'healed' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function applyTroll(int $characterId, array $row, int $locationCode): array
    {
        if ((int) $row['race_code'] === self::RACE_TROLL) {
            $this->adjustTurn($characterId, 1);
            $this->adjustCharm($characterId, 1);

            return $this->resolved(self::OUTCOME_TROLL, $locationCode, [
                'is_troll_race' => true,
                'turn_gained' => 1,
                'charm_gained' => 1,
            ]);
        }

        $this->adjustTurn($characterId, -1);
        $this->adjustCharm($characterId, -1);

        return $this->resolved(self::OUTCOME_TROLL, $locationCode, [
            'is_troll_race' => false,
            'turn_lost' => 1,
            'charm_lost' => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function applyHag(int $characterId, int $locationCode): array
    {
        $this->adjustCharm($characterId, -1);

        return $this->resolved(self::OUTCOME_HAG, $locationCode, ['charm_lost' => 1]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function applyDeath(int $characterId, array $row, int $locationCode): array
    {
        $this->connection
            ->prepare(
                'UPDATE character_vital
                    SET is_alive       = 0,
                        hit_point      = 0,
                        killed_in_area = :killed_in_area
                  WHERE character_id = :character_id',
            )
            ->execute(['killed_in_area' => 'distress', 'character_id' => $characterId]);

        $this->newsService->publish(
            \sprintf('`%%%s`3 special.distress.news.slain', (string) $row['display_name']),
            (int) $row['account_id'],
        );

        return $this->resolved(self::OUTCOME_DEATH, $locationCode, []);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function applyEscape(int $characterId, array $row, int $locationCode): array
    {
        $this->adjustTurn($characterId, -1);

        $reducedHitPoint = \max(1, (int) \round((int) $row['hit_point'] * self::ESCAPE_HIT_POINT_RATE));

        $this->connection
            ->prepare(
                'UPDATE character_vital SET hit_point = :hit_point WHERE character_id = :character_id',
            )
            ->execute(['hit_point' => $reducedHitPoint, 'character_id' => $characterId]);

        return $this->resolved(self::OUTCOME_ESCAPE, $locationCode, [
            'turn_lost' => 1,
            'hit_point' => $reducedHitPoint,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function applyFine(int $characterId, int $locationCode): array
    {
        $this->connection
            ->prepare('UPDATE character_wealth SET gold = 0 WHERE character_id = :character_id')
            ->execute(['character_id' => $characterId]);

        return $this->resolved(self::OUTCOME_FINED, $locationCode, ['gold_lost_all' => true]);
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function resolved(string $outcome, int $locationCode, array $extra): array
    {
        return [
            'stage' => 'resolved',
            'outcome' => $outcome,
            'location_code' => $locationCode,
        ] + $extra;
    }

    private function adjustWealth(int $characterId, int $goldDelta, int $gemDelta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gold = MAX(0, gold + :gold_delta),
                        gem  = MAX(0, gem + :gem_delta)
                  WHERE character_id = :character_id',
            )
            ->execute([
                'gold_delta' => $goldDelta,
                'gem_delta' => $gemDelta,
                'character_id' => $characterId,
            ]);
    }

    private function adjustTurn(int $characterId, int $delta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance
                    SET forest_turn = MAX(0, forest_turn + :delta)
                  WHERE character_id = :character_id',
            )
            ->execute(['delta' => $delta, 'character_id' => $characterId]);
    }

    private function adjustCharm(int $characterId, int $delta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_social
                    SET charm = MAX(0, charm + :delta)
                  WHERE character_id = :character_id',
            )
            ->execute(['delta' => $delta, 'character_id' => $characterId]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCharacterRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.account_id,
                    game_character.display_name,
                    game_character.level,
                    game_character.race_code,
                    character_vital.hit_point,
                    character_progression.experience
               FROM game_character
               JOIN character_vital       ON character_vital.character_id = game_character.character_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
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
