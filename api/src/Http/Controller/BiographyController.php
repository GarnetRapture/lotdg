<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\BiographyService;
use Lotdg\Http\ControllerInterface;
use Lotdg\I18n\CatalogTranslator;
use Lotdg\I18n\LocaleResolver;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class BiographyController implements ControllerInterface
{
    private readonly BiographyService $biographyService;

    private readonly LocaleResolver $localeResolver;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->localeResolver = new LocaleResolver($connection);
        $this->biographyService = new BiographyService(
            $connection,
            new BadWordFilter($connection, $gameSettingRepository),
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

        return $this->biographyService->view(
            $characterId,
            $this->localeResolver->resolve($parameterMap['request_locale_code'] ?? null),
        );
    }
}
