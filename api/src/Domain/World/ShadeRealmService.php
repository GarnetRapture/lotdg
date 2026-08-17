<?php

declare(strict_types=1);

namespace Lotdg\Domain\World;

use Lotdg\Domain\Social\CommentaryService;
use Lotdg\Support\LocalizedException;
use PDO;

final class ShadeRealmService
{
    public const string SECTION_CODE = 'shade';

    private const int DEFAULT_LIMIT = 25;

    public function __construct(
        private readonly PDO $connection,
        private readonly CommentaryService $commentaryService,
        private readonly GameClock $gameClock,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function enter(int $characterId): array
    {
        $row = $this->fetchShadeRow($characterId);

        if ((int) $row['is_alive'] === 1) {
            return ['entered' => false, 'message_key' => 'shade.error.still-alive'];
        }

        $board = $this->commentaryService->listBySection(
            $characterId,
            self::SECTION_CODE,
            self::DEFAULT_LIMIT,
            0,
        );

        return [
            'entered' => true,
            'game_time' => $this->gameClock->formatGameTime(),
            'soul_point' => (int) $row['soul_point'],
            'section_code' => self::SECTION_CODE,
            'comment_list' => $board['comment_list'],
            'post_quota_remaining' => $board['post_quota_remaining'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function post(int $characterId, string $commentText): array
    {
        $row = $this->fetchShadeRow($characterId);

        if ((int) $row['is_alive'] === 1) {
            return ['posted' => false, 'message_key' => 'shade.error.still-alive'];
        }

        return $this->commentaryService->post(
            $characterId,
            self::SECTION_CODE,
            $commentText,
            self::DEFAULT_LIMIT,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchShadeRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT character_vital.is_alive,
                    character_vital.soul_point
               FROM game_character
               JOIN character_vital ON character_vital.character_id = game_character.character_id
              WHERE game_character.character_id = :character_id',
        );
        $statement->execute(['character_id' => $characterId]);

        $row = $statement->fetch();

        if ($row === false) {
            throw new LocalizedException('system-message', 'error.character-not-found');
        }

        return $row;
    }
}
