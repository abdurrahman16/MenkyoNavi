<?php

namespace Tests\Feature\Translations;

use App\Enums\ExamAttemptType;
use App\Enums\StagedTranslationStatus;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Services\Exams\ExamAttemptService;
use App\Services\Translations\StagedTranslationImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Exams\CreatesPracticeExams;
use Tests\TestCase;

class KarimenJapaneseQ28to50Test extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    /**
     * @return array{format_version: int, rows: list<array<string, mixed>>}
     */
    private function japaneseRowPayload(QuestionImportEntry $entry, array $overrides = []): array
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
                'question_text' => '交差点またはその付近以外の場所で緊急自動車が接近してきたときは、車両は道路の左側に寄って一時停止しなければならない。',
                'explanation' => '一時停止する必要はない。左側に寄って進路を譲ればよい。',
                'figure_caption' => null,
                'status' => StagedTranslationStatus::Draft->value,
                'nuance_note' => 'Q28-50 style draft.',
                ...$overrides,
            ]],
        ];
    }

    #[Test]
    public function test_source_selection_uses_group_and_question_number_not_assumed_ids(): void
    {
        $wrongIdShape = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_2',
            'original_question_number' => 29,
            'english_question' => 'Wrong group question.',
            'english_explanation' => 'Should not be selected.',
            'correct_answer' => true,
        ]);

        $target = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 29,
            'english_question' => 'If an emergency vehicle is approaching a location that is not at or near an intersection, vehicles must move to the left side of the road and come to a STOP.',
            'english_explanation' => 'There is no need to stop — just move left and give way.',
            'correct_answer' => false,
            'review_flags' => [],
        ]);

        $selected = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->whereIn('original_question_number', range(28, 50))
            ->get()
            ->filter(fn (QuestionImportEntry $entry): bool => ($entry->review_flags ?? []) === []);

        $this->assertTrue($selected->contains('id', $target->id));
        $this->assertFalse($selected->contains('id', $wrongIdShape->id));
        $this->assertSame(29, $selected->firstWhere('id', $target->id)?->original_question_number);
        $this->assertNotSame($wrongIdShape->id, $target->id);
    }

    #[Test]
    public function test_japanese_q28_50_import_persists_drafts_and_preserves_english_and_answers(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 29,
            'english_question' => 'If an emergency vehicle is approaching a location that is not at or near an intersection, vehicles must move to the left side of the road and come to a STOP.',
            'english_explanation' => 'There is no need to stop — just move left and give way. (Stopping is for intersections.)',
            'correct_answer' => false,
            'is_published' => false,
        ]);

        $existing = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 20,
            'english_question' => 'Overtaking is prohibited at a railway crossing or within 30 meters before one.',
            'english_explanation' => 'Prohibited so as to prevent accidents at railway crossings.',
            'correct_answer' => true,
        ]);

        $existingJa = '踏切とその手前30メートル以内では、追い越しをしてはならない。';
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $existing->id,
            'locale' => 'ja',
            'question_text' => $existingJa,
            'explanation' => '踏切での事故を防ぐために禁止されている。',
            'status' => StagedTranslationStatus::NeedsReview,
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $existing->id,
            'locale' => 'zh',
            'question_text' => '在铁路道口或其前方30米以内禁止超车。',
            'explanation' => '为防止铁路道口事故而禁止。',
            'status' => StagedTranslationStatus::Draft,
        ]);

        $englishQuestion = $entry->english_question;
        $answer = $entry->correct_answer;
        $checksum = $entry->source_entry_checksum;

        $summary = app(StagedTranslationImportService::class)->importPayload(
            $this->japaneseRowPayload($entry),
            dryRun: false,
        );

        $this->assertSame(1, $summary['created_count']);
        $this->assertSame(0, $summary['validation_error_count']);

        $entry->refresh();
        $this->assertSame($englishQuestion, $entry->english_question);
        $this->assertSame($answer, $entry->correct_answer);
        $this->assertSame($checksum, $entry->source_entry_checksum);
        $this->assertFalse($entry->is_published);
        $this->assertNull($entry->promoted_question_id);

        $translation = $entry->translationFor('ja');
        $this->assertNotNull($translation);
        $this->assertSame(StagedTranslationStatus::Draft, $translation->status);
        $this->assertTrue($translation->hasCompleteText());
        $this->assertStringContainsString('緊急自動車', (string) $translation->question_text);

        $this->assertSame($existingJa, $existing->fresh()->translationFor('ja')?->question_text);
        $this->assertSame(StagedTranslationStatus::NeedsReview, $existing->fresh()->translationFor('ja')?->status);
        $this->assertSame(
            '在铁路道口或其前方30米以内禁止超车。',
            $existing->fresh()->translationFor('zh')?->question_text,
        );
        $this->assertSame(0, Question::query()->count());
    }

    #[Test]
    public function test_import_is_idempotent_preserves_review_flags_and_unicode(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 41,
            'english_question' => 'At a railway crossing without traffic signals, drivers must bring the vehicle to a complete stop, open a window, and perform a visual and aural check.',
            'english_explanation' => 'The three principles at a railway crossing: stop, look, and listen.',
            'correct_answer' => true,
            'review_flags' => [],
        ]);

        $payload = $this->japaneseRowPayload($entry, [
            'question_text' => '信号機のない踏切では、車両を完全に停止させ、窓を開けて、目と耳による確認をしなければならない。',
            'explanation' => '踏切における三原則は、一時停止、目視、聴音である。',
            'status' => StagedTranslationStatus::NeedsReview->value,
            'japanese_reading_data' => [
                ['text' => '踏切', 'reading' => 'ふみきり'],
                ['text' => 'では完全に停止する。'],
            ],
        ]);

        $service = app(StagedTranslationImportService::class);
        $first = $service->importPayload($payload, dryRun: false);
        $second = $service->importPayload($payload, dryRun: false);

        $this->assertSame(1, $first['created_count']);
        $this->assertSame(1, $second['updated_count']);
        $this->assertSame(1, QuestionImportEntryTranslation::query()->where('locale', 'ja')->count());

        $entry->refresh();
        $this->assertSame([], $entry->review_flags ?? []);
        $translation = $entry->translationFor('ja');
        $this->assertSame(StagedTranslationStatus::NeedsReview, $translation?->status);
        $this->assertSame('ふみきり', $translation?->japanese_reading_data[0]['reading'] ?? null);
        $this->assertNull($translation?->reviewed_at);
    }

    #[Test]
    public function test_flagged_review_queue_entries_are_not_auto_translated_or_published(): void
    {
        $flagged = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 4,
            'english_question' => 'It is important for drivers not to be selfish, but to have a give-and-take spirit.',
            'english_explanation' => 'This is an attitude required for a driver.',
            'correct_answer' => true,
            'review_flags' => ['ambiguous_terminology'],
            'is_published' => false,
        ]);

        $eligible = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->whereIn('original_question_number', range(28, 50))
            ->get()
            ->filter(fn (QuestionImportEntry $entry): bool => ($entry->review_flags ?? []) === []);

        $this->assertFalse($eligible->contains('id', $flagged->id));
        $this->assertNull($flagged->fresh()->translationFor('ja'));
        $this->assertFalse($flagged->fresh()->is_published);
        $this->assertSame(['ambiguous_terminology'], $flagged->fresh()->review_flags);
    }

    #[Test]
    public function test_new_japanese_drafts_do_not_alter_exam_scoring(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 30,
            'english_question' => 'A driver must wear a seatbelt and make sure passengers wear one too.',
            'english_explanation' => 'Ensuring passenger safety is the driver\'s responsibility.',
            'correct_answer' => true,
        ]);

        app(StagedTranslationImportService::class)->importPayload(
            $this->japaneseRowPayload($entry, [
                'question_text' => 'シートベルトは傷害のリスクを大幅に減らすため、運転者は着用し、同乗者にも着用させなければならない。',
                'explanation' => '同乗者の安全を確保することは運転者の責任である。',
            ]),
            dryRun: false,
        );

        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true, [
            'japanese_text' => 'シートベルトを着用しなければならない。',
        ]);

        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $service->recordAnswer($attempt, $attempt->questions->first()->id, 0, true);
        $final = $service->submit($attempt->fresh());

        $this->assertSame(1, $final->score);
        $this->assertTrue($final->passed);
        $this->assertFalse($entry->fresh()->is_published);
    }
}
