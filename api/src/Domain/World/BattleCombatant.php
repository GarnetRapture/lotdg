<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

final class BattleCombatant
{
    public function __construct(
        public readonly string $name,
        public readonly int $level,
        public readonly string $weaponName,
        public int $hitPoint,
        public readonly int $attackPoint,
        public readonly int $defencePoint,
    ) {
    }

    public function isAlive(): bool
    {
        return $this->hitPoint > 0;
    }

    public function receiveDamage(int $damage): void
    {
        $this->hitPoint -= $damage;
    }
}
