<?php

declare(strict_types=1);

namespace Lotdg\Domain\Catalog;

use PDO;

final class TauntEditorService
{
    public const string PLACEHOLDER_LOSER_NAME = '%w';

    public const string PLACEHOLDER_LOSER_WEAPON = '%x';

    public const string PLACEHOLDER_LOSER_SUBJECTIVE = '%s';

    public const string PLACEHOLDER_LOSER_POSSESSIVE = '%p';

    public const string PLACEHOLDER_LOSER_OBJECTIVE = '%o';

    public const string PLACEHOLDER_WINNER_NAME = '%W';

    public const string PLACEHOLDER_WINNER_WEAPON = '%X';

    /** @var array<string, string> */
    private const PREVIEW_SAMPLE = [
        self::PLACEHOLDER_LOSER_SUBJECTIVE => 'him',
        self::PLACEHOLDER_LOSER_OBJECTIVE => 'he',
        self::PLACEHOLDER_LOSER_POSSESSIVE => 'his',
        self::PLACEHOLDER_LOSER_WEAPON => 'Pointy Twig',
        self::PLACEHOLDER_WINNER_WEAPON => 'Sharp Teeth',
        self::PLACEHOLDER_WINNER_NAME => 'Large Green Rat',
        self::PLACEHOLDER_LOSER_NAME => 'JoeBloe',
    ];

    public function __construct(
        private readonly PDO $connection,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function listAll(): array
    {
        $statement = $this->connection->query(
            'SELECT taunt_id, taunt_text, editor_name FROM taunt ORDER BY taunt_id ASC',
        );

        return [
            'placeholder_list' => \array_keys(self::PREVIEW_SAMPLE),
            'taunt_list' => \array_map(
                fn (array $row): array => [
                    'taunt_id' => (int) $row['taunt_id'],
                    'taunt_text' => (string) $row['taunt_text'],
                    'editor_name' => (string) ($row['editor_name'] ?? ''),
                    'preview_text' => $this->buildPreview((string) $row['taunt_text']),
                ],
                $statement === false ? [] : $statement->fetchAll(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function save(int $tauntId, string $tauntText, string $editorLoginName): array
    {
        $trimmedText = \trim($tauntText);

        if ($trimmedText === '') {
            return ['saved' => false, 'message_key' => 'taunt.error.empty-text'];
        }

        if ($tauntId > 0) {
            $statement = $this->connection->prepare(
                'UPDATE taunt
                    SET taunt_text        = :taunt_text,
                        editor_name = :editor_name
                  WHERE taunt_id = :taunt_id',
            );
            $statement->execute([
                'taunt_text' => $trimmedText,
                'editor_name' => $editorLoginName,
                'taunt_id' => $tauntId,
            ]);

            if ($statement->rowCount() === 0) {
                return ['saved' => false, 'message_key' => 'taunt.error.not-found'];
            }

            return [
                'saved' => true,
                'taunt_id' => $tauntId,
                'preview_text' => $this->buildPreview($trimmedText),
            ];
        }

        $this->connection
            ->prepare(
                'INSERT INTO taunt (taunt_text, editor_name)
                 VALUES (:taunt_text, :editor_name)',
            )
            ->execute([
                'taunt_text' => $trimmedText,
                'editor_name' => $editorLoginName,
            ]);

        return [
            'saved' => true,
            'taunt_id' => (int) $this->connection->lastInsertId(),
            'preview_text' => $this->buildPreview($trimmedText),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(int $tauntId): array
    {
        $statement = $this->connection->prepare('DELETE FROM taunt WHERE taunt_id = :taunt_id');
        $statement->execute(['taunt_id' => $tauntId]);

        return ['removed' => $statement->rowCount() > 0];
    }

    private function buildPreview(string $tauntText): string
    {
        return \str_replace(
            \array_keys(self::PREVIEW_SAMPLE),
            \array_values(self::PREVIEW_SAMPLE),
            $tauntText,
        );
    }
}
