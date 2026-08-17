<?php

declare(strict_types=1);

namespace Lotdg\Domain\World\Special;

use Lotdg\Domain\Social\CommentaryService;
use Lotdg\Support\LocalizedException;
use PDO;

final class GrassyFieldEvent implements SpecialEventInterface
{
    public const string EVENT_CODE = 'grassyfield';

    public const string SECTION_CODE = 'grassyfield';

    private const int COMMENT_LIMIT = 10;

    public function __construct(
        private readonly PDO $connection,
        private readonly SpecialEventState $eventState,
        private readonly CommentaryService $commentaryService,
    ) {
    }

    public function eventCode(): string
    {
        return self::EVENT_CODE;
    }

    /**
     * @return array<string, mixed>
     */
    public function start(int $characterId): array
    {
        $state = $this->eventState->load($characterId, self::EVENT_CODE);
        $alreadyRested = ($state['rested'] ?? false) === true;
        $row = $this->fetchCharacterRow($characterId);

        $healed = false;
        $turnLost = 0;
        $mountName = $row['mount_name'] === null ? null : (string) $row['mount_name'];

        if (!$alreadyRested) {
            if ((int) $row['hit_point'] < (int) $row['max_hit_point']) {
                $this->connection
                    ->prepare(
                        'UPDATE character_vital
                            SET hit_point = max_hit_point
                          WHERE character_id = :character_id',
                    )
                    ->execute(['character_id' => $characterId]);

                $healed = true;
            }

            if ($mountName !== null) {
                $this->rechargeMountBuff($characterId, (string) $row['buff_list_json'], (string) $row['mount_buff_json']);

                $this->connection
                    ->prepare(
                        'UPDATE character_daily_allowance
                            SET forest_turn = MAX(0, forest_turn - 1)
                          WHERE character_id = :character_id',
                    )
                    ->execute(['character_id' => $characterId]);

                $turnLost = 1;
            }
        }

        $this->eventState->store($characterId, self::EVENT_CODE, ['rested' => true]);

        $board = $this->commentaryService->listBySection(
            $characterId,
            self::SECTION_CODE,
            self::COMMENT_LIMIT,
            0,
        );

        return [
            'stage' => 'resting',
            'already_rested' => $alreadyRested,
            'healed' => $healed,
            'mount_name' => $mountName,
            'turn_lost' => $turnLost,
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
        return $this->commentaryService->post(
            $characterId,
            self::SECTION_CODE,
            $commentText,
            self::COMMENT_LIMIT,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function leave(int $characterId): array
    {
        $this->eventState->clear($characterId);

        return ['stage' => 'left'];
    }

    private function rechargeMountBuff(int $characterId, string $buffListJson, string $mountBuffJson): void
    {
        $mountBuff = \json_decode($mountBuffJson, true);

        if (!\is_array($mountBuff) || $mountBuff === []) {
            return;
        }

        $buffList = \json_decode($buffListJson, true);
        $buffList = \is_array($buffList) ? $buffList : [];
        $buffList['mount'] = $mountBuff;

        $encoded = \json_encode($buffList, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        $this->connection
            ->prepare(
                'UPDATE character_combat_stat
                    SET buff_list_json = :buff_list_json
                  WHERE character_id = :character_id',
            )
            ->execute([
                'buff_list_json' => $encoded === false ? '{}' : $encoded,
                'character_id' => $characterId,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchCharacterRow(int $characterId): array
    {
        $statement = $this->connection->prepare(
            'SELECT character_vital.hit_point,
                    character_vital.max_hit_point,
                    character_combat_stat.buff_list_json,
                    mount.mount_name,
                    COALESCE(mount.buff_json, \'{}\') AS mount_buff_json
               FROM game_character
               JOIN character_vital       ON character_vital.character_id = game_character.character_id
               JOIN character_combat_stat ON character_combat_stat.character_id = game_character.character_id
               JOIN character_equipment   ON character_equipment.character_id = game_character.character_id
               LEFT JOIN mount            ON mount.mount_id = character_equipment.mount_id
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
