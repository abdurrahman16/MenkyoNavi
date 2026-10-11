<?php

namespace App\Services\Imports;

use RuntimeException;

/**
 * Restricted static extractor for JavaScript array/object/string/number/boolean literals.
 *
 * Does not evaluate JavaScript or load HTML in a browser.
 */
class JsLiteralExtractor
{
    public function extractConstValue(string $html, string $name): mixed
    {
        $pattern = '/\bconst\s+'.preg_quote($name, '/').'\s*=\s*/';

        if (! preg_match($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException("Could not locate const {$name} assignment.");
        }

        $offset = $matches[0][1] + strlen($matches[0][0]);
        $offset = $this->skipWhitespaceAndComments($html, $offset);

        return $this->parseJsValue($html, $offset);
    }

    public function extractConstArray(string $html, string $name): mixed
    {
        $value = $this->extractConstValue($html, $name);

        if (! is_array($value)) {
            throw new RuntimeException("const {$name} is not an array literal.");
        }

        return $value;
    }

    /**
     * @return list<mixed>
     */
    public function extractPushArrays(string $html, string $arrayName): array
    {
        $pattern = '/\b'.preg_quote($arrayName, '/').'\.push\s*\(/';
        preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE);

        $arrays = [];

        foreach ($matches[0] as $match) {
            $offset = $match[1] + strlen($match[0]);
            $offset = $this->skipWhitespaceAndComments($html, $offset);

            if (($html[$offset] ?? '') !== '[') {
                throw new RuntimeException("{$arrayName}.push(...) does not start with an array literal.");
            }

            $value = $this->parseJsValue($html, $offset);

            if (! is_array($value)) {
                throw new RuntimeException("{$arrayName}.push(...) payload is not an array.");
            }

            $arrays[] = $value;
        }

        return $arrays;
    }

    public function extractConstObjectLiteral(string $html, string $name): string
    {
        $pattern = '/\bconst\s+'.preg_quote($name, '/').'\s*=\s*/';

        if (! preg_match($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
            return '';
        }

        $offset = $matches[0][1] + strlen($matches[0][0]);
        $offset = $this->skipWhitespaceAndComments($html, $offset);

        if (($html[$offset] ?? '') !== '{') {
            return '';
        }

        $end = $this->findMatchingBracket($html, $offset, '{', '}');

        return substr($html, $offset, $end - $offset + 1);
    }

    public function parseJsValue(string $source, int &$offset): mixed
    {
        $offset = $this->skipWhitespaceAndComments($source, $offset);
        $char = $source[$offset] ?? '';

        if ($char === '[') {
            return $this->parseJsArray($source, $offset);
        }

        if ($char === '{') {
            return $this->parseJsObject($source, $offset);
        }

        if ($char === '"' || $char === "'") {
            return $this->parseJsString($source, $offset);
        }

        if ($char === '-' || ctype_digit($char)) {
            return $this->parseJsNumber($source, $offset);
        }

        if (preg_match('/\Gtrue\b/', $source, $matches, 0, $offset) === 1) {
            $offset += 4;

            return true;
        }

        if (preg_match('/\Gfalse\b/', $source, $matches, 0, $offset) === 1) {
            $offset += 5;

            return false;
        }

        if (preg_match('/\Gnull\b/', $source, $matches, 0, $offset) === 1) {
            $offset += 4;

            return null;
        }

        throw new RuntimeException('Unsupported JavaScript literal near offset '.$offset.'.');
    }

    /**
     * @return list<mixed>
     */
    private function parseJsArray(string $source, int &$offset): array
    {
        $offset++; // skip [
        $items = [];

        while (true) {
            $offset = $this->skipWhitespaceAndComments($source, $offset);
            $char = $source[$offset] ?? '';

            if ($char === ']') {
                $offset++;

                return $items;
            }

            if ($char === ',') {
                $offset++;

                continue;
            }

            $items[] = $this->parseJsValue($source, $offset);
            $offset = $this->skipWhitespaceAndComments($source, $offset);
            $next = $source[$offset] ?? '';

            if ($next === ',') {
                $offset++;

                continue;
            }

            if ($next === ']') {
                $offset++;

                return $items;
            }

            throw new RuntimeException('Malformed array literal near offset '.$offset.'.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsObject(string $source, int &$offset): array
    {
        $offset++; // skip {
        $object = [];

        while (true) {
            $offset = $this->skipWhitespaceAndComments($source, $offset);
            $char = $source[$offset] ?? '';

            if ($char === '}') {
                $offset++;

                return $object;
            }

            if ($char === ',') {
                $offset++;

                continue;
            }

            $key = $this->parseJsObjectKey($source, $offset);
            $offset = $this->skipWhitespaceAndComments($source, $offset);

            if (($source[$offset] ?? '') !== ':') {
                throw new RuntimeException('Malformed object literal: expected ":" near offset '.$offset.'.');
            }

            $offset++;
            $object[$key] = $this->parseJsValue($source, $offset);
            $offset = $this->skipWhitespaceAndComments($source, $offset);
            $next = $source[$offset] ?? '';

            if ($next === ',') {
                $offset++;

                continue;
            }

            if ($next === '}') {
                $offset++;

                return $object;
            }

            throw new RuntimeException('Malformed object literal near offset '.$offset.'.');
        }
    }

    private function parseJsObjectKey(string $source, int &$offset): string
    {
        $offset = $this->skipWhitespaceAndComments($source, $offset);
        $char = $source[$offset] ?? '';

        if ($char === '"' || $char === "'") {
            return $this->parseJsString($source, $offset);
        }

        if (preg_match('/\G[A-Za-z_][A-Za-z0-9_]*/', $source, $matches, 0, $offset) === 1) {
            $offset += strlen($matches[0]);

            return $matches[0];
        }

        throw new RuntimeException('Malformed object key near offset '.$offset.'.');
    }

    private function parseJsString(string $source, int &$offset): string
    {
        $quote = $source[$offset];
        $offset++;
        $result = '';
        $length = strlen($source);

        while ($offset < $length) {
            $char = $source[$offset];

            if ($char === $quote) {
                $offset++;

                return $result;
            }

            if ($char === '\\') {
                $offset++;
                $escaped = $source[$offset] ?? '';

                $result .= match ($escaped) {
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    '"' => '"',
                    "'" => "'",
                    '\\' => '\\',
                    '/' => '/',
                    default => $escaped,
                };
                $offset++;

                continue;
            }

            $result .= $char;
            $offset++;
        }

        throw new RuntimeException('Unterminated string literal near offset '.$offset.'.');
    }

    private function parseJsNumber(string $source, int &$offset): int|float
    {
        if (! preg_match('/\G-?\d+(?:\.\d+)?/', $source, $matches, 0, $offset)) {
            throw new RuntimeException('Invalid numeric literal near offset '.$offset.'.');
        }

        $offset += strlen($matches[0]);
        $raw = $matches[0];

        return str_contains($raw, '.') ? (float) $raw : (int) $raw;
    }

    public function skipWhitespaceAndComments(string $source, int $offset): int
    {
        $length = strlen($source);

        while ($offset < $length) {
            $char = $source[$offset];

            if ($char === ' ' || $char === "\n" || $char === "\r" || $char === "\t") {
                $offset++;

                continue;
            }

            if ($char === '/' && ($source[$offset + 1] ?? '') === '/') {
                $newline = strpos($source, "\n", $offset);
                $offset = $newline === false ? $length : $newline + 1;

                continue;
            }

            if ($char === '/' && ($source[$offset + 1] ?? '') === '*') {
                $end = strpos($source, '*/', $offset + 2);
                $offset = $end === false ? $length : $end + 2;

                continue;
            }

            break;
        }

        return $offset;
    }

    public function findMatchingBracket(string $source, int $start, string $open, string $close): int
    {
        $depth = 0;
        $length = strlen($source);
        $inString = false;
        $stringQuote = '';
        $escaped = false;

        for ($i = $start; $i < $length; $i++) {
            $char = $source[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;

                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;

                    continue;
                }

                if ($char === $stringQuote) {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $inString = true;
                $stringQuote = $char;

                continue;
            }

            if ($char === '/' && ($source[$i + 1] ?? '') === '/') {
                $newline = strpos($source, "\n", $i);
                $i = $newline === false ? $length : $newline;

                continue;
            }

            if ($char === '/' && ($source[$i + 1] ?? '') === '*') {
                $end = strpos($source, '*/', $i + 2);
                $i = $end === false ? $length : $end + 1;

                continue;
            }

            if ($char === $open) {
                $depth++;

                continue;
            }

            if ($char === $close) {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        throw new RuntimeException("Unbalanced {$open}{$close} near offset {$start}.");
    }
}
