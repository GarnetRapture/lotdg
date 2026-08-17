<?php

declare(strict_types=1);

namespace Lotdg\Support;

final class PasswordHasher
{
    public const int MINIMUM_LENGTH = 4;

    private const string ALGORITHM = \PASSWORD_DEFAULT;

    public function hash(string $plainPassword): string
    {
        return \password_hash($plainPassword, self::ALGORITHM);
    }

    public function verify(string $plainPassword, string $storedValue): bool
    {
        if ($this->isHashed($storedValue)) {
            return \password_verify($plainPassword, $storedValue);
        }

        return \hash_equals($storedValue, $plainPassword);
    }

    public function needsRehash(string $storedValue): bool
    {
        if (!$this->isHashed($storedValue)) {
            return true;
        }

        return \password_needs_rehash($storedValue, self::ALGORITHM);
    }

    public function isAcceptableLength(string $plainPassword): bool
    {
        return \mb_strlen($plainPassword) >= self::MINIMUM_LENGTH;
    }

    private function isHashed(string $storedValue): bool
    {
        return \password_get_info($storedValue)['algo'] !== null;
    }
}
