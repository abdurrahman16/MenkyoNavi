<?php

namespace App\Services\Translations;

use App\Models\QuestionImportEntry;
use App\Support\SupportedLocale;

class TranslationQualityChecker
{
    /**
     * @param  array{
     *     locale?: string|null,
     *     question_text?: string|null,
     *     explanation?: string|null,
     *     figure_caption?: string|null,
     *     nuance_note?: string|null
     * }  $payload
     * @return list<string>
     */
    public function check(QuestionImportEntry $entry, array $payload): array
    {
        $flags = [];
        $locale = (string) ($payload['locale'] ?? '');

        if ($locale === '' || ! SupportedLocale::isSupported($locale)) {
            $flags[] = 'wrong_language_code';
        }

        $questionText = $this->normalizeNullableString($payload['question_text'] ?? null);
        $explanation = $this->normalizeNullableString($payload['explanation'] ?? null);
        $figureCaption = $this->normalizeNullableString($payload['figure_caption'] ?? null);

        if ($questionText === null || $questionText === '') {
            $flags[] = 'missing_translation_text';
        }

        if ($explanation === null || $explanation === '') {
            $flags[] = 'missing_explanation';
        }

        foreach (['question_text' => $questionText, 'explanation' => $explanation, 'figure_caption' => $figureCaption] as $field => $value) {
            if ($value === null) {
                continue;
            }

            if ($value === '') {
                $flags[] = 'empty_string_'.$field;
            }

            if (! mb_check_encoding($value, 'UTF-8')) {
                $flags[] = 'invalid_unicode_'.$field;
            }

            if ($this->containsUnsafeMarkup($value)) {
                $flags[] = 'unsafe_markup_'.$field;
            }

            if ($this->containsUnexpectedHtml($value)) {
                $flags[] = 'unexpected_html_'.$field;
            }
        }

        if ($entry->figure_caption !== null && ($figureCaption === null || $figureCaption === '')) {
            $flags[] = 'missing_figure_caption';
        }

        if ($questionText !== null && $questionText !== '') {
            if ($locale !== 'en' && $this->looksUntranslatedEnglish($questionText, $entry->english_question)) {
                $flags[] = 'untranslated_english_question';
            }

            if ($this->numbersAltered($entry->english_question, $questionText)) {
                $flags[] = 'potentially_altered_numbers';
            }

            if ($this->unitsMissing($entry->english_question, $questionText)) {
                $flags[] = 'missing_measurement_units';
            }

            if ($this->structureChanged($entry->english_question, $questionText)) {
                $flags[] = 'unexpected_structure_change';
            }

            if ($locale !== 'en' && $this->negationMissing($entry->english_question, $questionText)) {
                $flags[] = 'missing_negation';
            }
        }

        if ($explanation !== null && $explanation !== '' && $locale !== 'en'
            && $this->looksUntranslatedEnglish($explanation, $entry->english_explanation)) {
            $flags[] = 'untranslated_english_explanation';
        }

        if ($explanation !== null && $explanation !== '' && $locale !== 'en'
            && $this->negationMissing($entry->english_explanation, $explanation)) {
            $flags[] = 'missing_negation_explanation';
        }

        return array_values(array_unique($flags));
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return $value;
    }

    private function containsUnexpectedHtml(string $value): bool
    {
        return preg_match('/<\s*\/?\s*[a-zA-Z][^>]*>/', $value) === 1;
    }

    private function containsUnsafeMarkup(string $value): bool
    {
        return preg_match('/<\s*(script|iframe|object|embed|link|style)\b/i', $value) === 1
            || preg_match('/\bon\w+\s*=/i', $value) === 1
            || preg_match('/javascript\s*:/i', $value) === 1;
    }

    private function looksUntranslatedEnglish(string $candidate, string $englishSource): bool
    {
        $normalizedCandidate = $this->collapse($candidate);
        $normalizedSource = $this->collapse($englishSource);

        if ($normalizedCandidate === $normalizedSource) {
            return true;
        }

        similar_text($normalizedCandidate, $normalizedSource, $percent);

        return $percent >= 92.0;
    }

    private function numbersAltered(string $english, string $translation): bool
    {
        preg_match_all('/\d+(?:\.\d+)?/', $english, $englishNumbers);
        preg_match_all('/\d+(?:\.\d+)?/', $translation, $translationNumbers);

        $englishList = $englishNumbers[0] ?? [];
        $translationList = $translationNumbers[0] ?? [];

        if ($englishList === []) {
            return false;
        }

        sort($englishList);
        sort($translationList);

        return $englishList !== $translationList;
    }

    private function unitsMissing(string $english, string $translation): bool
    {
        $units = [];

        if (preg_match_all('/\b(km\/h|m|cm|mm|kg|t|cc|seconds?|minutes?|meters?)\b/i', $english, $matches) === false) {
            return false;
        }

        foreach ($matches[1] as $unit) {
            $units[] = strtolower($unit);
        }

        $units = array_values(array_unique($units));

        foreach ($units as $unit) {
            if (preg_match('/\b'.preg_quote($unit, '/').'\b/i', $translation) !== 1
                && ! $this->hasLocalizedUnitEquivalent($unit, $translation)) {
                return true;
            }
        }

        return false;
    }

    private function hasLocalizedUnitEquivalent(string $unit, string $translation): bool
    {
        $equivalents = match (strtolower($unit)) {
            'km/h' => ['km/h', 'kmh', 'キロ', 'km/시', '千米/小时', 'km/jam', 'किमी', 'কিমি', 'km/giờ'],
            'm', 'meters', 'meter' => ['m', 'メートル', '미터', '米', 'मिटर', 'মিটার', 'mét', 'métơ'],
            'seconds', 'second' => ['second', '秒', '초', 'วินาที', 'giây', 'सेकेन्ड', 'সেকেন্ড', 'segundo', 'segundos', 'सेकेण्ड'],
            'minutes', 'minute' => ['minute', '分', '분', 'phút', 'मिनट', 'মিনিট'],
            default => [],
        };

        foreach ($equivalents as $equivalent) {
            if (str_contains(mb_strtolower($translation), mb_strtolower($equivalent))) {
                return true;
            }
        }

        return false;
    }

    private function structureChanged(string $english, string $translation): bool
    {
        $englishClauses = substr_count($english, ',') + substr_count($english, ';');
        $translationClauses = substr_count($translation, ',') + substr_count($translation, ';')
            + substr_count($translation, '、') + substr_count($translation, '；');

        return abs($englishClauses - $translationClauses) >= 4;
    }

    /**
     * Flag translations that drop clear English negation / prohibition markers.
     */
    private function negationMissing(string $english, string $translation): bool
    {
        if (! $this->englishHasNegation($english)) {
            return false;
        }

        return ! $this->translationHasNegation($translation);
    }

    private function englishHasNegation(string $english): bool
    {
        return preg_match(
            '/\b(must not|may not|cannot|can\'t|should not|shall not|do not|does not|did not|is not|are not|was not|were not|no |never|not |prohibited|forbidden|without|unless)\b/i',
            $english
        ) === 1;
    }

    private function translationHasNegation(string $translation): bool
    {
        // CJK and other scripts do not use ASCII word boundaries reliably.
        if (preg_match(
            '/(ない|ません|ぬ|ず|禁止|不可|いけな|ならな|できな|なく|非|無|勿|不是|不表示|不得|不能|不要|无需|没有|并非|不许|不允许|不必|不会|并不|不影响|금지|안 된다|않아서|않으며|않는다|못 |아니|없다|없는|아닌|아니라|제외|không|chưa|cấm|trừ khi|bawal|hindi|huwag|walang|ipinagbabawal|maliban|न|नाइ|होइन|निषेध|ना|না|নেই|নয়|নিষিদ্ধ)/iu',
            $translation
        ) === 1) {
            return true;
        }

        return preg_match(
            '/\b(not|no|never|must not|may not|cannot|can\'t|prohibited|forbidden|without|unless|tidak)\b/iu',
            $translation
        ) === 1;
    }

    private function collapse(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim(mb_strtolower($value)));

        return is_string($normalized) ? $normalized : trim(mb_strtolower($value));
    }
}
