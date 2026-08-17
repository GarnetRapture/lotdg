<?php

declare(strict_types=1);

namespace Lotdg\Support;

use RuntimeException;

final class LocalizedException extends RuntimeException
{
    /**
     * @param array<string, string|int> $placeholderMap
     */
    public function __construct(
        private readonly string $namespaceCode,
        private readonly string $labelPath,
        private readonly array $placeholderMap = [],
    ) {
        parent::__construct($namespaceCode . '.' . $labelPath);
    }

    public function namespaceCode(): string
    {
        return $this->namespaceCode;
    }

    public function labelPath(): string
    {
        return $this->labelPath;
    }

    /**
     * @return array<string, string|int>
     */
    public function placeholderMap(): array
    {
        return $this->placeholderMap;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'error_namespace' => $this->namespaceCode,
            'error_label_path' => $this->labelPath,
            'error_placeholder' => $this->placeholderMap,
        ];
    }
}
