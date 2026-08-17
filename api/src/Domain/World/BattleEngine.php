<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

final class BattleEngine
{
    private const int CRITICAL_ATTACK_DENOMINATOR = 20;

    private const int CRITICAL_ATTACK_MULTIPLIER = 3;

    private const int ENEMY_FIRST_STRIKE_DENOMINATOR = 3;

    public function resolveRound(
        BattleCombatant $player,
        BattleCombatant $enemy,
        BattleModifier $modifier,
        bool $isPlayerVersusPlayer = false,
    ): BattleRoundResult {
        if (!$player->isAlive() || !$enemy->isAlive()) {
            return new BattleRoundResult(
                0,
                0,
                false,
                0.0,
                $player->hitPoint,
                $enemy->hitPoint,
            );
        }

        $adjustment = $isPlayerVersusPlayer
            ? 1.0
            : $player->level / \max(1, $enemy->level);

        $adjustedEnemyDefence = $isPlayerVersusPlayer
            ? $enemy->defencePoint * $modifier->enemyDefenceModifier
            : $modifier->enemyDefenceModifier * $enemy->defencePoint / ($adjustment * $adjustment);

        $adjustedEnemyAttack = $enemy->attackPoint * $modifier->enemyAttackModifier;
        $adjustedPlayerDefence = $player->defencePoint * $adjustment * $modifier->defenceModifier;

        $damageToEnemy = 0;
        $damageToPlayer = 0;
        $attackScore = 0.0;
        $isCriticalAttack = false;

        while ($damageToEnemy === 0 && $damageToPlayer === 0) {
            $attackScore = $player->attackPoint * $modifier->attackModifier;
            $isCriticalAttack = $this->rollInteger(1, self::CRITICAL_ATTACK_DENOMINATOR) === 1;

            if ($isCriticalAttack) {
                $attackScore *= self::CRITICAL_ATTACK_MULTIPLIER;
            }

            $damageToEnemy = $this->resolveDamage(
                $this->rollFloat(0.0, $attackScore),
                $this->rollFloat(0.0, $adjustedEnemyDefence),
                $modifier->damageModifier,
                $modifier->enemyDamageModifier,
            );

            $damageToPlayer = $this->resolveDamage(
                $this->rollFloat(0.0, $adjustedEnemyAttack),
                $this->rollFloat(0.0, $adjustedPlayerDefence),
                $modifier->enemyDamageModifier,
                $modifier->damageModifier,
            );
        }

        $enemy->receiveDamage($damageToEnemy);
        $player->receiveDamage($damageToPlayer);

        return new BattleRoundResult(
            $damageToEnemy,
            $damageToPlayer,
            $isCriticalAttack,
            $attackScore,
            $player->hitPoint,
            $enemy->hitPoint,
        );
    }

    public function rollsEnemyFirstStrike(): bool
    {
        return $this->rollInteger(1, self::ENEMY_FIRST_STRIKE_DENOMINATOR) === 1;
    }

    private function resolveDamage(
        float $attackRoll,
        float $defenceRoll,
        float $positiveModifier,
        float $negativeModifier,
    ): int {
        $damage = (int) ($attackRoll - $defenceRoll);

        if ($damage < 0) {
            $damage = (int) ($damage / 2);

            return (int) \round($damage * $negativeModifier);
        }

        return (int) \round($damage * $positiveModifier);
    }

    private function rollInteger(int $minimum, int $maximum): int
    {
        return $minimum >= $maximum ? $minimum : \random_int($minimum, $maximum);
    }

    private function rollFloat(float $minimum, float $maximum): float
    {
        $scaledMinimum = (int) \round($minimum * 1000);
        $scaledMaximum = (int) \round($maximum * 1000);

        if ($scaledMinimum === $scaledMaximum) {
            return $scaledMinimum / 1000;
        }

        if ($scaledMinimum > $scaledMaximum) {
            [$scaledMinimum, $scaledMaximum] = [$scaledMaximum, $scaledMinimum];
        }

        return \round(\random_int($scaledMinimum, $scaledMaximum) / 1000);
    }
}
