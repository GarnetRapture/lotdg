<?php

declare(strict_types=1);

namespace Lotdg\Persistence\Repository;

use PDO;

final class LocaleRepository
{
    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return list<array{locale_code: string, endonym: string, is_fallback: int, sort_order: int}>
     */
    public function findAllLocale(): array
    {
        $statement = $this->connection->query(
            'SELECT locale_code, endonym, is_fallback, sort_order
               FROM locale
              ORDER BY sort_order ASC',
        );

        /** @var list<array{locale_code: string, endonym: string, is_fallback: int, sort_order: int}> $rowList */
        $rowList = $statement === false ? [] : $statement->fetchAll();

        return $rowList;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function findLabelBundle(string $localeCode, string $fallbackLocaleCode): array
    {
        $statement = $this->connection->prepare(
            'SELECT label_key.namespace_code AS namespace_code,
                    label_key.label_path     AS label_path,
                    COALESCE(requested.translation_text, fallback.translation_text) AS translation_text
               FROM label_key
               LEFT JOIN label_translation AS requested
                      ON requested.label_key_id = label_key.label_key_id
                     AND requested.locale_code  = :locale_code
               LEFT JOIN label_translation AS fallback
                      ON fallback.label_key_id = label_key.label_key_id
                     AND fallback.locale_code  = :fallback_locale_code
              WHERE COALESCE(requested.translation_text, fallback.translation_text) IS NOT NULL
              ORDER BY label_key.namespace_code ASC, label_key.label_path ASC',
        );

        $statement->execute([
            'locale_code' => $localeCode,
            'fallback_locale_code' => $fallbackLocaleCode,
        ]);

        $labelBundle = [];

        /** @var array{namespace_code: string, label_path: string, translation_text: string} $row */
        foreach ($statement->fetchAll() as $row) {
            $labelBundle[$row['namespace_code']][$row['label_path']] = $row['translation_text'];
        }

        return $labelBundle;
    }

    public function existsLocale(string $localeCode): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM locale WHERE locale_code = :locale_code',
        );
        $statement->execute(['locale_code' => $localeCode]);

        return $statement->fetchColumn() !== false;
    }
}
