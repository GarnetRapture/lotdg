<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

final class BattleModifier
{
    public function __construct(
        public readonly float $attackModifier = 1.0,
        public readonly float $defenceModifier = 1.0,
        public readonly float $damageModifier = 1.0,
        public readonly float $enemyAttackModifier = 1.0,
        public readonly float $enemyDefenceModifier = 1.0,
        public readonly float $enemyDamageModifier = 1.0,
    ) {
    }

    /**
     * @param array<string, mixed> $buffListMap
     */
    public static function fromBuffList(array $buffListMap): self
    {
        $attackModifier = 1.0;
        $defenceModifier = 1.0;
        $damageModifier = 1.0;
        $enemyAttackModifier = 1.0;
        $enemyDefenceModifier = 1.0;
        $enemyDamageModifier = 1.0;

        foreach ($buffListMap as $buff) {
            if (!\is_array($buff)) {
                continue;
            }

            $attackModifier *= (float) ($buff['atkmod'] ?? 1);
            $defenceModifier *= (float) ($buff['defmod'] ?? 1);
            $damageModifier *= (float) ($buff['dmgmod'] ?? 1);
            $enemyAttackModifier *= (float) ($buff['badguyatkmod'] ?? 1);
            $enemyDefenceModifier *= (float) ($buff['badguydefmod'] ?? 1);
            $enemyDamageModifier *= (float) ($buff['badguydmgmod'] ?? 1);
        }

        return new self(
            $attackModifier,
            $defenceModifier,
            $damageModifier,
            $enemyAttackModifier,
            $enemyDefenceModifier,
            $enemyDamageModifier,
        );
    }
}
