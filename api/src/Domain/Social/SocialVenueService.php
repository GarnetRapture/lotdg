<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\Support\LocalizedException;
use PDO;

final class SocialVenueService
{
    public const string VENUE_GARDENS = 'gardens';

    public const string VENUE_VETERANS = 'veterans';

    private const int DEFAULT_LIMIT = 30;

    private const int VETERANS_REQUIRED_SUPERUSER_LEVEL = 2;

    public function __construct(
        private readonly PDO $connection,
        private readonly CommentaryService $commentaryService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function enter(int $characterId, string $venueCode): array
    {
        if (!\in_array($venueCode, [self::VENUE_GARDENS, self::VENUE_VETERANS], true)) {
            return ['entered' => false, 'message_key' => 'venue.error.unknown-venue'];
        }

        if ($venueCode === self::VENUE_VETERANS && !$this->canEnterVeteransClub($characterId)) {
            return ['entered' => false, 'message_key' => 'venue.error.veterans-locked'];
        }

        $board = $this->commentaryService->listBySection(
            $characterId,
            $venueCode,
            self::DEFAULT_LIMIT,
            0,
        );

        return [
            'entered' => true,
            'venue_code' => $venueCode,
            'comment_list' => $board['comment_list'],
            'post_quota_remaining' => $board['post_quota_remaining'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function post(int $characterId, string $venueCode, string $commentText): array
    {
        if (!\in_array($venueCode, [self::VENUE_GARDENS, self::VENUE_VETERANS], true)) {
            return ['posted' => false, 'message_key' => 'venue.error.unknown-venue'];
        }

        if ($venueCode === self::VENUE_VETERANS && !$this->canEnterVeteransClub($characterId)) {
            return ['posted' => false, 'message_key' => 'venue.error.veterans-locked'];
        }

        return $this->commentaryService->post(
            $characterId,
            $venueCode,
            $commentText,
            self::DEFAULT_LIMIT,
        );
    }

    private function canEnterVeteransClub(int $characterId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT character_progression.dragon_kill_count,
                    account_privilege.superuser_level
               FROM game_character
               JOIN character_progression ON character_progression.character_id = game_character.character_id
               JOIN account_privilege     ON account_privilege.account_id = game_character.account_id
              WHERE game_character.character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return (int) $row['dragon_kill_count'] > 0
            || (int) $row['superuser_level'] >= self::VETERANS_REQUIRED_SUPERUSER_LEVEL;
    }
}
