<?php

declare(strict_types=1);

namespace Lotdg\I18n;

use Lotdg\Persistence\Repository\LocaleRepository;
use Lotdg\Support\LocalizedException;

final class LabelTranslator
{
    /** @var array<string, array<string, string>> */
    private array $cachedTranslationMap = [];

    public function __construct(
        private readonly LocaleRepository $localeRepository,
        private readonly LocaleResolver $localeResolver,
    ) {
    }

    /**
     * @param array<string, string|int> $placeholderMap
     */
    public function translate(
        string $namespaceCode,
        string $labelPath,
        string $localeCode,
        array $placeholderMap = [],
    ): ?string {
        $translationText = $this->lookup($namespaceCode, $labelPath, $localeCode);

        return $translationText === null
            ? null
            : $this->applyPlaceholder($translationText, $placeholderMap);
    }

    /**
     * @return array<string, mixed>
     */
    public function decorateException(LocalizedException $exception, string $localeCode): array
    {
        $payload = $exception->toPayload();
        $translationText = $this->translate(
            $exception->namespaceCode(),
            $exception->labelPath(),
            $localeCode,
            $exception->placeholderMap(),
        );

        $payload['locale_code'] = $localeCode;

        if ($translationText !== null) {
            $payload['error_message'] = $translationText;
        }

        return $payload;
    }

    private function lookup(string $namespaceCode, string $labelPath, string $localeCode): ?string
    {
        if (!isset($this->cachedTranslationMap[$localeCode])) {
            $labelBundle = $this->localeRepository->findLabelBundle(
                $localeCode,
                $this->localeResolver->fallbackLocaleCode(),
            );

            $this->cachedTranslationMap[$localeCode] = [];

            foreach ($labelBundle as $namespace => $pathMap) {
                foreach ($pathMap as $path => $translationText) {
                    $this->cachedTranslationMap[$localeCode][$namespace . '.' . $path] = $translationText;
                }
            }
        }

        return $this->cachedTranslationMap[$localeCode][$namespaceCode . '.' . $labelPath] ?? null;
    }

    /**
     * @param array<string, string|int> $placeholderMap
     */
    private function applyPlaceholder(string $translationText, array $placeholderMap): string
    {
        if ($placeholderMap === []) {
            return $translationText;
        }

        $searchList = [];
        $replaceList = [];

        foreach ($placeholderMap as $key => $value) {
            $searchList[] = '{' . $key . '}';
            $replaceList[] = (string) $value;
        }

        return \str_replace($searchList, $replaceList, $translationText);
    }
}
