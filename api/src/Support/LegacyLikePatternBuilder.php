<?php

declare(strict_types=1);

namespace Lotdg\Support;

final class LegacyLikePatternBuilder
{
    public function build(string $searchTerm): string
    {
        $pattern = '%';
        $length = \mb_strlen($searchTerm);

        for ($index = 0; $index < $length; ++$index) {
            $pattern .= \mb_substr($searchTerm, $index, 1) . '%';
        }

        return $pattern;
    }
}
