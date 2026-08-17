<?php

declare(strict_types=1);

namespace Lotdg\I18n;

use PDO;

final class LocaleResolver
{
    private ?string $cachedFallbackLocaleCode = null;

    /** @var list<string>|null */
    private ?array $cachedSupportedLocaleCodeList = null;

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    public function resolve(?string $requestedLocaleCode, ?int $accountId = null): string
    {
        if ($requestedLocaleCode !== null && $this->isSupported($requestedLocaleCode)) {
            return $requestedLocaleCode;
        }

        if ($accountId !== null) {
            $preferredLocaleCode = $this->fetchAccountLocaleCode($accountId);

            if ($preferredLocaleCode !== null && $this->isSupported($preferredLocaleCode)) {
                return $preferredLocaleCode;
            }
        }

        return $this->fallbackLocaleCode();
    }

    public function isSupported(string $localeCode): bool
    {
        return \in_array($localeCode, $this->supportedLocaleCodeList(), true);
    }

    public function fallbackLocaleCode(): string
    {
        if ($this->cachedFallbackLocaleCode !== null) {
            return $this->cachedFallbackLocaleCode;
        }

        $statement = $this->connection->query(
            'SELECT locale_code FROM locale ORDER BY is_fallback DESC, sort_order ASC LIMIT 1',
        );

        $localeCode = $statement === false ? false : $statement->fetchColumn();
        $this->cachedFallbackLocaleCode = \is_string($localeCode) ? $localeCode : 'en';

        return $this->cachedFallbackLocaleCode;
    }

    /**
     * @return list<string>
     */
    public function supportedLocaleCodeList(): array
    {
        if ($this->cachedSupportedLocaleCodeList !== null) {
            return $this->cachedSupportedLocaleCodeList;
        }

        $statement = $this->connection->query('SELECT locale_code FROM locale ORDER BY sort_order ASC');
        $localeCodeList = [];

        foreach ($statement === false ? [] : $statement->fetchAll() as $row) {
            $localeCodeList[] = (string) $row['locale_code'];
        }

        $this->cachedSupportedLocaleCodeList = $localeCodeList;

        return $localeCodeList;
    }

    private function fetchAccountLocaleCode(int $accountId): ?string
    {
        $statement = $this->connection->prepare(
            'SELECT locale_code FROM account_preference WHERE account_id = :account_id',
        );
        $statement->execute(['account_id' => $accountId]);

        $localeCode = $statement->fetchColumn();

        return \is_string($localeCode) && $localeCode !== '' ? $localeCode : null;
    }
}
