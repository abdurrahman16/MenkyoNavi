<?php

namespace App\Services\Editorial;

use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Resolves SIGNS assets from private import HTML for admin preview only.
 *
 * Production HTML stores many signs as JS helper calls (S.blueC / S.redC / …),
 * not raw SVG literals. This resolver reconstructs SVG when possible.
 */
class KarimenTest1SignAssetResolver
{
    /**
     * @return array{
     *     sign_key: ?string,
     *     present_in_signs_object: bool,
     *     html_source_path: ?string,
     *     html_source_exists: bool,
     *     expression: ?string,
     *     svg_markup: ?string,
     *     classification: string,
     *     notes: string
     * }
     */
    public function resolve(QuestionImportEntry $entry): array
    {
        $signKey = filled($entry->sign_key) ? (string) $entry->sign_key : null;
        $batch = $entry->batch;
        $path = $this->resolveHtmlPath($batch);

        if ($signKey === null) {
            return [
                'sign_key' => null,
                'present_in_signs_object' => false,
                'html_source_path' => $path,
                'html_source_exists' => $path !== null && is_file($path),
                'expression' => null,
                'svg_markup' => null,
                'classification' => 'not_figure_dependent',
                'notes' => 'No sign_key on this entry.',
            ];
        }

        if ($path === null || ! is_file($path)) {
            return [
                'sign_key' => $signKey,
                'present_in_signs_object' => false,
                'html_source_path' => $path,
                'html_source_exists' => false,
                'expression' => null,
                'svg_markup' => null,
                'classification' => 'missing',
                'notes' => 'Import HTML source file not found under private storage.',
            ];
        }

        $html = File::get($path);
        $expression = $this->extractSignExpression($html, $signKey);

        if ($expression === null) {
            return [
                'sign_key' => $signKey,
                'present_in_signs_object' => false,
                'html_source_path' => $path,
                'html_source_exists' => true,
                'expression' => null,
                'svg_markup' => null,
                'classification' => 'missing',
                'notes' => 'sign_key not found in the SIGNS object.',
            ];
        }

        $svg = $this->expressionToSvg($expression);
        $classification = $svg !== null
            ? 'present_technically_valid_editorial_review_pending'
            : 'present_js_helper_unrendered';

        return [
            'sign_key' => $signKey,
            'present_in_signs_object' => true,
            'html_source_path' => $path,
            'html_source_exists' => true,
            'expression' => $expression,
            'svg_markup' => $svg,
            'classification' => $classification,
            'notes' => $svg !== null
                ? 'Sign present in private import HTML SIGNS library. Reconstructed SVG is for admin preview only — not editorial verification or rights clearance.'
                : 'Sign key present as a JS expression that could not be safely reconstructed; human review of source HTML required.',
        ];
    }

    public function resolveHtmlPath(?QuestionImportBatch $batch): ?string
    {
        if ($batch === null || blank($batch->source_relative_path)) {
            return null;
        }

        $relative = str_replace('\\', '/', (string) $batch->source_relative_path);
        $candidate = storage_path('app/'.$relative);

        return is_file($candidate) ? $candidate : null;
    }

    public function extractSignExpression(string $html, string $signKey): ?string
    {
        if (! preg_match('/const\s+SIGNS\s*=\s*\{([\s\S]*?)\n\s*\};/m', $html, $block)) {
            return null;
        }

        $body = $block[1];
        $pattern = '/\b'.preg_quote($signKey, '/').'\s*:\s*/';
        if (! preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start = $match[0][1] + strlen($match[0][0]);
        $slice = substr($body, $start);
        $depth = 0;
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $escape = false;
        $buffer = '';

        $length = strlen($slice);
        for ($i = 0; $i < $length; $i++) {
            $ch = $slice[$i];

            if ($escape) {
                $buffer .= $ch;
                $escape = false;

                continue;
            }

            if (($inSingle || $inDouble || $inBacktick) && $ch === '\\') {
                $buffer .= $ch;
                $escape = true;

                continue;
            }

            if (! $inDouble && ! $inBacktick && $ch === "'") {
                $inSingle = ! $inSingle;
                $buffer .= $ch;

                continue;
            }

            if (! $inSingle && ! $inBacktick && $ch === '"') {
                $inDouble = ! $inDouble;
                $buffer .= $ch;

                continue;
            }

            if (! $inSingle && ! $inDouble && $ch === '`') {
                $inBacktick = ! $inBacktick;
                $buffer .= $ch;

                continue;
            }

            if ($inSingle || $inDouble || $inBacktick) {
                $buffer .= $ch;

                continue;
            }

            if ($ch === '(' || $ch === '{' || $ch === '[') {
                $depth++;
                $buffer .= $ch;

                continue;
            }

            if ($ch === ')' || $ch === '}' || $ch === ']') {
                $depth = max(0, $depth - 1);
                $buffer .= $ch;

                continue;
            }

            if (($ch === ',' || $ch === "\n") && $depth === 0) {
                break;
            }

            $buffer .= $ch;
        }

        $expression = trim($buffer);

        return $expression !== '' ? rtrim($expression, ',') : null;
    }

    /**
     * Backward-compatible alias used by older call sites/tests.
     */
    public function extractSignSvg(string $html, string $signKey): ?string
    {
        $expression = $this->extractSignExpression($html, $signKey);

        return $expression === null ? null : $this->expressionToSvg($expression);
    }

    public function expressionToSvg(string $expression): ?string
    {
        $expression = trim($expression);

        if (str_starts_with($expression, '`') && str_ends_with($expression, '`')) {
            $raw = substr($expression, 1, -1);

            return str_contains(mb_strtolower($raw), '<svg') ? $raw : $this->svgWrap($raw);
        }

        if (preg_match('/^svgWrap\(\s*[\'`]([\s\S]*?)[\'`]\s*(?:,\s*\d+)?\s*\)$/', $expression, $match)) {
            return $this->svgWrap($match[1]);
        }

        if (preg_match('/^S\.blueC\(\s*[\'`]([\s\S]*?)[\'`]\s*(?:,\s*\d+)?\s*\)$/', $expression, $match)) {
            return $this->svgWrap('<circle cx="22" cy="22" r="21" fill="#1D5BBF"/>'.$match[1]);
        }

        if (preg_match('/^S\.redC\(\s*[\'`]([\s\S]*?)[\'`]\s*(?:,\s*\d+)?\s*\)$/', $expression, $match)) {
            return $this->svgWrap('<circle cx="22" cy="22" r="20" fill="#fff" stroke="#C73E2E" stroke-width="5"/>'.$match[1]);
        }

        if (preg_match('/^S\.yDia\(\s*[\'`]([\s\S]*?)[\'`]\s*\)$/', $expression, $match)) {
            return $this->svgWrap('<rect x="7" y="7" width="30" height="30" rx="3" transform="rotate(45 22 22)" fill="#F2B705" stroke="#1B2A4A" stroke-width="1.5"/>'.$match[1]);
        }

        if (preg_match('/^S\.redTri\(\s*[\'`]([\s\S]*?)[\'`]\s*\)$/', $expression, $match)) {
            $text = htmlspecialchars($match[1], ENT_QUOTES | ENT_XML1);

            return $this->svgWrap('<path d="M22 41 L3 6 L41 6 Z" fill="#C73E2E"/><text x="22" y="22" font-size="9.5" fill="#fff" text-anchor="middle" font-weight="900">'.$text.'</text>');
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function listSignKeysInBatch(?QuestionImportBatch $batch): array
    {
        $path = $this->resolveHtmlPath($batch);
        if ($path === null || ! is_file($path)) {
            return [];
        }

        $html = File::get($path);
        if (! preg_match('/const\s+SIGNS\s*=\s*\{([\s\S]*?)\n\s*\};/m', $html, $block)) {
            throw new RuntimeException('SIGNS object not found in import HTML.');
        }

        preg_match_all('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*:/m', $block[1], $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    public function sanitizeSvgMarkup(string $svg): string
    {
        $sanitized = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg) ?? $svg;
        $sanitized = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/\s(on\w+|formaction|xlink:href)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/javascript\s*:/i', '', $sanitized) ?? $sanitized;

        return trim($sanitized);
    }

    private function svgWrap(string $inner, int $width = 44): string
    {
        return '<svg width="'.$width.'" height="44" viewBox="0 0 44 44" xmlns="http://www.w3.org/2000/svg">'.$inner.'</svg>';
    }
}
