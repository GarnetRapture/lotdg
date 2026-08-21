<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\World\ForestService;
use Lotdg\Http\ControllerInterface;
use Lotdg\I18n\CatalogTranslator;
use Lotdg\I18n\LocaleResolver;
use Lotdg\Persistence\Repository\CreatureRepository;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class ForestController implements ControllerInterface
{
    private readonly ForestService $forestService;

    private readonly LocaleResolver $localeResolver;

    public function __construct(PDO $connection)
    {
        $this->localeResolver = new LocaleResolver($connection);
        $this->forestService = new ForestService(
            $connection,
            new CreatureRepository($connection),
            new GameSettingRepository($connection),
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

        $action = $parameterMap['action'] ?? 'search';

        return match ($action) {
            'search' => $this->forestService->beginEncounter(
                $characterId,
                $this->readSearchType(),
                $this->localeResolver->resolve($parameterMap['request_locale_code'] ?? null),
            ),
            'fight' => $this->forestService->fightRound($characterId),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }

    private function readSearchType(): string
    {
        $searchType = $_POST['search_type'] ?? ForestService::SEARCH_TYPE_NORMAL;

        if (!\is_string($searchType)) {
            return ForestService::SEARCH_TYPE_NORMAL;
        }

        return \in_array(
            $searchType,
            [
                ForestService::SEARCH_TYPE_NORMAL,
                ForestService::SEARCH_TYPE_SLUM,
                ForestService::SEARCH_TYPE_THRILL,
            ],
            true,
        ) ? $searchType : ForestService::SEARCH_TYPE_NORMAL;
    }
}
