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

class KarimenEightLocaleBatch25Test extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    /**
     * @return array{format_version: int, rows: list<array<string, mixed>>}
     */
    private function multiLocalePayload(QuestionImportEntry $entry): array
    {
        $rows = [];

        foreach (['en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            $rows[] = [
                'source_entry_id' => $entry->id,
                'source_stable_id' => $entry->stableSourceId(),
                'source_entry_checksum' => $entry->source_entry_checksum,
                'english_question' => $locale === 'en' ? 'MUTATED ENGLISH SOURCE' : $entry->english_question,
                'english_explanation' => $locale === 'en' ? 'MUTATED ENGLISH EXPLANATION' : $entry->english_explanation,
                'english_figure_caption' => $entry->figure_caption,
                'correct_answer' => $locale === 'en' ? false : $entry->correct_answer,
                'locale' => $locale,
                'question_text' => match ($locale) {
                    'en' => 'MUTATED SHOULD BE IGNORED',
                    'zh' => '在铁路道口或其前方30米以内禁止超车。',
                    'vi' => 'Cấm vượt tại đường ngang hoặc trong phạm vi 30 mét phía trước đường ngang.',
                    'fil' => 'Bawal mag-overtake sa railway crossing o sa loob ng 30 meters bago ito.',
                    'ne' => 'रेलवे क्रसिङमा वा त्यसको अगाडि 30 मिटरभित्र ओभरटेक गर्न निषेध छ।',
                    'bn' => 'রেলক্রসিংয়ে বা তার আগে 30 মিটারের মধ্যে ওভারটেক নিষিদ্ধ।',
                    'ko' => '건널목과 그 직전 30미터 이내에서는 앞지르기를 해서는 안 된다.',
                },
                'explanation' => match ($locale) {
                    'en' => 'MUTATED EXPLANATION',
                    'zh' => '为防止铁路道口事故而禁止。',
                    'vi' => 'Bị cấm để ngăn tai nạn tại đường ngang.',
                    'fil' => 'Ipinagbabawal upang maiwasan ang aksidente sa railway crossing.',
                    'ne' => 'रेलवे क्रसिङमा दुर्घटना रोक्न निषेध गरिएको हो।',
                    'bn' => 'রেলক্রসিংয়ে দুর্ঘটনা রোধ করতে এটি নিষিদ্ধ।',
                    'ko' => '건널목 사고를 막기 위해 금지되어 있다.',
                },
                'figure_caption' => null,
                'status' => StagedTranslationStatus::Draft->value,
            ];
        }

        return [
            'format_version' => 1,
            'rows' => $rows,
        ];
    }

    #[Test]
    public function test_eight_locale_import_covers_all_locales_and_preserves_english_and_answers(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'source_group' => 'karimen_test_1',
            'original_question_number' => 20,
            'english_question' => 'Overtaking is prohibited at a railway crossing or within 30 meters before one.',
            'english_explanation' => 'Prohibited so as to prevent accidents at railway crossings.',
            'correct_answer' => true,
            'is_published' => false,
        ]);

        $jaText = '踏切とその手前30メートル以内では、追い越しをしてはならない。';
        $jaExplanation = '踏切での事故を防ぐために禁止されている。';

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => $jaText,
            'explanation' => $jaExplanation,
            'status' => StagedTranslationStatus::Draft,
        ]);

        $englishQuestion = $entry->english_question;
        $englishExplanation = $entry->english_explanation;
        $answer = $entry->correct_answer;
        $checksum = $entry->source_entry_checksum;

        $summary = app(StagedTranslationImportService::class)->importPayload(
            $this->multiLocalePayload($entry),
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
        }

        $this->assertSame($jaText, $entry->translationFor('ja')?->question_text);
        $this->assertSame($jaExplanation, $entry->translationFor('ja')?->explanation);
        $this->assertSame($englishQuestion, $entry->translationFor('en')?->question_text);
        $this->assertSame($englishExplanation, $entry->translationFor('en')?->explanation);
        $this->assertSame(8, QuestionImportEntryTranslation::query()->where('question_import_entry_id', $entry->id)->count());
        $this->assertSame(0, Question::query()->count());
    }

    #[Test]
    public function test_existing_pilot_translations_are_preserved_and_import_is_idempotent(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Drivers must not park here.',
            'english_explanation' => 'Parking is prohibited.',
            'correct_answer' => true,
        ]);

        $pilotZh = '此处不得停车。';
        $pilotZhExplanation = '停车被禁止。';

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'zh',
            'question_text' => $pilotZh,
            'explanation' => $pilotZhExplanation,
            'status' => StagedTranslationStatus::Draft,
        ]);

        QuestionImportEntryTranslation::factory()->create([
            'question_import_entry_id' => $entry->id,
            'locale' => 'ja',
            'question_text' => 'ここに駐車してはならない。',
            'explanation' => '駐車は禁止されている。',
            'status' => StagedTranslationStatus::NeedsReview,
        ]);

        $payload = [
            'format_version' => 1,
            'rows' => [[
                'source_entry_id' => $entry->id,
                'source_stable_id' => $entry->stableSourceId(),
                'source_entry_checksum' => $entry->source_entry_checksum,
                'locale' => 'vi',
                'question_text' => 'Không được đỗ xe ở đây.',
                'explanation' => 'Việc đỗ xe bị cấm.',
                'status' => StagedTranslationStatus::Draft->value,
            ]],
        ];

        $service = app(StagedTranslationImportService::class);
        $first = $service->importPayload($payload, dryRun: false);
        $second = $service->importPayload($payload, dryRun: false);

        $this->assertSame(1, $first['created_count']);
        $this->assertSame(1, $second['updated_count']);
        $this->assertSame($pilotZh, $entry->fresh()->translationFor('zh')?->question_text);
        $this->assertSame(StagedTranslationStatus::NeedsReview, $entry->fresh()->translationFor('ja')?->status);
        $this->assertSame(1, QuestionImportEntryTranslation::query()->where('locale', 'vi')->count());
    }

    #[Test]
    public function test_unicode_negation_and_caption_checks_for_eight_locales(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Vehicles must not overtake within 30 meters before a pedestrian crossing.',
            'english_explanation' => 'Overtaking is not allowed there.',
            'figure_caption' => 'Pedestrian crossing diagram',
        ]);

        $checker = new TranslationQualityChecker;

        $ok = $checker->check($entry, [
            'locale' => 'bn',
            'question_text' => 'পথচারী ক্রসিংয়ের আগে 30 মিটারের মধ্যে যানবাহন ওভারটেক করতে পারবে না।',
            'explanation' => 'সেখানে ওভারটেক নিষিদ্ধ।',
            'figure_caption' => 'পথচারী ক্রসিং চিত্র',
        ]);

        $this->assertNotContains('missing_negation', $ok);
        $this->assertNotContains('missing_negation_explanation', $ok);
        $this->assertNotContains('potentially_altered_numbers', $ok);
        $this->assertNotContains('missing_figure_caption', $ok);
        $this->assertNotContains('invalid_unicode_question_text', $ok);

        $missingCaption = $checker->check($entry, [
            'locale' => 'ko',
            'question_text' => '횡단보도 직전 30미터 이내에서는 앞지르기를 해서는 안 된다.',
            'explanation' => '그곳에서 앞지르기는 허용되지 않는다.',
            'figure_caption' => null,
        ]);

        $this->assertContains('missing_figure_caption', $missingCaption);

        $missingExplanation = $checker->check($entry, [
            'locale' => 'vi',
            'question_text' => 'Cấm vượt trong phạm vi 30 mét trước vạch qua đường.',
            'explanation' => '',
            'figure_caption' => 'Hình',
        ]);

        $this->assertContains('missing_explanation', $missingExplanation);
    }

    #[Test]
    public function test_eight_locale_drafts_do_not_alter_exam_runner_scoring(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Seatbelts substantially reduce injury risk.',
            'english_explanation' => 'Drivers must ensure passengers wear seatbelts.',
            'correct_answer' => true,
            'is_published' => false,
        ]);

        app(StagedTranslationImportService::class)->importPayload(
            $this->multiLocalePayload($entry),
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
}
