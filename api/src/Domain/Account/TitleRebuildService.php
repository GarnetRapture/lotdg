<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

use Lotdg\Domain\World\RankTitleTable;
use PDO;

final class TitleRebuildService
{
    public function __construct(
        private readonly PDO $connection,
        private readonly RankTitleTable $rankTitleTable = new RankTitleTable(),
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function rebuild(): array
    {
        $statement = $this->connection->query(
            'SELECT game_character.character_id,
                    game_character.display_name,
                    game_character.rank_title,
                    game_character.sex_code,
                    game_character.custom_title,
                    character_progression.dragon_kill_count
               FROM game_character
               JOIN character_progression ON character_progression.character_id = game_character.character_id
              WHERE character_progression.dragon_kill_count > 0',
        );

        $changeList = [];
        $updateStatement = $this->connection->prepare(
            'UPDATE game_character
                SET display_name = :display_name,
                    rank_title   = :rank_title
              WHERE character_id = :character_id',
        );

        foreach ($statement === false ? [] : $statement->fetchAll() as $row) {
            if (\trim((string) ($row['custom_title'] ?? '')) !== '') {
                continue;
            }

            $newTitle = $this->rankTitleTable->resolve(
                (int) $row['dragon_kill_count'],
                (int) $row['sex_code'],
            );
            $previousName = (string) $row['display_name'];
            $newName = $this->replaceTitle($previousName, (string) $row['rank_title'], $newTitle);

            if ($newName === $previousName && (string) $row['rank_title'] === $newTitle) {
                continue;
            }

            $updateStatement->execute([
                'display_name' => $newName,
                'rank_title' => $newTitle,
                'character_id' => (int) $row['character_id'],
            ]);

            $changeList[] = [
                'character_id' => (int) $row['character_id'],
                'previous_name' => $previousName,
                'new_name' => $newName,
                'new_title' => $newTitle,
                'dragon_kill_count' => (int) $row['dragon_kill_count'],
            ];
        }

        return ['rebuilt_count' => \count($changeList), 'change_list' => $changeList];
    }

    private function replaceTitle(string $displayName, string $currentTitle, string $newTitle): string
    {
        if ($currentTitle === '') {
            return \trim($newTitle . ' ' . $displayName);
        }

        $position = \mb_strpos($displayName, $currentTitle);

        if ($position === false) {
            return \trim($newTitle . ' ' . $displayName);
        }

        return \mb_substr($displayName, 0, $position)
            . $newTitle
            . \mb_substr($displayName, $position + \mb_strlen($currentTitle));
    }
}
