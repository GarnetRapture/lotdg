<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\TrainingService;
use Lotdg\Http\ControllerInterface;
use Lotdg\I18n\CatalogTranslator;
use Lotdg\I18n\LocaleResolver;
use Lotdg\Persistence\Repository\CreatureRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class TrainingController implements ControllerInterface
{
    private readonly TrainingService $trainingService;

    private readonly LocaleResolver $localeResolver;

    public function __construct(PDO $connection)
    {
        $this->localeResolver = new LocaleResolver($connection);
        $this->trainingService = new TrainingService(
            $connection,
            new CreatureRepository($connection),
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

        return match ($parameterMap['action'] ?? 'inspect') {
            'inspect' => $this->trainingService->inspect($characterId, $localeCode),
            'challenge' => $this->trainingService->challenge($characterId, $localeCode),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
