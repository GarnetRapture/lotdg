<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\Support\LocalizedException;
use PDO;

final class GypsySeerService
{
    public const string SHADE_SECTION_CODE = 'shade';

    private const int COST_PER_LEVEL = 20;

    public function __construct(
        private readonly PDO $connection,
        private readonly CommentaryService $commentaryService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(int $characterId): array
    {
        $row = $this->fetchSeerRow($characterId);
        $cost = (int) $row['level'] * self::COST_PER_LEVEL;

        return [
            'gold' => (int) $row['gold'],
            'cost' => $cost,
            'affordable' => (int) $row['gold'] >= $cost,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payAndListen(int $characterId, int $limit = 25, int $page = 0): array
    {
        $row = $this->fetchSeerRow($characterId);
        $cost = (int) $row['level'] * self::COST_PER_LEVEL;

        if ((int) $row['gold'] < $cost) {
            return [
                'listened' => false,
                'message_key' => 'gypsy.error.not-enough-gold',
                'cost' => $cost,
            ];
        }

        $this->connection
            ->prepare('UPDATE character_wealth SET gold = gold - :cost WHERE character_id = :character_id')
            ->execute(['cost' => $cost, 'character_id' => $characterId]);

        $board = $this->commentaryService->listBySection(
            $characterId,
            self::SHADE_SECTION_CODE,
            $limit,
            $page,
        );

        return [
            'listened' => true,
            'cost' => $cost,
            'section_code' => self::SHADE_SECTION_CODE,
            'comment_list' => $board['comment_list'],
            'post_quota_remaining' => $board['post_quota_remaining'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchSeerRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT game_character.level,
                    character_wealth.gold
               FROM game_character
               JOIN character_wealth ON character_wealth.character_id = game_character.character_id
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
