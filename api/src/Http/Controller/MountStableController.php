<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Catalog\MountStableService;
use Lotdg\Http\ControllerInterface;
use Lotdg\I18n\CatalogTranslator;
use Lotdg\I18n\LocaleResolver;
use Lotdg\Support\LocalizedException;
use PDO;

final class MountStableController implements ControllerInterface
{
    private readonly MountStableService $mountStableService;

    private readonly LocaleResolver $localeResolver;

    public function __construct(PDO $connection)
    {
        $this->localeResolver = new LocaleResolver($connection);
        $this->mountStableService = new MountStableService(
            $connection,
            new CatalogTranslator($connection, $this->localeResolver),
        );
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        $characterId = (int) ($parameterMap['character_id'] ?? 0);

        if ($characterId <= 0) {
            throw new LocalizedException('system-message', 'error.invalid-character-id');
        }

        $localeCode = $this->localeResolver->resolve($parameterMap['request_locale_code'] ?? null);

        return match ($parameterMap['action'] ?? 'browse') {
            'browse' => $this->mountStableService->browse($characterId, $localeCode),
            'buy' => $this->mountStableService->buy(
                $characterId,
                (int) ($_POST['mount_id'] ?? 0),
                $localeCode,
            ),
            'sell' => $this->mountStableService->sell($characterId, $localeCode),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
