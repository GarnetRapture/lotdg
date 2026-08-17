<?php

declare(strict_types=1);

namespace Lotdg\Domain\Social;

use Lotdg\Persistence\Repository\GameSettingRepository;
use PDO;

final class BadWordFilter
{
    private const string MASK = '`i$@#%`i';

    /** @var array<string, string> */
    private const array LEET_SUBSTITUTION = [
        'a' => '[a4@]',
        'l' => '[l1!]',
        'i' => '[li1!]',
        'e' => '[e3]',
        't' => '[t7+]',
        'o' => '[o0]',
        's' => '[sz$]',
        'c' => '[c(k]',
    ];

    /** @var list<string>|null */
    private ?array $cachedPatternList = null;

    public function __construct(
        private readonly PDO $connection,
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    public function clean(string $input): string
    {
        if (!$this->gameSettingRepository->getBool('soap', true)) {
            return $input;
        }

        $result = $input;

        foreach ($this->loadPatternList() as $pattern) {
            $replaced = \preg_replace($pattern, '$1' . self::MASK . '$2', $result);

            if ($replaced !== null) {
                $result = $replaced;
            }
        }

        return $result;
    }

    public function containsBadWord(string $input): bool
    {
        return $this->clean($input) !== $input;
    }

    /**
     * @return array<string, mixed>
     */
    public function listWord(): array
    {
        $statement = $this->connection->query(
            'SELECT word_pattern FROM nasty_word ORDER BY word_pattern ASC',
        );

        $groupMap = [];

        foreach ($statement === false ? [] : $statement->fetchAll() as $row) {
            $wordPattern = (string) $row['word_pattern'];
            $initial = \strtoupper(\mb_substr(\ltrim($wordPattern, '*'), 0, 1));
            $groupMap[$initial][] = $wordPattern;
        }

        return ['group_map' => (object) $groupMap];
    }

    /**
     * @return array<string, mixed>
     */
    public function addWord(string $wordPattern): array
    {
        $normalized = \strtolower(\trim($wordPattern));

        if ($normalized === '' || $normalized === '*') {
            return ['added' => false, 'message_key' => 'badword.error.empty-word'];
        }

        $statement = $this->connection->prepare(
            'INSERT INTO nasty_word (word_pattern) VALUES (:word_pattern)
             ON CONFLICT(word_pattern) DO NOTHING',
        );
        $statement->execute(['word_pattern' => $normalized]);

        $this->cachedPatternList = null;

        return ['added' => $statement->rowCount() > 0, 'word_pattern' => $normalized];
    }

    /**
     * @return array<string, mixed>
     */
    public function removeWord(string $wordPattern): array
    {
        $statement = $this->connection->prepare(
            'DELETE FROM nasty_word WHERE word_pattern = :word_pattern',
        );
        $statement->execute(['word_pattern' => \strtolower(\trim($wordPattern))]);

        $this->cachedPatternList = null;

        return ['removed' => $statement->rowCount() > 0];
    }

    /**
     * @return array<string, mixed>
     */
    public function testWord(string $input): array
    {
        return [
            'input_text' => $input,
            'filtered_text' => $this->clean($input),
            'filter_enabled' => $this->gameSettingRepository->getBool('soap', true),
        ];
    }

    /**
     * @return list<string>
     */
    private function loadPatternList(): array
    {
        if ($this->cachedPatternList !== null) {
            return $this->cachedPatternList;
        }

        $statement = $this->connection->query('SELECT word_pattern FROM nasty_word');
        $patternList = [];

        foreach ($statement === false ? [] : $statement->fetchAll() as $row) {
            $pattern = $this->buildPattern((string) $row['word_pattern']);

            if ($pattern !== null) {
                $patternList[] = $pattern;
            }
        }

        $this->cachedPatternList = $patternList;

        return $patternList;
    }

    private function buildPattern(string $wordPattern): ?string
    {
        $normalized = \strtolower(\trim($wordPattern));

        if ($normalized === '') {
            return null;
        }

        $body = '';
        $length = \strlen($normalized);

        for ($index = 0; $index < $length; ++$index) {
            $character = $normalized[$index];

            if ($character === '*') {
                $body .= '[[:alnum:]]*';

                continue;
            }

            if ($character === 'k') {
                $body .= self::LEET_SUBSTITUTION['c'];

                continue;
            }

            $body .= self::LEET_SUBSTITUTION[$character] ?? \preg_quote($character, '/');
        }

        return '/(\s|\A)' . $body . '(\s|\Z)/iu';
    }
}
