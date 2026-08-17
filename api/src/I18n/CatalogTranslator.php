<?php

declare(strict_types=1);

namespace Lotdg\I18n;

use PDO;

final class CatalogTranslator
{
    public const string ENTITY_WEAPON = 'weapon';

    public const string ENTITY_ARMOR = 'armor';

    public const string ENTITY_CREATURE = 'creature';

    public const string ENTITY_TRAINING_MASTER = 'training_master';

    public const string ENTITY_MOUNT = 'mount';

    public const string ENTITY_RIDDLE = 'riddle';

    public const string ENTITY_TAUNT = 'taunt';

    /** @var array<string, array<string, string>> */
    private array $cachedEntityMap = [];

    public function __construct(
        private readonly PDO $connection,
        private readonly LocaleResolver $localeResolver,
    ) {
    }

    public function translate(
        string $entityType,
        int $entityId,
        string $fieldCode,
        string $originalText,
        string $localeCode,
    ): string {
        $translationText = $this->lookup($entityType, $localeCode, $entityId, $fieldCode);

        if ($translationText !== null) {
            return $translationText;
        }

        $fallbackLocaleCode = $this->localeResolver->fallbackLocaleCode();

        if ($fallbackLocaleCode !== $localeCode) {
            $translationText = $this->lookup($entityType, $fallbackLocaleCode, $entityId, $fieldCode);
        }

        return $translationText ?? $originalText;
    }

    /**
     * @param list<array<string, mixed>> $rowList
     *
     * @return list<array<string, mixed>>
     */
    public function translateRowList(
        array $rowList,
        string $entityType,
        string $identifierColumn,
        string $fieldCode,
        string $localeCode,
    ): array {
        return \array_map(
            function (array $row) use ($entityType, $identifierColumn, $fieldCode, $localeCode): array {
                if (!isset($row[$identifierColumn], $row[$fieldCode])) {
                    return $row;
                }

                $row[$fieldCode] = $this->translate(
                    $entityType,
                    (int) $row[$identifierColumn],
                    $fieldCode,
                    (string) $row[$fieldCode],
                    $localeCode,
                );

                return $row;
            },
            $rowList,
        );
    }

    private function lookup(string $entityType, string $localeCode, int $entityId, string $fieldCode): ?string
    {
        $cacheKey = $entityType . '|' . $localeCode;

        if (!isset($this->cachedEntityMap[$cacheKey])) {
            $statement = $this->connection->prepare(
                'SELECT entity_id, field_code, translation_text
                   FROM catalog_translation
                  WHERE entity_type = :entity_type
                    AND locale_code = :locale_code',
            );
            $statement->execute(['entity_type' => $entityType, 'locale_code' => $localeCode]);

            $entityMap = [];

            foreach ($statement->fetchAll() as $row) {
                $entityMap[$row['entity_id'] . '|' . $row['field_code']] = (string) $row['translation_text'];
            }

            $this->cachedEntityMap[$cacheKey] = $entityMap;
        }

        return $this->cachedEntityMap[$cacheKey][$entityId . '|' . $fieldCode] ?? null;
    }
}
