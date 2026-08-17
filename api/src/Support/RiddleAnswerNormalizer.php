<?php

declare(strict_types=1);

namespace Lotdg\Support;

final class RiddleAnswerNormalizer
{
    private const string ANSWER_SEPARATOR = ';';

    /** @var list<string> */
    private const STOP_WORD_LIST = [
        'a', 'an', 'and', 'the', 'my', 'your',
        'someones', "someone's", 'someone', 'his', 'her', 's',
    ];

    /** @var list<string> */
    private const SUFFIX_LIST = ['s', 'ing', 'ed'];

    public function matches(string $guessText, string $answerText, int $tolerance): bool
    {
        $normalizedGuess = $this->normalize($guessText);

        if ($normalizedGuess === '') {
            return false;
        }

        foreach (\explode(self::ANSWER_SEPARATOR, $answerText) as $candidate) {
            $normalizedCandidate = $this->normalize($candidate);

            if ($normalizedCandidate === '') {
                continue;
            }

            if (\levenshtein($normalizedGuess, $normalizedCandidate) <= $tolerance) {
                return true;
            }
        }

        return false;
    }

    public function normalize(string $input): string
    {
        $lowered = \mb_strtolower(\trim($input));
        $stripped = \preg_replace('/[^\p{L}\p{N}\s]/u', '', $lowered) ?? $lowered;
        $wordList = \preg_split('/\s+/u', \trim($stripped)) ?: [];

        $normalizedList = [];

        foreach ($wordList as $word) {
            if ($word === '' || \in_array($word, self::STOP_WORD_LIST, true)) {
                continue;
            }

            $normalizedList[] = $this->stripSuffix($word);
        }

        return \implode('', $normalizedList);
    }

    private function stripSuffix(string $word): string
    {
        foreach (self::SUFFIX_LIST as $suffix) {
            if (\mb_strlen($word) > \mb_strlen($suffix) && \str_ends_with($word, $suffix)) {
                return \mb_substr($word, 0, \mb_strlen($word) - \mb_strlen($suffix));
            }
        }

        return $word;
    }
}
