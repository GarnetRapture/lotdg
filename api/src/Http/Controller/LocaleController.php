<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Http\ControllerInterface;
use Lotdg\I18n\LocaleResolver;
use Lotdg\Persistence\Repository\LocaleRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class LocaleController implements ControllerInterface
{
    private readonly LocaleRepository $localeRepository;

    private readonly LocaleResolver $localeResolver;

    public function __construct(PDO $connection)
    {
        $this->localeRepository = new LocaleRepository($connection);
        $this->localeResolver = new LocaleResolver($connection);
    }

    /**
     * @param array<string, string> $parameterMap
     *
     * @return array<string, mixed>
     */
    public function handle(array $parameterMap): array
    {
        $requestedLocaleCode = $parameterMap['locale_code'] ?? null;

        if ($requestedLocaleCode === null) {
            return ['locale' => $this->localeRepository->findAllLocale()];
        }

        if (!$this->localeRepository->existsLocale($requestedLocaleCode)) {
            throw new LocalizedException('system-message', 'error.unsupported-locale');
        }

        return [
            'locale_code' => $requestedLocaleCode,
            'namespace' => $this->localeRepository->findLabelBundle(
                $requestedLocaleCode,
                $this->localeResolver->fallbackLocaleCode(),
            ),
        ];
    }
}
