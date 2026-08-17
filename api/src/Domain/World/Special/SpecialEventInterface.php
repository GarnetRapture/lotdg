<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

interface SpecialEventInterface
{
    public function eventCode(): string;

    /**
     * @return array<string, mixed>
     */
    public function start(int $characterId): array;
}
