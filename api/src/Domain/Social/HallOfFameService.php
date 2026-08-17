<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use PDO;

final class HallOfFameService
{
    private const int RANKING_LIMIT = 10;

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'dragon_slayer' => $this->fetchDragonSlayer(),
            'top_warrior' => $this->fetchTopWarrior(),
            'wealthiest' => $this->fetchWealthiest(),
            'strongest' => $this->fetchStrongest(),
            'bounty_hunter' => $this->fetchBountyHunter(),
            'most_resurrected' => $this->fetchMostResurrected(),
            'most_active' => $this->fetchMostActive(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchDragonSlayer(): array
    {
        return $this->fetchAll(
            'SELECT game_character.display_name,
                    character_progression.dragon_kill_count
               FROM game_character
               JOIN account               ON account.account_id = game_character.account_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
              WHERE account.is_locked = 0
                AND character_progression.dragon_kill_count > 0
              ORDER BY character_progression.dragon_kill_count DESC',
            null,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchTopWarrior(): array
    {
        return $this->fetchAll(
            'SELECT game_character.display_name,
                    game_character.level,
                    character_progression.experience
               FROM game_character
               JOIN account               ON account.account_id = game_character.account_id
               JOIN character_progression ON character_progression.character_id = game_character.character_id
              WHERE account.is_locked = 0
              ORDER BY game_character.level DESC, character_progression.experience DESC',
            self::RANKING_LIMIT,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchWealthiest(): array
    {
        return $this->fetchAll(
            'SELECT game_character.display_name,
                    character_wealth.gold,
                    character_wealth.gold_in_bank
               FROM game_character
               JOIN account          ON account.account_id = game_character.account_id
               JOIN character_wealth ON character_wealth.character_id = game_character.character_id
              WHERE account.is_locked = 0
              ORDER BY (character_wealth.gold + character_wealth.gold_in_bank) DESC',
            self::RANKING_LIMIT,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchStrongest(): array
    {
        return $this->fetchAll(
            'SELECT game_character.display_name,
                    character_combat_stat.attack_point,
                    character_combat_stat.defence_point
               FROM game_character
               JOIN account               ON account.account_id = game_character.account_id
               JOIN character_combat_stat ON character_combat_stat.character_id = game_character.character_id
              WHERE account.is_locked = 0
              ORDER BY (character_combat_stat.attack_point + character_combat_stat.defence_point) DESC',
            self::RANKING_LIMIT,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchBountyHunter(): array
    {
        return $this->fetchAll(
            'SELECT game_character.display_name,
                    game_character.level,
                    character_social.player_kill_count
               FROM game_character
               JOIN account          ON account.account_id = game_character.account_id
               JOIN character_social ON character_social.character_id = game_character.character_id
              WHERE account.is_locked = 0
              ORDER BY character_social.player_kill_count DESC',
            self::RANKING_LIMIT,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchMostResurrected(): array
    {
        return $this->fetchAll(
            'SELECT game_character.display_name,
                    character_vital.resurrection_count
               FROM game_character
               JOIN account         ON account.account_id = game_character.account_id
               JOIN character_vital ON character_vital.character_id = game_character.character_id
              WHERE account.is_locked = 0
              ORDER BY character_vital.resurrection_count DESC',
            self::RANKING_LIMIT,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchMostActive(): array
    {
        return $this->fetchAll(
            'SELECT game_character.display_name,
                    character_session_state.generation_count
               FROM game_character
               JOIN account                 ON account.account_id = game_character.account_id
               JOIN character_session_state  ON character_session_state.character_id = game_character.character_id
              WHERE account.is_locked = 0
              ORDER BY character_session_state.generation_count DESC',
            self::RANKING_LIMIT,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAll(string $sql, ?int $limit): array
    {
        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }

        $statement = $this->connection->query($sql);

        if ($statement === false) {
            return [];
        }

        $rankedList = [];
        $rank = 1;

        foreach ($statement->fetchAll() as $row) {
            $rankedList[] = ['rank' => $rank++] + $row;
        }

        return $rankedList;
    }
}
