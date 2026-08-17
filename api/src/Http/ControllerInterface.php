<?php

declare(strict_types=1);

namespace Lotdg\Http;

interface ControllerInterface
{
    /**
     * @param array<string, string> $parameterMap 경로에서 추출된 이름-값 쌍.
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function handle(array $parameterMap): array;
}
