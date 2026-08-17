<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use PDO;

final class CrazyAudreyEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'audrey';

    public const int KITTEN_KIND_COUNT = 4;

    public const int JACKPOT_KITTEN_CODE = 4;

    public const int JACKPOT_DENOMINATOR = 20;

    public const string OUTCOME_JACKPOT = 'jackpot';

    public const string OUTCOME_TRIPLE = 'triple';

    public const string OUTCOME_PAIR = 'pair';

    public const string OUTCOME_NONE = 'none';

    private const int JACKPOT_TURN_GAIN = 3;

    private const int TRIPLE_TURN_GAIN = 2;

    private const int PAIR_TURN_GAIN = 1;

    public function __construct(
        private readonly PDO $connection,
        private readonly SpecialEventState $eventState,
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
    public function runAway(int $characterId): array
    {
        $this->eventState->clear($characterId);

        return ['stage' => 'fled'];
    }

    /**
     * @return array<string, mixed>
     */
    public function play(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);

        if (($state['stage'] ?? '') !== 'awaiting-choice') {
            return ['stage' => 'invalid', 'message_key' => 'special.error.wrong-stage'];
        }

        $this->eventState->clear($characterId);

        $isJackpot = \random_int(1, self::JACKPOT_DENOMINATOR) === 1;

        $kittenCodeList = $isJackpot
            ? [self::JACKPOT_KITTEN_CODE, self::JACKPOT_KITTEN_CODE, self::JACKPOT_KITTEN_CODE]
            : [
                \random_int(0, self::KITTEN_KIND_COUNT - 1),
                \random_int(0, self::KITTEN_KIND_COUNT - 1),
                \random_int(0, self::KITTEN_KIND_COUNT - 1),
            ];

        [$first, $second, $third] = $kittenCodeList;

        if ($first === $second && $second === $third) {
            $turnGain = $isJackpot ? self::JACKPOT_TURN_GAIN : self::TRIPLE_TURN_GAIN;
            $this->adjustTurn($characterId, $turnGain);

            return [
                'stage' => 'resolved',
                'outcome' => $isJackpot ? self::OUTCOME_JACKPOT : self::OUTCOME_TRIPLE,
                'kitten_code_list' => $kittenCodeList,
                'turn_gained' => $turnGain,
            ];
        }

        if ($first === $second || $second === $third || $first === $third) {
            $this->adjustTurn($characterId, self::PAIR_TURN_GAIN);

            return [
                'stage' => 'resolved',
                'outcome' => self::OUTCOME_PAIR,
                'kitten_code_list' => $kittenCodeList,
                'turn_gained' => self::PAIR_TURN_GAIN,
            ];
        }

        $this->connection
            ->prepare(
                'UPDATE character_daily_allowance SET forest_turn = 0 WHERE character_id = :character_id',
            )
            ->execute(['character_id' => $characterId]);

        return [
            'stage' => 'resolved',
            'outcome' => self::OUTCOME_NONE,
            'kitten_code_list' => $kittenCodeList,
            'turn_lost_all' => true,
        ];
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
}
