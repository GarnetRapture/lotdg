<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

final class BattleRoundResult
{
    public function __construct(
        public readonly int $damageToEnemy,
        public readonly int $damageToPlayer,
        public readonly bool $isCriticalAttack,
        public readonly float $attackScore,
        public readonly int $playerHitPoint,
        public readonly int $enemyHitPoint,
    ) {
    }

    public function isPlayerVictorious(): bool
    {
        return $this->enemyHitPoint <= 0 && $this->playerHitPoint > 0;
    }

    public function isPlayerDefeated(): bool
    {
        return $this->playerHitPoint <= 0;
    }
}
