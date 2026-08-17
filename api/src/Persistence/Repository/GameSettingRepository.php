<?php

declare(strict_types=1);

namespace Lotdg\Persistence\Repository;

use PDO;

final class GameSettingRepository
{
    /** @var array<string, string>|null */
    private ?array $cachedSettingMap = null;

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    public function getString(string $settingKey, string $defaultValue): string
    {
        $settingMap = $this->loadAll();

        if (!isset($settingMap[$settingKey]) || \trim($settingMap[$settingKey]) === '') {
            $this->put($settingKey, $defaultValue);

            return $defaultValue;
        }

        return $settingMap[$settingKey];
    }

    public function getInt(string $settingKey, int $defaultValue): int
    {
        return (int) $this->getString($settingKey, (string) $defaultValue);
    }

    public function getBool(string $settingKey, bool $defaultValue): bool
    {
        return $this->getString($settingKey, $defaultValue ? '1' : '0') === '1';
    }

    public function put(string $settingKey, string $settingValue): void
    {
        $this->connection
            ->prepare(
                'INSERT INTO game_setting (setting_key, setting_value, updated_at)
                 VALUES (:setting_key, :setting_value, datetime(\'now\'))
                 ON CONFLICT(setting_key)
                 DO UPDATE SET setting_value = excluded.setting_value,
                               updated_at    = excluded.updated_at',
            )
            ->execute(['setting_key' => $settingKey, 'setting_value' => $settingValue]);

        $this->cachedSettingMap[$settingKey] = $settingValue;
    }

    /**
     * @return array<string, string>
     */
    public function loadAll(): array
    {
        if ($this->cachedSettingMap !== null) {
            return $this->cachedSettingMap;
        }

        $statement = $this->connection->query('SELECT setting_key, setting_value FROM game_setting');
        $settingMap = [];

        foreach ($statement === false ? [] : $statement->fetchAll() as $row) {
            $settingMap[(string) $row['setting_key']] = (string) $row['setting_value'];
        }

        $this->cachedSettingMap = $settingMap;

        return $settingMap;
    }
}
