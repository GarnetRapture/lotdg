<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Support\LocalizedException;
use Lotdg\Support\RiddleAnswerNormalizer;
use PDO;

final class RiddleEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'riddles';

    public const string OUTCOME_GEM_ONE = 'gem-one';

    public const string OUTCOME_GEM_TWO = 'gem-two';

    public const string OUTCOME_TURN = 'turn';

    public const string OUTCOME_SPECIALTY_OR_EXPERIENCE = 'specialty-or-experience';

    public const string OUTCOME_GOLD_LOST = 'gold-lost';

    public const string OUTCOME_TURN_LOST = 'turn-lost';

    public const string OUTCOME_CHARM_LOST = 'charm-lost';

    private const int LEVENSHTEIN_TOLERANCE = 2;

    private const int EXPERIENCE_PER_LEVEL = 10;

    public function __construct(
        private readonly PDO $connection,
        private readonly SpecialEventState $eventState,
        private readonly RiddleAnswerNormalizer $answerNormalizer = new RiddleAnswerNormalizer(),
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
    public function accept(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-choice') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $riddleRow = $this->fetchRandomRiddle();

        if ($riddleRow === null) {
            $this->eventState->clear($characterId);

            return ['stage' => 'invalid', 'message_key' => 'special.riddle.error.catalog-empty'];
        }

        $this->eventState->store($characterId, self::EVENT_CODE, [
            'stage' => 'awaiting-answer',
            'riddle_id' => (int) $riddleRow['riddle_id'],
        ]);

        return [
            'stage' => 'awaiting-answer',
            'riddle_id' => (int) $riddleRow['riddle_id'],
            'riddle_text' => (string) $riddleRow['riddle_text'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function answer(int $characterId, string $answerText): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-answer') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $riddleRow = $this->fetchRiddle((int) ($state['riddle_id'] ?? 0));

        if ($riddleRow === null) {
            $this->eventState->clear($characterId);

            return ['stage' => 'invalid', 'message_key' => 'special.riddle.error.catalog-empty'];
        }

        $this->eventState->clear($characterId);

        $isCorrect = $this->answerNormalizer->matches(
            $answerText,
            (string) $riddleRow['answer_text'],
            self::LEVENSHTEIN_TOLERANCE,
        );

        return $isCorrect
            ? $this->applyReward($characterId, (string) $riddleRow['answer_text'])
            : $this->applyPenalty($characterId, (string) $riddleRow['answer_text']);
    }

    /**
     * @return array<string, mixed>
     */
    private function applyReward(int $characterId, string $answerText): array
    {
        $roll = \random_int(1, 10);

        if ($roll <= 4) {
            $this->adjustGem($characterId, 1);

            return $this->resolved(self::OUTCOME_GEM_ONE, $answerText, ['gem_gained' => 1]);
        }

        if ($roll <= 7) {
            $this->adjustGem($characterId, 2);

            return $this->resolved(self::OUTCOME_GEM_TWO, $answerText, ['gem_gained' => 2]);
        }

        if ($roll <= 9) {
            $this->adjustTurn($characterId, 1);

            return $this->resolved(self::OUTCOME_TURN, $answerText, ['turn_gained' => 1]);
        }

        return $this->resolved(
            self::OUTCOME_SPECIALTY_OR_EXPERIENCE,
            $answerText,
            $this->grantSpecialtyOrExperience($characterId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function applyPenalty(int $characterId, string $answerText): array
    {
        $roll = \random_int(1, 6);

        if ($roll <= 3) {
            $row = $this->fetchCharacterRow($characterId);
            $goldLost = \min(
                (int) $row['gold'],
                \random_int(1, \max(1, (int) $row['level'] * 10)),
            );

            $this->connection
                ->prepare(
                    'UPDATE character_wealth
                        SET gold = MAX(0, gold - :gold)
                      WHERE character_id = :character_id',
                )
                ->execute(['gold' => $goldLost, 'character_id' => $characterId]);

            $this->adjustTurn($characterId, -1);

            return $this->resolved(
                self::OUTCOME_GOLD_LOST,
                $answerText,
                ['gold_lost' => $goldLost, 'turn_lost' => 1],
            );
        }

        if ($roll <= 5) {
            $this->adjustTurn($characterId, -1);

            return $this->resolved(self::OUTCOME_TURN_LOST, $answerText, ['turn_lost' => 1]);
        }

        $this->connection
            ->prepare(
                'UPDATE character_social
                    SET charm = MAX(0, charm - 1)
                  WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return $this->resolved(self::OUTCOME_CHARM_LOST, $answerText, ['charm_lost' => 1]);
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function resolved(string $outcome, string $answerText, array $extra): array
    {
        return [
            'stage' => 'resolved',
            'outcome' => $outcome,
            'answer_text' => $answerText,
        ] + $extra;
    }

    /**
     * @return array<string, mixed>
     */
    private function grantSpecialtyOrExperience(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT specialty_code FROM character_specialty WHERE character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $specialtyCode = (int) $statement->fetchColumn();

        $column = match ($specialtyCode) {
            1 => 'dark_arts_rank',
            2 => 'mystical_power_rank',
            3 => 'thievery_rank',
            default => null,
        };

        if ($column !== null) {
            $this->connection
                ->prepare(
                    \sprintf(
                        'UPDATE character_specialty SET %s = %s + 1 WHERE character_id = :character_id',
                        $column,
                        $column,
                    ),
                )
                ->execute(['character_id' => $characterId]);

            return ['specialty_code' => $specialtyCode, 'point_gained' => 1];
        }

        $row = $this->fetchCharacterRow($characterId);
        $experienceGained = (int) $row['level'] * self::EXPERIENCE_PER_LEVEL;

        $this->connection
            ->prepare(
                'UPDATE character_progression
                    SET experience = experience + :experience
                  WHERE character_id = :character_id',
            )
            ->execute(['experience' => $experienceGained, 'character_id' => $characterId]);

        return ['experience_gained' => $experienceGained];
    }

    private function adjustGem(int $characterId, int $delta): void
    {
        $this->connection
            ->prepare(
                'UPDATE character_wealth
                    SET gem = MAX(0, gem + :delta)
                  WHERE character_id = :character_id',
            )
            ->execute(['delta' => $delta, 'character_id' => $characterId]);
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

    /**
     * @return array<string, mixed>|null
     */
    private function fetchRandomRiddle(): ?array
    {
        $statement = $this->connection->query(
            'SELECT riddle_id, riddle_text, answer_text FROM riddle ORDER BY RANDOM() LIMIT 1',
        );

        if ($statement === false) {
            return null;
        }

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchRiddle(int $riddleId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT riddle_id, riddle_text, answer_text FROM riddle WHERE riddle_id = :riddle_id',
        );
        $statement->execute(['riddle_id' => $riddleId]);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCharacterRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.level,
                    character_wealth.gold
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
