<?php

namespace Tests\Feature\Translations;

use App\Enums\ExamAttemptType;
use App\Enums\StagedTranslationStatus;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Services\Exams\ExamAttemptService;
use App\Services\Translations\StagedTranslationImportService;
use App\Support\SafeRubyMarkup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Exams\CreatesPracticeExams;
use Tests\TestCase;

class KarimenJapaneseBatch25Test extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    /**
     * @return array{format_version: int, rows: list<array<string, mixed>>}
     */
    private function japaneseImportPayload(QuestionImportEntry $entry, array $overrides = []): array
    {
        return [
            'format_version' => 1,
            'rows' => [[
                'source_entry_id' => $entry->id,
                'source_stable_id' => $entry->stableSourceId(),
                'source_entry_checksum' => $entry->source_entry_checksum,
                'english_question' => $entry->english_question,
                'english_explanation' => $entry->english_explanation,
                'english_figure_caption' => $entry->figure_caption,
                'correct_answer' => $entry->correct_answer,
                'locale' => 'ja',
                'question_text' => '踏切とその手前30メートル以内では、追い越しをしてはならない。',
                'explanation' => '踏切での事故を防ぐために禁止されている。',
                'figure_caption' => null,
                'nuance_note' => 'Draft JA batch test.',
                'status' => StagedTranslationStatus::Draft->value,
                'japanese_reading_data' => [
                    ['text' => '踏切', 'reading' => 'ふみきり'],
                    ['text' => 'とその手前30メートル以内では、追い越しをしてはならない。'],
                ],
                ...$overrides,
            ]],
        ];
    }

    #[Test]
    public function test_japanese_batch_import_persists_drafts_preserves_english_and_answers(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 20,
            'english_question' => 'Overtaking is prohibited at a railway crossing or within 30 meters before one.',
            'english_explanation' => 'Prohibited so as to prevent accidents at railway crossings.',
            'correct_answer' => true,
            'is_published' => false,
        ]);

        $englishQuestion = $entry->english_question;
        $englishExplanation = $entry->english_explanation;
        $answer = $entry->correct_answer;
        $checksum = $entry->source_entry_checksum;

        $summary = app(StagedTranslationImportService::class)->importPayload(
            $this->japaneseImportPayload($entry),
            dryRun: false,
        );

        $this->assertSame(1, $summary['created_count']);
        $this->assertSame(0, $summary['validation_error_count']);

        $entry->refresh();
        $this->assertSame($englishQuestion, $entry->english_question);
        $this->assertSame($englishExplanation, $entry->english_explanation);
        $this->assertSame($answer, $entry->correct_answer);
        $this->assertSame($checksum, $entry->source_entry_checksum);
        $this->assertFalse($entry->is_published);

        $translation = QuestionImportEntryTranslation::query()
            ->where('question_import_entry_id', $entry->id)
            ->where('locale', 'ja')
            ->firstOrFail();

        $this->assertSame('踏切とその手前30メートル以内では、追い越しをしてはならない。', $translation->question_text);
        $this->assertSame('踏切での事故を防ぐために禁止されている。', $translation->explanation);
        $this->assertSame(StagedTranslationStatus::Draft, $translation->status);
        $this->assertNull($translation->reviewed_at);
        $this->assertNotSame(StagedTranslationStatus::ReadyForPublication, $translation->status);
        $this->assertNotEmpty($translation->japanese_reading_data);

        $ruby = SafeRubyMarkup::fromReadingData($translation->japanese_reading_data);
        $this->assertStringContainsString('<ruby>踏切<rt>ふみきり</rt></ruby>', $ruby);
        $this->assertStringNotContainsString('<script>', $ruby);
    }

    #[Test]
    public function test_import_reuses_unreviewed_pilot_and_does_not_overwrite_reviewed_translation(): void
    {
        $pilotEntry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 1,
            'english_question' => 'Even when proceeding on a one-way street, vehicles may not drive on the right-hand side of the road.',
            'english_explanation' => 'Vehicles MAY proceed on the right-hand side if the road is one-way.',
            'correct_answer' => false,
        ]);

        $pilotText = '一方通行の道路を通行する場合でも、車両は道路の右側を通行してはならない。';
        $pilotExplanation = '一方通行の道路では、車両は右側を通行することができる。';

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $pilotEntry->id,
            'locale' => 'ja',
            'question_text' => $pilotText,
            'explanation' => $pilotExplanation,
            'status' => StagedTranslationStatus::Draft,
        ]);

        $reuse = app(StagedTranslationImportService::class)->importPayload(
            $this->japaneseImportPayload($pilotEntry, [
                'question_text' => $pilotText,
                'explanation' => $pilotExplanation,
                'status' => StagedTranslationStatus::Draft->value,
                'japanese_reading_data' => null,
            ]),
            dryRun: false,
        );

        $this->assertSame(1, $reuse['updated_count']);
        $this->assertSame(
            $pilotText,
            $pilotEntry->fresh()->translationFor('ja')?->question_text,
        );

        $reviewedEntry = QuestionImportEntry::factory()->create([
            'english_question' => 'Drivers must not park here.',
            'english_explanation' => 'Parking is prohibited.',
            'correct_answer' => true,
        ]);

        QuestionImportEntryTranslation::factory()->reviewed()->create([
            'question_import_entry_id' => $reviewedEntry->id,
            'locale' => 'ja',
            'question_text' => 'ここに駐車してはならない。',
            'explanation' => '駐車は禁止されている。',
        ]);

        $blocked = app(StagedTranslationImportService::class)->importPayload(
            $this->japaneseImportPayload($reviewedEntry, [
                'question_text' => '変更された訳文',
                'explanation' => '変更された解説',
                'status' => StagedTranslationStatus::Draft->value,
            ]),
            dryRun: false,
        );

        $this->assertSame(1, $blocked['blocked_reviewed_count']);
        $this->assertSame(
            'ここに駐車してはならない。',
            $reviewedEntry->fresh()->translationFor('ja')?->question_text,
        );
    }

    #[Test]
    public function test_needs_review_status_is_preserved_and_import_is_idempotent(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'A traffic sign as shown designates either a pedestrian crossing or a bicycle crossing.',
            'english_explanation' => 'The sign designates that there are BOTH pedestrian and bicycle crossings.',
            'figure_caption' => 'Pedestrian + bicycle crossing sign',
            'correct_answer' => false,
        ]);

        $payload = $this->japaneseImportPayload($entry, [
            'question_text' => '図示の交通標識は、横断歩道または自転車横断帯のいずれかを示している。',
            'explanation' => 'この標識は、横断歩道と自転車横断帯の両方があることを示している。',
            'figure_caption' => '横断歩道・自転車横断帯の標識',
            'status' => StagedTranslationStatus::NeedsReview->value,
            'nuance_note' => '図依存のため要レビュー。',
        ]);

        $service = app(StagedTranslationImportService::class);
        $first = $service->importPayload($payload, dryRun: false);
        $second = $service->importPayload($payload, dryRun: false);

        $this->assertSame(1, $first['created_count']);
        $this->assertSame(1, $second['updated_count']);
        $this->assertSame(1, QuestionImportEntryTranslation::query()->where('locale', 'ja')->count());

        $translation = $entry->fresh()->translationFor('ja');
        $this->assertNotNull($translation);
        $this->assertSame(StagedTranslationStatus::NeedsReview, $translation->status);
        $this->assertNull($translation->reviewed_at);
        $this->assertTrue($translation->hasCompleteText());
    }

    #[Test]
    public function test_safe_ruby_markup_strips_unsafe_html_from_stored_japanese_text(): void
    {
        $entry = QuestionImportEntry::factory()->create();

        $translation = QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => '<script>alert(1)</script>一時停止しなければならない。',
            'explanation' => '<b>完全に停止</b>する。',
            'japanese_reading_data' => [
                ['text' => '<img src=x onerror=alert(1)>一時停止', 'reading' => 'いちじていし'],
            ],
        ]);

        $stored = $translation->fresh();
        $this->assertStringNotContainsString('<script>', (string) $stored->question_text);
        $this->assertStringNotContainsString('<b>', (string) $stored->explanation);
        $this->assertStringContainsString('一時停止しなければならない。', (string) $stored->question_text);
        $this->assertSame('完全に停止する。', $stored->explanation);

        $ruby = SafeRubyMarkup::fromReadingData($stored->japanese_reading_data);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $ruby);
        $this->assertStringNotContainsString('<img', $ruby);
        $this->assertStringContainsString('<ruby>', $ruby);
    }

    #[Test]
    public function test_draft_japanese_import_does_not_publish_or_alter_exam_scoring(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Seatbelts substantially reduce injury risk.',
            'english_explanation' => 'Drivers must ensure passengers wear seatbelts.',
            'correct_answer' => true,
            'is_published' => false,
        ]);

        app(StagedTranslationImportService::class)->importPayload(
            $this->japaneseImportPayload($entry, [
                'question_text' => 'シートベルトは傷害のリスクを大幅に減らす。',
                'explanation' => '運転者は同乗者にも着用させなければならない。',
                'status' => StagedTranslationStatus::Draft->value,
                'japanese_reading_data' => null,
            ]),
            dryRun: false,
        );

        $this->assertFalse($entry->fresh()->is_published);
        $this->assertNull($entry->fresh()->promoted_question_id);
        $this->assertSame(0, Question::query()->count());

        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $question = $this->publishedTrueFalse($examType, true, [
            'japanese_text' => '一時停止の標識では止まらなければならない。',
        ]);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $service->recordAnswer($attempt, $attempt->questions->first()->id, 0, true);
        $final = $service->submit($attempt->fresh());

        $this->assertSame(1, $final->score);
        $this->assertTrue($final->passed);
        $this->assertSame($question->id, $attempt->questions->first()->question_id);
    }

    #[Test]
    public function test_japanese_first_localization_still_renders_single_language_exam_ui(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, false, [
            'japanese_text' => '路側帯を自動車が通行してはならない。',
        ]);
        $attempt = app(ExamAttemptService::class)->start($user, $examType, ExamAttemptType::Full);

        $this->actingAs($user)
            ->withSession(['locale' => 'ja'])
            ->get(route('exams.attempts.show', $attempt))
            ->assertSuccessful()
            ->assertSee('路側帯を自動車が通行してはならない。', false)
            ->assertDontSee('Overtaking is prohibited', false);
    }
}
