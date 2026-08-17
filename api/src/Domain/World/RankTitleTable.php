<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

final class RankTitleTable
{
    /** @var array<int, array{0: string, 1: string}> */
    private const array TITLE_BY_DRAGON_KILL = [
        0 => ['Farmboy', 'Farmgirl'],
        1 => ['Page', 'Page'],
        2 => ['Squire', 'Squire'],
        3 => ['Gladiator', 'Gladiatrix'],
        4 => ['Legionnaire', 'Legioness'],
        5 => ['Centurion', 'Centurioness'],
        6 => ['Sir', 'Madam'],
        7 => ['Reeve', 'Reeve'],
        8 => ['Steward', 'Stewardess'],
        9 => ['Mayor', 'Mayoress'],
        10 => ['Baron', 'Baroness'],
        11 => ['Count', 'Countess'],
        12 => ['Viscount', 'Viscountess'],
        13 => ['Marquis', 'Marquisette'],
        14 => ['Chancellor', 'Chancelress'],
        15 => ['Prince', 'Princess'],
        16 => ['King', 'Queen'],
        17 => ['Emperor', 'Empress'],
        18 => ['Angel', 'Angel'],
        19 => ['Archangel', 'Archangel'],
        20 => ['Principality', 'Principality'],
        21 => ['Power', 'Power'],
        22 => ['Virtue', 'Virtue'],
        23 => ['Dominion', 'Dominion'],
        24 => ['Throne', 'Throne'],
        25 => ['Cherub', 'Cherub'],
        26 => ['Seraph', 'Seraph'],
        27 => ['Demigod', 'Demigoddess'],
        28 => ['Titan', 'Titaness'],
        29 => ['Archtitan', 'Archtitaness'],
        30 => ['Undergod', 'Undergoddess'],
    ];

    public function resolve(int $dragonKillCount, int $sexCode): string
    {
        $titlePair = self::TITLE_BY_DRAGON_KILL[$dragonKillCount] ?? null;

        if ($titlePair === null) {
            return $sexCode === 1 ? 'Goddess' : 'God';
        }

        return $titlePair[$sexCode === 1 ? 1 : 0];
    }
}
