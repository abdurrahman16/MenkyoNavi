<?php

namespace Tests\Feature\Translations;

use App\Enums\ExamAttemptType;
use App\Enums\StagedTranslationStatus;
use App\Models\Question;
use App\Models\QuestionImportEntry;
use App\Models\QuestionImportEntryTranslation;
use App\Services\Exams\ExamAttemptService;
use App\Services\Translations\StagedTranslationImportService;
use App\Services\Translations\TranslationQualityChecker;
use App\Support\SupportedLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Exams\CreatesPracticeExams;
use Tests\TestCase;

class KarimenSevenLocaleQ28to50Test extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    /**
     * @return array{format_version: int, rows: list<array<string, mixed>>}
     */
    private function sevenLocalePayload(QuestionImportEntry $entry, bool $needsReview = false): array
    {
        $status = $needsReview
            ? StagedTranslationStatus::NeedsReview->value
            : StagedTranslationStatus::Draft->value;

        $rows = [[
            'source_entry_id' => $entry->id,
            'source_stable_id' => $entry->stableSourceId(),
            'source_entry_checksum' => $entry->source_entry_checksum,
            'english_question' => 'MUTATED SHOULD BE IGNORED',
            'english_explanation' => 'MUTATED EXPLANATION',
            'english_figure_caption' => $entry->figure_caption,
            'correct_answer' => ! $entry->correct_answer,
            'locale' => 'en',
            'question_text' => 'MUTATED ENGLISH TEXT',
            'explanation' => 'MUTATED ENGLISH EXPLANATION',
            'figure_caption' => $entry->figure_caption,
            'status' => StagedTranslationStatus::Draft->value,
            'nuance_note' => 'Exact original English source retained as immutable reference copy.',
        ]];

        foreach (['zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            $rows[] = [
                'source_entry_id' => $entry->id,
                'source_stable_id' => $entry->stableSourceId(),
                'source_entry_checksum' => $entry->source_entry_checksum,
                'english_question' => $entry->english_question,
                'english_explanation' => $entry->english_explanation,
                'english_figure_caption' => $entry->figure_caption,
                'correct_answer' => $entry->correct_answer,
                'locale' => $locale,
                'question_text' => match ($locale) {
                    'zh' => '驾驶员必须在打算变换车道前约 3 秒开始发出信号。',
                    'vi' => 'Người lái phải bắt đầu ra tín hiệu khoảng 3 giây trước khi định đổi làn.',
                    'fil' => 'Dapat magsimulang mag-signal ang driver nang humigit-kumulang 3 segundo bago magpalit ng lane.',
                    'ne' => 'लेन बदल्न खोज्दा करिब 3 सेकेन्ड अघिबाट संकेत सुरु गर्नुपर्छ।',
                    'bn' => 'লেন বদলের ইচ্ছা করার প্রায় 3 সেকেন্ড আগে সংকেত শুরু করতে হবে।',
                    'ko' => '차로를 변경하려는 약 3초 전부터 신호를 시작해야 한다.',
                },
                'explanation' => match ($locale) {
                    'zh' => '变换车道的信号按时间规定（提前 3 秒），而不是按地点。',
                    'vi' => 'Tín hiệu đổi làn được quy định theo thời gian (3 giây trước), không theo vị trí.',
                    'fil' => 'Ang lane-change signal ay ayon sa oras (3 segundo bago), hindi sa lokasyon.',
                    'ne' => 'लेन परिवर्तनको संकेत स्थान होइन समय (3 सेकेन्ड अघि) ले तोकिएको हो।',
                    'bn' => 'লেন-বদল সংকেত স্থান নয়, সময় (3 সেকেন্ড আগে) দিয়ে নির্ধারিত।',
                    'ko' => '차로 변경 신호는 장소가 아니라 시간(약 3초 전)으로 정해져 있다.',
                },
                'figure_caption' => null,
                'status' => $status,
                'nuance_note' => 'Q28-50 seven-locale draft test row.',
            ];
        }

        return [
            'format_version' => 1,
            'rows' => $rows,
        ];
    }

    #[Test]
    public function test_source_selection_uses_group_and_question_numbers_for_q28_to_50(): void
    {
        $wrongGroup = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_2',
            'original_question_number' => 44,
            'english_question' => 'Wrong group.',
            'english_explanation' => 'Skip.',
            'correct_answer' => true,
        ]);

        $target = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 44,
            'english_question' => 'A driver must start signaling about 3 seconds before intending to change lanes.',
            'english_explanation' => 'The lane-change signal is designated by timing (3 seconds before), not by location.',
            'correct_answer' => true,
        ]);

        $excluded = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 4,
            'english_question' => 'Clarification queue.',
            'english_explanation' => 'Pending.',
            'correct_answer' => true,
        ]);

        $selected = QuestionImportEntry::query()
            ->where('source_group', 'karimen_test_1')
            ->whereIn('original_question_number', range(28, 50))
            ->get();

        $this->assertTrue($selected->contains('id', $target->id));
        $this->assertFalse($selected->contains('id', $wrongGroup->id));
        $this->assertFalse($selected->contains('id', $excluded->id));
        $this->assertSame(44, $selected->firstWhere('id', $target->id)?->original_question_number);
    }

    #[Test]
    public function test_seven_locale_import_preserves_english_answers_and_existing_japanese(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 44,
            'english_question' => 'A driver must start signaling about 3 seconds before intending to change lanes.',
            'english_explanation' => 'The lane-change signal is designated by timing (3 seconds before), not by location.',
            'correct_answer' => true,
            'is_published' => false,
        ]);

        $jaText = '進路を変更しようとするときは、その約3秒前から合図を始めなければならない。';
        $jaExplanation = '進路変更の合図は、場所ではなく時間（約3秒前）で定められている。';

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => $jaText,
            'explanation' => $jaExplanation,
            'status' => StagedTranslationStatus::Draft,
        ]);

        $priorComplete = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 20,
            'english_question' => 'Overtaking is prohibited at a railway crossing or within 30 meters before one.',
            'english_explanation' => 'Prohibited so as to prevent accidents at railway crossings.',
            'correct_answer' => true,
        ]);

        $priorZh = '在铁路道口或其前方30米以内禁止超车。';
        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $priorComplete->id,
            'locale' => 'zh',
            'question_text' => $priorZh,
            'explanation' => '为防止铁路道口事故而禁止。',
            'status' => StagedTranslationStatus::Draft,
        ]);

        $englishQuestion = $entry->english_question;
        $englishExplanation = $entry->english_explanation;
        $answer = $entry->correct_answer;
        $checksum = $entry->source_entry_checksum;

        $summary = app(StagedTranslationImportService::class)->importPayload(
            $this->sevenLocalePayload($entry),
            dryRun: false,
        );

        $this->assertSame(7, $summary['created_count']);
        $this->assertSame(0, $summary['validation_error_count']);
        $this->assertGreaterThan(0, $summary['english_mutations_blocked']);

        $entry->refresh();
        $this->assertSame($englishQuestion, $entry->english_question);
        $this->assertSame($englishExplanation, $entry->english_explanation);
        $this->assertSame($answer, $entry->correct_answer);
        $this->assertSame($checksum, $entry->source_entry_checksum);
        $this->assertFalse($entry->is_published);
        $this->assertNull($entry->promoted_question_id);

        foreach (SupportedLocale::codes() as $locale) {
            $translation = $entry->translationFor($locale);
            $this->assertNotNull($translation, "Missing {$locale}");
            $this->assertTrue($translation->hasCompleteText(), "Incomplete {$locale}");
            $this->assertContains($translation->status, [
                StagedTranslationStatus::Draft,
                StagedTranslationStatus::NeedsReview,
            ]);
            $this->assertNotSame(StagedTranslationStatus::ReadyForPublication, $translation->status);
            $this->assertNotSame(StagedTranslationStatus::Reviewed, $translation->status);
        }

        $this->assertSame($jaText, $entry->translationFor('ja')?->question_text);
        $this->assertSame($jaExplanation, $entry->translationFor('ja')?->explanation);
        $this->assertSame($englishQuestion, $entry->translationFor('en')?->question_text);
        $this->assertSame($englishExplanation, $entry->translationFor('en')?->explanation);
        $this->assertSame($priorZh, $priorComplete->fresh()->translationFor('zh')?->question_text);
        $this->assertSame(8, QuestionImportEntryTranslation::query()->where('question_import_entry_id', $entry->id)->count());
        $this->assertSame(0, Question::query()->count());
    }

    #[Test]
    public function test_reimport_is_idempotent_and_does_not_duplicate_records(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 44,
            'english_question' => 'A driver must start signaling about 3 seconds before intending to change lanes.',
            'english_explanation' => 'The lane-change signal is designated by timing (3 seconds before), not by location.',
            'correct_answer' => true,
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => '進路を変更しようとするときは、その約3秒前から合図を始めなければならない。',
            'explanation' => '進路変更の合図は、場所ではなく時間（約3秒前）で定められている。',
            'status' => StagedTranslationStatus::NeedsReview,
        ]);

        $payload = $this->sevenLocalePayload($entry, needsReview: true);
        $service = app(StagedTranslationImportService::class);

        $first = $service->importPayload($payload, dryRun: false);
        $second = $service->importPayload($payload, dryRun: false);

        $this->assertSame(7, $first['created_count']);
        $this->assertSame(0, $first['updated_count']);
        $this->assertSame(0, $second['created_count']);
        $this->assertSame(7, $second['updated_count']);
        $this->assertSame(8, QuestionImportEntryTranslation::query()->where('question_import_entry_id', $entry->id)->count());
        $this->assertSame(StagedTranslationStatus::NeedsReview, $entry->fresh()->translationFor('ja')?->status);
        $this->assertSame(StagedTranslationStatus::NeedsReview, $entry->fresh()->translationFor('zh')?->status);
    }

    #[Test]
    public function test_unicode_negation_and_number_checks_for_q28_50_locales(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Using a mobile phone while driving does not affect a driver\'s attention.',
            'english_explanation' => 'It is dangerous because it may distract the driver\'s attention.',
            'figure_caption' => null,
        ]);

        $checker = new TranslationQualityChecker;

        $zh = $checker->check($entry, [
            'locale' => 'zh',
            'question_text' => '驾驶时使用手机对驾驶人的注意力没有任何影响。',
            'explanation' => '这很危险，因为可能分散驾驶人的注意力。',
        ]);
        $this->assertNotContains('missing_negation', $zh);
        $this->assertNotContains('invalid_unicode_question_text', $zh);

        $fil = $checker->check($entry, [
            'locale' => 'fil',
            'question_text' => 'Ang paggamit ng mobile phone habang nagmamaneho ay hindi nakaaapekto sa atensyon ng driver.',
            'explanation' => 'Mapanganib ito dahil maaaring makagambala sa atensyon ng driver.',
        ]);
        $this->assertNotContains('missing_negation', $fil);

        $timed = QuestionImportEntry::factory()->create([
            'english_question' => 'A driver must start signaling about 3 seconds before intending to change lanes.',
            'english_explanation' => 'Timing uses 3 seconds.',
        ]);

        $filSeconds = $checker->check($timed, [
            'locale' => 'fil',
            'question_text' => 'Dapat magsimulang mag-signal ang driver nang humigit-kumulang 3 segundo bago magpalit ng lane.',
            'explanation' => 'Ang signal ay ayon sa oras (3 segundo bago).',
        ]);
        $this->assertNotContains('missing_measurement_units', $filSeconds);
        $this->assertNotContains('potentially_altered_numbers', $filSeconds);
    }

    #[Test]
    public function test_batch_data_file_covers_all_q28_50_locales_without_japanese_rows(): void
    {
        $path = database_path('data/karimen_test_1_q28_50_seven_locales.php');

        if (! is_file($path)) {
            $this->markTestSkipped('Private imported translation batch asset is not present in this checkout.');
        }

        /** @var array<string, mixed> $batch */
        $batch = require $path;

        $this->assertSame(range(28, 50), $batch['selected_question_numbers']);
        $this->assertSame([4, 23], $batch['excluded_review_queue_numbers']);
        $this->assertSame([28, 31, 33, 35, 38, 39, 42, 45, 47], $batch['needs_review_question_numbers']);

        foreach (range(28, 50) as $number) {
            $this->assertArrayHasKey($number, $batch['translations']);
            foreach (['zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
                $this->assertArrayHasKey($locale, $batch['translations'][$number]);
                $this->assertNotSame('', $batch['translations'][$number][$locale]['question_text']);
                $this->assertNotSame('', $batch['translations'][$number][$locale]['explanation']);
                $this->assertContains(
                    $batch['translations'][$number][$locale]['status'] ?? 'draft',
                    ['draft', 'needs_review'],
                );
            }
            $this->assertArrayNotHasKey('ja', $batch['translations'][$number]);
            $this->assertArrayNotHasKey('en', $batch['translations'][$number]);
        }
    }

    #[Test]
    public function test_seven_locale_drafts_do_not_alter_exam_scoring_or_publish(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 30,
            'english_question' => 'A driver must wear a seatbelt and make sure passengers wear one too.',
            'english_explanation' => 'Ensuring passenger safety is the driver\'s responsibility.',
            'correct_answer' => true,
            'is_published' => false,
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => 'シートベルトは傷害のリスクを大幅に減らすため、運転者は着用し、同乗者にも着用させなければならない。',
            'explanation' => '同乗者の安全を確保することは運転者の責任である。',
            'status' => StagedTranslationStatus::Draft,
        ]);

        app(StagedTranslationImportService::class)->importPayload(
            $this->sevenLocalePayload($entry),
            dryRun: false,
        );

        $this->assertFalse($entry->fresh()->is_published);
        $this->assertSame(0, Question::query()->count());

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
    }

    #[Test]
    public function test_japanese_first_public_site_still_renders(): void
    {
        $this->get('/traffic-rules')
            ->assertSuccessful()
            ->assertSee(__('pages.traffic_rules.title', [], 'ja'), false);
    }
}
