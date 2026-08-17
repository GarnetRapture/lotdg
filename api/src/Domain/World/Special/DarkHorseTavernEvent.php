<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Domain\Social\CommentaryService;
use Lotdg\Support\LegacyLikePatternBuilder;
use Lotdg\Support\LocalizedException;
use PDO;

final class DarkHorseTavernEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'darkhorse';

    public const string SECTION_CODE = 'darkhorse';

    public const string SIDE_LIKE_PAIR = 'likepair';

    public const string SIDE_UNLIKE_PAIR = 'unlikepair';

    public const int ENEMY_LOOKUP_COST = 100;

    public const int STONE_TARGET_SCORE = 8;

    private const int RED_STONE_COUNT = 6;

    private const int BLUE_STONE_COUNT = 10;

    private const int DICE_MAXIMUM_ROLL = 3;

    private const int SEARCH_RESULT_LIMIT = 100;

    private const int COMMENT_LIMIT = 25;

    public function __construct(
        private readonly PDO $connection,
        private readonly SpecialEventState $eventState,
        private readonly CommentaryService $commentaryService,
        private readonly LegacyLikePatternBuilder $likePatternBuilder = new LegacyLikePatternBuilder(),
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
        $this->eventState->store($characterId, self::EVENT_CODE, ['stage' => 'outside']);

        return ['stage' => 'outside'];
    }

    /**
     * @return array<string, mixed>
     */
    public function enter(int $characterId): array
    {
        $this->eventState->store($characterId, self::EVENT_CODE, ['stage' => 'inside']);

        return ['stage' => 'inside'];
    }

    /**
     * @return array<string, mixed>
     */
    public function leave(int $characterId): array
    {
        $this->eventState->clear($characterId);

        return ['stage' => 'left'];
    }

    /**
     * @return array<string, mixed>
     */
    public function viewEtching(int $characterId): array
    {
        $board = $this->commentaryService->listBySection(
            $characterId,
            self::SECTION_CODE,
            self::COMMENT_LIMIT,
            0,
        );

        return [
            'stage' => 'etching',
            'section_code' => self::SECTION_CODE,
            'comment_list' => $board['comment_list'],
            'post_quota_remaining' => $board['post_quota_remaining'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function postEtching(int $characterId, string $commentText): array
    {
        return $this->commentaryService->post(
            $characterId,
            self::SECTION_CODE,
            $commentText,
            self::COMMENT_LIMIT,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function searchEnemy(int $characterId, string $searchTerm): array
    {
        unset($characterId);

        if (\trim($searchTerm) === '') {
            return ['search_term' => $searchTerm, 'candidate_list' => [], 'truncated' => false];
        }

        $statement = $this->connection->prepare(
            'SELECT account.login_name,
                    game_character.display_name,
                    game_character.level
               FROM game_character
               JOIN account ON account.account_id = game_character.account_id
              WHERE account.is_locked = 0
                AND game_character.display_name LIKE :pattern
              ORDER BY game_character.level DESC
              LIMIT :limit',
        );
        $statement->bindValue('pattern', $this->likePatternBuilder->build($searchTerm));
        $statement->bindValue('limit', self::SEARCH_RESULT_LIMIT + 1, PDO::PARAM_INT);
        $statement->execute();

        $rowList = $statement->fetchAll();
        $isTruncated = \count($rowList) > self::SEARCH_RESULT_LIMIT;

        if ($isTruncated) {
            $rowList = \array_slice($rowList, 0, self::SEARCH_RESULT_LIMIT);
        }

        return [
            'search_term' => $searchTerm,
            'truncated' => $isTruncated,
            'lookup_cost' => self::ENEMY_LOOKUP_COST,
            'candidate_list' => \array_map(
                static fn (array $row): array => [
                    'login_name' => (string) $row['login_name'],
                    'display_name' => (string) $row['display_name'],
                    'level' => (int) $row['level'],
                ],
                $rowList,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inspectEnemy(int $characterId, string $targetLoginName): array
    {
        $row = $this->fetchCharacterRow($characterId);

        if ((int) $row['gold'] < self::ENEMY_LOOKUP_COST) {
            return ['inspected' => false, 'message_key' => 'special.dark-horse.error.not-enough-gold'];
        }

        $statement = $this->connection->prepare(
            'SELECT game_character.display_name,
                    game_character.level,
                    character_vital.max_hit_point,
                    character_wealth.gold,
                    character_equipment.weapon_name,
                    character_equipment.armor_name,
                    character_combat_stat.attack_point,
                    character_combat_stat.defence_point
               FROM account
               JOIN game_character        ON game_character.account_id = account.account_id
               JOIN character_vital       ON character_vital.character_id = game_character.character_id
               JOIN character_wealth      ON character_wealth.character_id = game_character.character_id
               JOIN character_equipment   ON character_equipment.character_id = game_character.character_id
               JOIN character_combat_stat ON character_combat_stat.character_id = game_character.character_id
              WHERE account.login_name = :login_name
                AND account.is_locked = 0',
        );
        $statement->execute(['login_name' => $targetLoginName]);

        $targetRow = $statement->fetch();

        if ($targetRow === false) {
            return ['inspected' => false, 'message_key' => 'special.dark-horse.error.target-not-found'];
        }

        $this->adjustGold($characterId, -self::ENEMY_LOOKUP_COST);

        return [
            'inspected' => true,
            'cost' => self::ENEMY_LOOKUP_COST,
            'display_name' => (string) $targetRow['display_name'],
            'level' => (int) $targetRow['level'],
            'max_hit_point' => (int) $targetRow['max_hit_point'],
            'gold' => (int) $targetRow['gold'],
            'weapon_name' => (string) $targetRow['weapon_name'],
            'armor_name' => (string) $targetRow['armor_name'],
            'attack_point' => (int) $targetRow['attack_point'],
            'defence_point' => (int) $targetRow['defence_point'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function startStoneGame(int $characterId, string $side, int $betAmount): array
    {
        if (!\in_array($side, [self::SIDE_LIKE_PAIR, self::SIDE_UNLIKE_PAIR], true)) {
            return ['started' => false, 'message_key' => 'special.dark-horse.error.unknown-side'];
        }

        $row = $this->fetchCharacterRow($characterId);
        $bet = \min((int) $row['gold'], \abs($betAmount));

        if ($bet <= 0) {
            return ['started' => false, 'message_key' => 'special.dark-horse.error.zero-bet'];
        }

        $this->eventState->store($characterId, self::EVENT_CODE, [
            'stage' => 'stone-game',
            'side' => $side,
            'bet' => $bet,
            'red' => self::RED_STONE_COUNT,
            'blue' => self::BLUE_STONE_COUNT,
            'player_score' => 0,
            'old_man_score' => 0,
        ]);

        return [
            'started' => true,
            'side' => $side,
            'bet' => $bet,
            'red' => self::RED_STONE_COUNT,
            'blue' => self::BLUE_STONE_COUNT,
            'player_score' => 0,
            'old_man_score' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function drawStone(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'stone-game') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $red = (int) $state['red'];
        $blue = (int) $state['blue'];
        $playerScore = (int) $state['player_score'];
        $oldManScore = (int) $state['old_man_score'];
        $bet = (int) $state['bet'];

        if ($red + $blue < 2 || $playerScore > self::STONE_TARGET_SCORE || $oldManScore > self::STONE_TARGET_SCORE) {
            return $this->settleStoneGame($characterId, $playerScore, $oldManScore, $bet);
        }

        $drawnList = [];

        for ($index = 0; $index < 2; ++$index) {
            $roll = \random_int(1, $red + $blue);

            if ($roll <= $red) {
                $drawnList[] = 'red';
                --$red;

                continue;
            }

            $drawnList[] = 'blue';
            --$blue;
        }

        $isLikePair = $drawnList[0] === $drawnList[1];
        $playerWinsRound = ($state['side'] === self::SIDE_LIKE_PAIR) === $isLikePair;

        if ($playerWinsRound) {
            $playerScore += 2;
        } else {
            $oldManScore += 2;
        }

        if ($red + $blue < 2 || $playerScore > self::STONE_TARGET_SCORE || $oldManScore > self::STONE_TARGET_SCORE) {
            return $this->settleStoneGame($characterId, $playerScore, $oldManScore, $bet)
                + ['drawn_list' => $drawnList, 'red' => $red, 'blue' => $blue];
        }

        $state['red'] = $red;
        $state['blue'] = $blue;
        $state['player_score'] = $playerScore;
        $state['old_man_score'] = $oldManScore;
        $this->eventState->store($characterId, self::EVENT_CODE, $state);

        return [
            'stage' => 'stone-game',
            'drawn_list' => $drawnList,
            'player_score' => $playerScore,
            'old_man_score' => $oldManScore,
            'red' => $red,
            'blue' => $blue,
            'bet' => $bet,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function settleStoneGame(int $characterId, int $playerScore, int $oldManScore, int $bet): array
    {
        $this->eventState->store($characterId, self::EVENT_CODE, ['stage' => 'inside']);

        if ($playerScore > $oldManScore) {
            $this->adjustGold($characterId, $bet);

            return [
                'stage' => 'stone-settled',
                'outcome' => 'won',
                'gold_gained' => $bet,
                'player_score' => $playerScore,
                'old_man_score' => $oldManScore,
            ];
        }

        if ($playerScore < $oldManScore) {
            $this->adjustGold($characterId, -$bet);

            return [
                'stage' => 'stone-settled',
                'outcome' => 'lost',
                'gold_lost' => $bet,
                'player_score' => $playerScore,
                'old_man_score' => $oldManScore,
            ];
        }

        return [
            'stage' => 'stone-settled',
            'outcome' => 'draw',
            'player_score' => $playerScore,
            'old_man_score' => $oldManScore,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function startDiceGame(int $characterId, int $betAmount): array
    {
        $row = $this->fetchCharacterRow($characterId);
        $bet = \abs($betAmount);

        if ($bet <= 0) {
            return ['started' => false, 'message_key' => 'special.dark-horse.error.zero-bet'];
        }

        if ($bet > (int) $row['gold']) {
            return ['started' => false, 'message_key' => 'special.dark-horse.error.bet-too-large'];
        }

        $roll = \random_int(1, 6);

        $this->eventState->store($characterId, self::EVENT_CODE, [
            'stage' => 'dice-game',
            'bet' => $bet,
            'roll' => $roll,
            'roll_count' => 1,
        ]);

        return [
            'stage' => 'dice-game',
            'bet' => $bet,
            'roll' => $roll,
            'roll_count' => 1,
            'can_reroll' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rerollDice(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'dice-game') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $rollCount = (int) $state['roll_count'];

        if ($rollCount >= self::DICE_MAXIMUM_ROLL) {
            return ['stage' => 'dice-game', 'message_key' => 'special.dark-horse.error.no-reroll-left'];
        }

        $roll = \random_int(1, 6);
        ++$rollCount;

        $state['roll'] = $roll;
        $state['roll_count'] = $rollCount;
        $this->eventState->store($characterId, self::EVENT_CODE, $state);

        return [
            'stage' => 'dice-game',
            'bet' => (int) $state['bet'],
            'roll' => $roll,
            'roll_count' => $rollCount,
            'can_reroll' => $rollCount < self::DICE_MAXIMUM_ROLL,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function keepDice(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'dice-game') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $playerRoll = (int) $state['roll'];
        $bet = (int) $state['bet'];
        $this->eventState->store($characterId, self::EVENT_CODE, ['stage' => 'inside']);

        $oldManRollList = [];
        $oldManRoll = \random_int(1, 6);
        $oldManRollList[] = $oldManRoll;

        if ($oldManRoll <= $playerRoll && $oldManRoll !== 6) {
            $oldManRoll = \random_int(1, 6);
            $oldManRollList[] = $oldManRoll;

            if ($oldManRoll < $playerRoll) {
                $oldManRoll = \random_int(1, 6);
                $oldManRollList[] = $oldManRoll;
            }
        }

        if ($oldManRoll > $playerRoll) {
            $this->adjustGold($characterId, -$bet);

            return [
                'stage' => 'dice-settled',
                'outcome' => 'lost',
                'player_roll' => $playerRoll,
                'old_man_roll_list' => $oldManRollList,
                'gold_lost' => $bet,
            ];
        }

        if ($oldManRoll === $playerRoll) {
            return [
                'stage' => 'dice-settled',
                'outcome' => 'draw',
                'player_roll' => $playerRoll,
                'old_man_roll_list' => $oldManRollList,
            ];
        }

        $this->adjustGold($characterId, $bet);

        return [
            'stage' => 'dice-settled',
            'outcome' => 'won',
            'player_roll' => $playerRoll,
            'old_man_roll_list' => $oldManRollList,
            'gold_gained' => $bet,
        ];
    }

    private function adjustGold(int $characterId, int $delta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gold = MAX(0, gold + :delta)
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
            'SELECT character_wealth.gold
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
