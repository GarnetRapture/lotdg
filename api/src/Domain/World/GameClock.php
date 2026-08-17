<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Persistence\Repository\GameSettingRepository;

final class GameClock
{
    private const int SECONDS_PER_DAY = 86400;

    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    public function daysPerCalendarDay(): int
    {
        return \max(1, $this->gameSettingRepository->getInt('daysperday', 4));
    }

    public function currentGameTimestamp(?int $realTimestamp = null): int
    {
        $realTimestamp ??= \time();
        $offsetSecond = $this->gameSettingRepository->getInt('gameoffsetseconds', 0);
        $adjusted = $realTimestamp - $offsetSecond;

        $mappedTimestamp = \strtotime(\date('1971-m-d H:i:s', $adjusted));

        if ($mappedTimestamp === false) {
            $mappedTimestamp = $adjusted;
        }

        $yearInSecond = \strtotime('1971-01-01 00:00:00');

        if ($yearInSecond === false || $yearInSecond <= 0) {
            $yearInSecond = self::SECONDS_PER_DAY * 365;
        }

        return ($mappedTimestamp * $this->daysPerCalendarDay()) % $yearInSecond;
    }

    public function formatGameTime(?int $realTimestamp = null): string
    {
        return \date('g:i a', $this->currentGameTimestamp($realTimestamp));
    }

    public function gameDateString(?int $realTimestamp = null): string
    {
        return \date('Y-m-d', $this->currentGameTimestamp($realTimestamp));
    }

    public function isNewDaySince(string $lastHitAt): bool
    {
        $lastTimestamp = \strtotime($lastHitAt);

        if ($lastTimestamp === false) {
            return true;
        }

        return $this->gameDateString() !== $this->gameDateString($lastTimestamp);
    }

    public function realSecondsUntilNextGameDay(?int $realTimestamp = null): int
    {
        $gameTimestamp = $this->currentGameTimestamp($realTimestamp);
        $secondsIntoGameDay = $gameTimestamp % self::SECONDS_PER_DAY;
        $secondsToNextGameDay = self::SECONDS_PER_DAY - $secondsIntoGameDay;

        return (int) \round($secondsToNextGameDay / $this->daysPerCalendarDay());
    }
}
