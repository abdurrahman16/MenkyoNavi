<?php

namespace Tests\Feature;

use App\Enums\AccuracyEvidenceStatus;
use App\Enums\AnswerKeyReviewStatus;
use App\Enums\ContentReviewStatus;
use App\Enums\ContentRightsReviewStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Livewire\Admin\QuestionBankIndex;
use App\Livewire\Admin\QuestionBankShow;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionImportBatch;
use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Editorial\OriginalKarimenPilotStagingService;
use App\Services\Exams\QuestionSelectionService;
use Database\Seeders\ExamTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OriginalKarimenPilot001Test extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_pilot_catalog_has_stable_candidate_ids_and_valid_structure(): void
    {
        $catalog = app(OriginalKarimenPilotStagingService::class)->catalog();

        $this->assertSame(OriginalKarimenPilotStagingService::COLLECTION_KEY, $catalog['collection_key']);
        $this->assertCount(10, $catalog['candidates']);
        $this->assertNotEmpty($catalog['withheld']);

        $ids = [];
        foreach ($catalog['candidates'] as $candidate) {
            $id = (string) $candidate['candidate_id'];
            $this->assertMatchesRegularExpression('/^menkyo_original_karimen_pilot_001:\d+$/', $id);
            $this->assertNotContains($id, $ids);
            $ids[] = $id;

            $this->assertIsBool($candidate['correct_answer']);
            $this->assertNotSame('', trim((string) $candidate['question_ja']));
            $this->assertNotSame('', trim((string) $candidate['explanation_ja']));
            $this->assertNotSame('', trim((string) $candidate['source_url']));
            $this->assertStringContainsString('laws.e-gov.go.jp', (string) $candidate['source_url']);
            $this->assertNotSame('', trim((string) $candidate['source_section']));
            $this->assertNotSame('', trim((string) $candidate['legal_topic']));
            $this->assertNotSame('', trim((string) $candidate['verification_notes']));
        }

        $this->assertSame(
            [
                'menkyo_original_karimen_pilot_001:1',
                'menkyo_original_karimen_pilot_001:2',
                'menkyo_original_karimen_pilot_001:3',
                'menkyo_original_karimen_pilot_001:4',
                'menkyo_original_karimen_pilot_001:5',
                'menkyo_original_karimen_pilot_001:6',
                'menkyo_original_karimen_pilot_001:7',
                'menkyo_original_karimen_pilot_001:8',
                'menkyo_original_karimen_pilot_001:9',
                'menkyo_original_karimen_pilot_001:10',
            ],
            $ids,
        );
    }

    #[Test]
    public function test_pilot_ids_do_not_collide_with_imported_source_groups(): void
    {
        $this->assertNotSame('karimen_exam_trainer_en_610', OriginalKarimenPilotStagingService::COLLECTION_KEY);
        $this->assertNotSame('karimen_exam_trainer_en_414', OriginalKarimenPilotStagingService::COLLECTION_KEY);
        $this->assertNotSame('karimen_test_1', OriginalKarimenPilotStagingService::SOURCE_GROUP);

        QuestionImportBatch::factory()->create([
            'source_key' => 'karimen_exam_trainer_en_610',
            'entry_count' => 3,
        ]);
        QuestionImportEntry::factory()->count(3)->create([
            'question_import_batch_id' => QuestionImportBatch::query()->where('source_key', 'karimen_exam_trainer_en_610')->value('id'),
            'source_group' => 'karimen_test_1',
        ]);

        $beforeImported = QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->count();
        $this->assertSame(3, $beforeImported);

        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $this->assertSame(3, QuestionImportEntry::query()->where('source_group', 'karimen_test_1')->count());
        $this->assertSame(
            3,
            QuestionImportBatch::query()->where('source_key', 'karimen_exam_trainer_en_610')->value('entry_count'),
        );
        $this->assertSame(
            10,
            QuestionImportEntry::query()->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)->count(),
        );
    }

    #[Test]
    public function test_staging_is_idempotent_and_keeps_approval_gates_closed(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--dry-run' => true]);
        $this->assertSame(0, QuestionImportEntry::query()->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)->count());

        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $entries = QuestionImportEntry::query()
            ->with('translations')
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->orderBy('original_question_number')
            ->get();

        $this->assertCount(10, $entries);
        $this->assertSame(
            1,
            QuestionImportBatch::query()->where('source_key', OriginalKarimenPilotStagingService::COLLECTION_KEY)->count(),
        );

        foreach ($entries as $entry) {
            $this->assertFalse($entry->is_published);
            $this->assertSame(AnswerKeyReviewStatus::VerificationPending, $entry->answer_key_status);
            $this->assertSame(AccuracyEvidenceStatus::EvidenceCollected, $entry->accuracy_evidence_status);
            $this->assertSame(ContentRightsReviewStatus::Unclear, $entry->content_rights_status);
            $this->assertNull($entry->accuracy_reviewed_at);
            $this->assertNull($entry->content_rights_reviewed_at);
            $this->assertNull($entry->promoted_question_id);
            $this->assertFalse($entry->isPublicationReady());
            $this->assertNotEmpty($entry->accuracy_evidence_sources);
            $this->assertSame('ja', data_get($entry->source_metadata, 'authoritative_locale'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'human_accuracy_review'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'human_editorial_approval'));
            $this->assertFalse((bool) data_get($entry->source_metadata, 'public_exam_eligible'));

            $ja = $entry->translationFor('ja');
            $this->assertNotNull($ja);
            $this->assertNotSame('', trim((string) $ja->question_text));
            $this->assertNotSame('', trim((string) $ja->explanation));
        }
    }

    #[Test]
    public function test_pilot_questions_are_excluded_from_public_exam_pools(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $this->assertSame(0, Question::query()->count());

        $examType = ExamType::query()->where('slug', 'karimen')->first()
            ?? ExamType::factory()->create(['slug' => 'karimen', 'is_active' => true]);

        $publishedLeak = Question::factory()->create([
            'status' => QuestionStatus::Published,
            'content_review_status' => ContentReviewStatus::Reviewed,
            'is_demo' => false,
            'type' => QuestionType::StandardTrueFalse,
            'japanese_text' => 'LEAK SHOULD NOT BE PILOT',
        ]);
        $publishedLeak->examTypes()->attach($examType->id);

        $poolCount = app(QuestionSelectionService::class)
            ->fullAvailability($examType, 'ja')
            ->availableStandard;

        $this->assertGreaterThanOrEqual(1, $poolCount);

        $pilotTexts = collect(app(OriginalKarimenPilotStagingService::class)->catalog()['candidates'])
            ->pluck('question_ja')
            ->all();

        $poolQuestions = Question::query()
            ->where('status', QuestionStatus::Published)
            ->where('is_demo', false)
            ->where('content_review_status', ContentReviewStatus::Reviewed)
            ->pluck('japanese_text')
            ->all();

        foreach ($pilotTexts as $text) {
            $this->assertNotContains($text, $poolQuestions);
        }

        $this->assertSame(
            0,
            Question::query()
                ->whereIn('japanese_text', $pilotTexts)
                ->where('status', QuestionStatus::Published)
                ->count(),
        );
    }

    #[Test]
    public function test_admin_can_locate_pilot_collection_and_guest_cannot(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);
        $entry = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->where('original_question_number', 1)
            ->firstOrFail();

        $guest = User::factory()->create(['is_admin' => false]);
        $this->actingAs($guest)
            ->get(route('admin.questions.show', $entry))
            ->assertForbidden();

        $admin = User::factory()->administrator()->create();

        Livewire::actingAs($admin)
            ->test(QuestionBankIndex::class)
            ->set('sourceKey', OriginalKarimenPilotStagingService::COLLECTION_KEY)
            ->assertOk()
            ->assertSee(OriginalKarimenPilotStagingService::COLLECTION_KEY, false)
            ->assertViewHas('entries', function ($entries): bool {
                return $entries->total() === 10
                    && $entries->every(
                        fn (QuestionImportEntry $item): bool => $item->source_group === OriginalKarimenPilotStagingService::SOURCE_GROUP
                    );
            });

        $japanese = (string) $entry->translationFor('ja')?->question_text;

        Livewire::actingAs($admin)
            ->test(QuestionBankShow::class, ['entry' => $entry->fresh(['translations', 'batch'])])
            ->assertOk()
            ->assertSee('menkyo_original_karimen_pilot_001', false)
            ->assertSee($japanese, false)
            ->assertSee('original_menkyo_pilot', false)
            ->assertSee('公開不可', false);

        $this->assertStringContainsString('laws.e-gov.go.jp', (string) data_get($entry->accuracy_evidence_sources, '0.url'));
        $this->assertStringContainsString('第7条', (string) $entry->answer_key_reference);
    }

    #[Test]
    public function test_adsense_remains_disabled_and_demo_exam_type_still_configured(): void
    {
        config(['services.adsense.enabled' => false]);
        $this->seed(ExamTypeSeeder::class);
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $this->assertFalse((bool) config('services.adsense.enabled'));
        $this->get(route('home'))
            ->assertSuccessful()
            ->assertDontSee('data-adsense-auto', false);

        $offer = app(QuestionSelectionService::class)->practiceOffer('karimen', null, 'ja');
        $this->assertTrue($offer['configured']);
        $this->assertArrayHasKey('demo', $offer);
        $this->assertArrayHasKey('full', $offer);
        $this->assertFalse($offer['full']['can_start']);
    }

    #[Test]
    public function test_staging_skips_human_protected_entries_without_overwrite(): void
    {
        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $entry = QuestionImportEntry::query()
            ->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)
            ->where('original_question_number', 1)
            ->firstOrFail();

        $entry->forceFill([
            'answer_key_status' => AnswerKeyReviewStatus::Verified,
            'answer_key_verified_at' => now(),
            'answer_key_notes' => 'Human verified fixture',
            'english_question' => 'PROTECTED ENGLISH MUST REMAIN',
        ])->save();

        Artisan::call('menkyo:stage-original-karimen-pilot', ['--commit' => true]);

        $entry->refresh();
        $this->assertSame('PROTECTED ENGLISH MUST REMAIN', $entry->english_question);
        $this->assertSame(AnswerKeyReviewStatus::Verified, $entry->answer_key_status);
        $this->assertSame(10, QuestionImportEntry::query()->where('source_group', OriginalKarimenPilotStagingService::SOURCE_GROUP)->count());
    }
}
