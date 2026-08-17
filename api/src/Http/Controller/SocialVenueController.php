<?php

declare(strict_types=1);

namespace Lotdg\Http\Controller;

use Lotdg\Domain\Social\BadWordFilter;
use Lotdg\Domain\Social\CommentaryService;
use Lotdg\Domain\Social\SocialVenueService;
use Lotdg\Http\ControllerInterface;
use Lotdg\Persistence\Repository\GameSettingRepository;
use Lotdg\Support\LocalizedException;
use PDO;

final class SocialVenueController implements ControllerInterface
{
    private readonly SocialVenueService $socialVenueService;

    public function __construct(PDO $connection)
    {
        $gameSettingRepository = new GameSettingRepository($connection);

        $this->socialVenueService = new SocialVenueService(
            $connection,
            new CommentaryService(
                $connection,
                $gameSettingRepository,
                new BadWordFilter($connection, $gameSettingRepository),
            ),
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
        $venueCode = $parameterMap['venue_code'] ?? '';

        if ($characterId <= 0 || $venueCode === '') {
            throw new LocalizedException('system-message', 'error.invalid-parameter');
        }

        return match ($parameterMap['action'] ?? 'enter') {
            'enter' => $this->socialVenueService->enter($characterId, $venueCode),
            'post' => $this->socialVenueService->post(
                $characterId,
                $venueCode,
                \is_string($_POST['comment_text'] ?? null) ? $_POST['comment_text'] : '',
            ),
            default => throw new LocalizedException('system-message', 'error.unknown-action'),
        };
    }
}
