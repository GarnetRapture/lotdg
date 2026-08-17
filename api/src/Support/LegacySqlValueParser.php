<?php

declare(strict_types=1);

namespace Lotdg\Support;

final class LegacySqlValueParser
{
    /**
     * @return list<list<string|null>>
     */
    public function parseInsertStatementList(string $sql, string $tableName): array
    {
        $rowList = [];
        $pattern = '/INSERT\s+INTO\s+' . \preg_quote($tableName, '/') . '\s*(?:\([^)]*\))?\s*VALUES\s*/i';
        $offset = 0;

        while (\preg_match($pattern, $sql, $match, \PREG_OFFSET_CAPTURE, $offset) === 1) {
            $cursor = $match[0][1] + \strlen($match[0][0]);
            $rowList[] = $this->readValueGroup($sql, $cursor);
            $offset = $cursor;
        }

        return $rowList;
    }

    /**
     * @return list<int> DELETE FROM <table> 
     */
    public function findDeleteOffsetList(string $sql, string $tableName): array
    {
        $offsetList = [];
        $pattern = '/DELETE\s+FROM\s+' . \preg_quote($tableName, '/') . '\s*;/i';

        if (\preg_match_all($pattern, $sql, $matchList, \PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        foreach ($matchList[0] as $match) {
            $offsetList[] = $match[1];
        }

        return $offsetList;
    }

    public function findInsertOffset(string $sql, string $tableName, int $occurrenceIndex): ?int
    {
        $pattern = '/INSERT\s+INTO\s+' . \preg_quote($tableName, '/') . '\s*(?:\([^)]*\))?\s*VALUES\s*/i';

        if (\preg_match_all($pattern, $sql, $matchList, \PREG_OFFSET_CAPTURE) === false) {
            return null;
        }

        return $matchList[0][$occurrenceIndex][1] ?? null;
    }

    /**
     * @param int $cursor 여는 괄호 위치. 호출 후 닫는 괄호 다음으로 이동한다.
     *
     * @return list<string|null>
     */
    private function readValueGroup(string $sql, int &$cursor): array
    {
        $length = \strlen($sql);

        while ($cursor < $length && $sql[$cursor] !== '(') {
            ++$cursor;
        }

        ++$cursor;

        $valueList = [];
        $buffer = '';
        $isQuoted = false;
        $wasQuoted = false;
        $quoteCharacter = "'";

        while ($cursor < $length) {
            $character = $sql[$cursor];

            if ($isQuoted) {
                if ($character === '\\' && $cursor + 1 < $length) {
                    $buffer .= $this->decodeEscape($sql[$cursor + 1]);
                    $cursor += 2;

                    continue;
                }

                if ($character === $quoteCharacter) {
                    if (($sql[$cursor + 1] ?? '') === $quoteCharacter) {
                        $buffer .= $quoteCharacter;
                        $cursor += 2;

                        continue;
                    }

                    $isQuoted = false;
                    ++$cursor;

                    continue;
                }

                $buffer .= $character;
                ++$cursor;

                continue;
            }

            if ($character === "'" || $character === '"') {
                if (\trim($buffer) === '') {
                    $buffer = '';
                }

                $isQuoted = true;
                $wasQuoted = true;
                $quoteCharacter = $character;
                ++$cursor;

                continue;
            }

            if ($character === ',') {
                $valueList[] = $this->normalizeValue($buffer, $wasQuoted);
                $buffer = '';
                $wasQuoted = false;
                ++$cursor;

                continue;
            }

            if ($character === ')') {
                $valueList[] = $this->normalizeValue($buffer, $wasQuoted);
                ++$cursor;

                return $valueList;
            }

            $buffer .= $character;
            ++$cursor;
        }

        $valueList[] = $this->normalizeValue($buffer, $wasQuoted);

        return $valueList;
    }

    private function decodeEscape(string $escapedCharacter): string
    {
        return match ($escapedCharacter) {
            'n' => "\n",
            'r' => "\r",
            't' => "\t",
            '0' => "\0",
            default => $escapedCharacter,
        };
    }

    private function normalizeValue(string $rawValue, bool $wasQuoted): ?string
    {
        if ($wasQuoted) {
            return $rawValue;
        }

        $trimmed = \trim($rawValue);

        return \strcasecmp($trimmed, 'NULL') === 0 ? null : $trimmed;
    }
}
