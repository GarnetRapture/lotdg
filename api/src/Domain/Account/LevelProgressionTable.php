<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

final class LevelProgressionTable
{
    public const int MAXIMUM_LEVEL = 15;

    private const array BASE_EXPERIENCE_BY_LEVEL = [
        1 => 100,
        2 => 400,
        3 => 1002,
        4 => 1912,
        5 => 3140,
        6 => 4707,
        7 => 6641,
        8 => 8985,
        9 => 11795,
        10 => 15143,
        11 => 19121,
        12 => 23840,
        13 => 29437,
        14 => 36071,
        15 => 43930,
    ];

    public const int HIT_POINT_GAIN_PER_LEVEL = 10;

    public const int SOUL_POINT_GAIN_PER_LEVEL = 5;

    public const int ATTACK_GAIN_PER_LEVEL = 1;

    public const int DEFENCE_GAIN_PER_LEVEL = 1;

    public function requiredExperience(int $level, int $dragonKillCount): int
    {
        $baseExperience = self::BASE_EXPERIENCE_BY_LEVEL[$level] ?? null;

        if ($baseExperience === null) {
            return \PHP_INT_MAX;
        }

        return (int) \round($baseExperience + ($dragonKillCount / 4) * $level * 100);
    }

    public function hasMaster(int $level): bool
    {
        return isset(self::BASE_EXPERIENCE_BY_LEVEL[$level]);
    }
}
