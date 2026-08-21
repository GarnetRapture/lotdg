<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\GraveyardService;
use Lotdg\Http\ControllerInterface;
use Lotdg\I18n\CatalogTranslator;
use Lotdg\I18n\LocaleResolver;
use Lotdg\Persistence\Repository\CreatureRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class GraveyardController implements ControllerInterface
{
    private readonly GraveyardService $graveyardService;

    private readonly LocaleResolver $localeResolver;

    public function __construct(PDO $connection)
    {
        $this->localeResolver = new LocaleResolver($connection);
        $this->graveyardService = new GraveyardService(
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

        return match ($parameterMap['action'] ?? 'inspect') {
            'inspect' => $this->graveyardService->inspect($characterId),
            'search' => $this->graveyardService->searchUndead(
                $characterId,
                $this->localeResolver->resolve($parameterMap['request_locale_code'] ?? null),
            ),
            'fight' => $this->graveyardService->fightRound($characterId),
            'restore' => $this->graveyardService->restoreSoul($characterId),
            'resurrect' => $this->graveyardService->resurrect($characterId),
            'haunt' => $this->graveyardService->haunt(
                $characterId,
                (int) ($_POST['target_character_id'] ?? 0),
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
