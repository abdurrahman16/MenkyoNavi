<?php

namespace App\Services\Exams;

use App\Models\ExamAttemptQuestion;
use App\Support\LocalePreference;
use App\Support\SafeRubyMarkup;
use App\Support\SupportedLocale;
use Illuminate\Support\Facades\Storage;

class ExamQuestionPresenter
{
    public function __construct(public LocalePreference $locales) {}

    /**
     * @return array<string, mixed>
     */
    public function forExam(ExamAttemptQuestion $question, string $locale): array
    {
        $snapshot = $question->content_snapshot;
        $locale = $this->normalizeLocale($locale);
        $presentation = $this->presentStem($snapshot, $locale);

        $adminPreview = is_array($snapshot['admin_preview'] ?? null) ? $snapshot['admin_preview'] : null;

        return [
            'id' => $question->id,
            'position' => $question->position,
            'illustrated' => $question->isIllustratedGroup(),
            'category' => $snapshot['category_name'] ?? null,
            'display_locale' => $locale,
            'available' => $presentation['available'],
            'stem_html' => $presentation['stem_html'],
            'stem_lang' => $presentation['stem_lang'],
            'shows_source_japanese' => $presentation['shows_source_japanese'],
            'media' => $this->media($snapshot['media'] ?? []),
            'answered' => $this->isAnswered($question),
            'statements' => $this->statementsForExam($question, $locale),
            'selected' => $question->isIllustratedGroup()
                ? null
                : $this->selectedValue($question, 0),
            'admin_preview' => $adminPreview,
            'needs_clarification' => (bool) data_get($adminPreview, 'needs_clarification', false),
            'candidate_id' => data_get($adminPreview, 'candidate_id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forReview(ExamAttemptQuestion $question, string $locale): array
    {
        $display = $this->forExam($question, $locale);
        $snapshot = $question->content_snapshot;
        $locale = $this->normalizeLocale($locale);
        $explanation = $this->presentExplanation($snapshot, $locale);

        $display['explanation_html'] = $explanation['html'];
        $display['explanation_lang'] = $explanation['lang'];
        $display['explanation_available'] = $explanation['available'];
        $display['nuance_note'] = $explanation['nuance_note'];
        $display['points_awarded'] = (int) $question->points_awarded;
        $display['scoring_weight'] = $question->scoring_weight;
        $display['is_correct'] = $question->is_correct === true;
        $display['correct_answer'] = $question->isIllustratedGroup()
            ? null
            : (bool) ($snapshot['correct_answer'] ?? false);
        $display['statements'] = $this->statementsForReview($question, $locale);

        return $display;
    }

    public function questionIsAnswered(ExamAttemptQuestion $question): bool
    {
        return $this->isAnswered($question);
    }

    public function preferredLocale(): string
    {
        return $this->locales->normalize((string) session('locale', $this->locales->defaultLocale()))
            ?? $this->locales->defaultLocale();
    }

    /**
     * Resolve a question translation for the selected locale only.
     * Never silently substitute English (or any other language) as the selected locale.
     *
     * @param  array<string, array{question_text?: string|null, explanation?: string|null, nuance_note?: string|null}>  $translations
     * @return array{text: ?string, explanation: ?string, nuance_note: ?string, locale: ?string, available: bool}
     */
    public function resolveTranslation(array $translations, string $locale): array
    {
        $locale = $this->normalizeLocale($locale);

        if ($locale === 'ja') {
            return [
                'text' => null,
                'explanation' => null,
                'nuance_note' => null,
                'locale' => 'ja',
                'available' => true,
            ];
        }

        $direct = $translations[$locale] ?? null;

        if (is_array($direct) && filled($direct['question_text'] ?? null)) {
            return [
                'text' => (string) $direct['question_text'],
                'explanation' => filled($direct['explanation'] ?? null) ? (string) $direct['explanation'] : null,
                'nuance_note' => filled($direct['nuance_note'] ?? null) ? (string) $direct['nuance_note'] : null,
                'locale' => $locale,
                'available' => true,
            ];
        }

        return [
            'text' => null,
            'explanation' => null,
            'nuance_note' => null,
            'locale' => null,
            'available' => false,
        ];
    }

    /**
     * Safe ruby markup, or escaped plain text when no reading data exists.
     *
     * @param  list<array{text?: string, reading?: string|null}>|null  $reading
     */
    public function rubyHtml(?array $reading, string $plain): string
    {
        if (is_array($reading) && $reading !== []) {
            return SafeRubyMarkup::fromReadingData($reading);
        }

        return e($plain);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{available: bool, stem_html: string, stem_lang: string, shows_source_japanese: bool}
     */
    private function presentStem(array $snapshot, string $locale): array
    {
        $japaneseHtml = $this->rubyHtml(
            $snapshot['japanese_reading_data'] ?? null,
            (string) ($snapshot['japanese_text'] ?? ''),
        );

        if ($locale === 'ja') {
            return [
                'available' => filled($snapshot['japanese_text'] ?? null),
                'stem_html' => $japaneseHtml,
                'stem_lang' => 'ja',
                'shows_source_japanese' => false,
            ];
        }

        $translation = $this->resolveTranslation($snapshot['translations'] ?? [], $locale);

        if ($translation['available'] && filled($translation['text'])) {
            return [
                'available' => true,
                'stem_html' => e((string) $translation['text']),
                'stem_lang' => $locale,
                'shows_source_japanese' => false,
            ];
        }

        return [
            'available' => false,
            'stem_html' => $japaneseHtml,
            'stem_lang' => 'ja',
            'shows_source_japanese' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{available: bool, html: ?string, lang: ?string, nuance_note: ?string}
     */
    private function presentExplanation(array $snapshot, string $locale): array
    {
        if ($locale === 'ja') {
            $explanation = $snapshot['explanation_ja'] ?? null;

            return [
                'available' => filled($explanation),
                'html' => filled($explanation) ? e((string) $explanation) : null,
                'lang' => 'ja',
                'nuance_note' => null,
            ];
        }

        $translation = $this->resolveTranslation($snapshot['translations'] ?? [], $locale);

        if ($translation['available'] && filled($translation['explanation'])) {
            return [
                'available' => true,
                'html' => e((string) $translation['explanation']),
                'lang' => $locale,
                'nuance_note' => $translation['nuance_note'],
            ];
        }

        $japanese = $snapshot['explanation_ja'] ?? null;

        return [
            'available' => false,
            'html' => filled($japanese) ? e((string) $japanese) : null,
            'lang' => filled($japanese) ? 'ja' : null,
            'nuance_note' => null,
        ];
    }

    /**
     * @param  list<array{disk?: string, path?: string, alt_text?: string|null}>  $media
     * @return array{url: string, alt: string}|null
     */
    private function media(array $media): ?array
    {
        $first = $media[0] ?? null;

        if (! is_array($first)) {
            return null;
        }

        $path = (string) ($first['path'] ?? '');

        if ($path === '' || str_contains($path, '..') || str_contains($path, '://')) {
            return null;
        }

        $disk = (string) ($first['disk'] ?? '');
        $url = null;

        if ($disk === 'web') {
            $url = asset(ltrim($path, '/'));
        } elseif ($disk !== '' && config('filesystems.disks.'.$disk) !== null) {
            $url = Storage::disk($disk)->url($path);
        }

        if ($url === null) {
            return null;
        }

        return [
            'url' => $url,
            'alt' => (string) ($first['alt_text'] ?? ''),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function statementsForExam(ExamAttemptQuestion $question, string $locale): array
    {
        if (! $question->isIllustratedGroup()) {
            return [];
        }

        $rows = [];

        foreach ($question->content_snapshot['statements'] ?? [] as $statement) {
            if (! is_array($statement)) {
                continue;
            }

            $slot = (int) ($statement['sort_order'] ?? 0);
            $presentation = $this->presentStem($statement, $locale);

            $rows[] = [
                'slot' => $slot,
                'available' => $presentation['available'],
                'stem_html' => $presentation['stem_html'],
                'stem_lang' => $presentation['stem_lang'],
                'shows_source_japanese' => $presentation['shows_source_japanese'],
                'selected' => $this->selectedValue($question, $slot),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function statementsForReview(ExamAttemptQuestion $question, string $locale): array
    {
        $rows = $this->statementsForExam($question, $locale);

        foreach ($question->content_snapshot['statements'] ?? [] as $index => $statement) {
            if (! is_array($statement) || ! isset($rows[$index])) {
                continue;
            }

            $explanation = $this->presentExplanation($statement, $locale);
            $rows[$index]['correct_answer'] = (bool) ($statement['correct_answer'] ?? false);
            $rows[$index]['explanation_html'] = $explanation['html'];
            $rows[$index]['explanation_lang'] = $explanation['lang'];
            $rows[$index]['explanation_available'] = $explanation['available'];
            $rows[$index]['nuance_note'] = $explanation['nuance_note'];
            $rows[$index]['is_correct'] = $question->answers->firstWhere('statement_slot', $rows[$index]['slot'])?->is_correct === true
                && $rows[$index]['selected'] !== null;
        }

        return $rows;
    }

    private function selectedValue(ExamAttemptQuestion $question, int $slot): ?bool
    {
        $answer = $question->answers->firstWhere('statement_slot', $slot);

        if ($answer === null) {
            return null;
        }

        return (bool) $answer->selected_answer;
    }

    private function isAnswered(ExamAttemptQuestion $question): bool
    {
        if ($question->isIllustratedGroup()) {
            $expected = count($question->content_snapshot['statements'] ?? []);

            return $expected > 0 && $question->answers->count() >= $expected;
        }

        return $question->answers->contains(fn ($answer): bool => (int) $answer->statement_slot === 0);
    }

    private function normalizeLocale(string $locale): string
    {
        return $this->locales->normalize($locale)
            ?? (SupportedLocale::isSupported($this->locales->defaultLocale())
                ? $this->locales->defaultLocale()
                : 'ja');
    }
}
