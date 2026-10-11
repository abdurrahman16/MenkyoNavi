<?php

namespace Tests\Feature;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Models\ExamAttempt;
use App\Models\QuestionImportEntry;
use App\Models\User;
use App\Services\Exams\ExamAttemptService;
use App\Support\Content\HumanImages;
use App\Support\Content\StudyMethodsGuide;
use App\Support\Seo\CanonicalUrl;
use Database\Seeders\DemoQuestionSeeder;
use Database\Seeders\ExamTypeSeeder;
use Database\Seeders\QuestionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Exams\CreatesPracticeExams;
use Tests\TestCase;

class LearnerExperienceTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_learn_hub_includes_study_roadmap_and_practice_links(): void
    {
        $this->withSession(['locale' => 'ja'])
            ->get(route('learn.index'))
            ->assertSuccessful()
            ->assertSee(__('pages.learn.title', [], 'ja'), false)
            ->assertSee(__('pages.learn.roadmap_title', [], 'ja'), false)
            ->assertSee('data-study-roadmap', false)
            ->assertSee('data-roadmap-stage="1"', false)
            ->assertSee('data-roadmap-stage="5"', false)
            ->assertSee(route('learn.traffic-rules', absolute: false), false)
            ->assertSee(route('learn.road-signs', absolute: false), false)
            ->assertSee(route('learn.traffic-rules.pedestrian-priority', absolute: false), false)
            ->assertSee(route('learn.traffic-rules.intersection-priority', absolute: false), false)
            ->assertSee(route('exams.karimen', absolute: false), false)
            ->assertSee(route('exams.honmen', absolute: false), false)
            ->assertSee(route('exams.gaimen-kirikae', absolute: false), false)
            ->assertSee(route('learn.study-methods', absolute: false), false)
            ->assertSee(route('exams.index', absolute: false), false)
            ->assertSee('images/humans/learner-studying-desk', false);
    }

    public function test_study_methods_guide_renders_plans_tips_and_indexing_policy(): void
    {
        $this->assertTrue(StudyMethodsGuide::hasCompleteContent('ja'));
        $this->assertTrue(StudyMethodsGuide::isIndexable('ja'));
        $this->assertFalse(StudyMethodsGuide::isIndexable('en'));

        $this->withSession(['locale' => 'ja'])
            ->get(route('learn.study-methods'))
            ->assertSuccessful()
            ->assertSee(__('study_methods.title', [], 'ja'), false)
            ->assertSee(__('study_methods.plans.title', [], 'ja'), false)
            ->assertSee(__('study_methods.tips.title', [], 'ja'), false)
            ->assertSee('data-study-plans', false)
            ->assertSee('data-study-tips', false)
            ->assertSee(route('exams.index', absolute: false), false)
            ->assertDontSee('<meta name="robots" content="noindex', false);

        $this->withSession(['locale' => 'en'])
            ->get(route('learn.study-methods'))
            ->assertSuccessful()
            ->assertSee(__('study_methods.title', [], 'en'), false)
            ->assertSee('noindex', false);
    }

    public function test_human_image_assets_exist_with_documented_provenance(): void
    {
        $assets = HumanImages::all();
        $this->assertGreaterThanOrEqual(5, count($assets));

        foreach ($assets as $asset) {
            $this->assertTrue(HumanImages::pathExists($asset['key']));
            $this->assertSame('ai-generated', $asset['origin']);
            $this->assertSame('original-menkyo-navi-generated', $asset['license']);
            $this->assertTrue((bool) $asset['commercial_use']);
            $this->assertFileExists(public_path($asset['fallback']));
        }
    }

    public function test_learn_image_alt_text_is_localized_across_eight_languages(): void
    {
        foreach (['ja', 'en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            $alt = __('pages.learn.hero_image_alt', [], $locale);
            $this->assertNotSame('', $alt);
            $this->assertNotSame('pages.learn.hero_image_alt', $alt);

            $this->withSession(['locale' => $locale])
                ->get(route('learn.index'))
                ->assertSuccessful()
                ->assertSee($alt, false)
                ->assertSee(__('pages.learn.roadmap_title', [], $locale), false)
                ->assertSee(__('pages.learn.study_methods_title', [], $locale), false);
        }
    }

    public function test_homepage_learner_journey_links_to_learn_and_practice(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get(route('home'))
            ->assertSuccessful()
            ->assertSee(route('learn.index', absolute: false), false)
            ->assertSee(__('home.learn_cta', [], 'en'), false)
            ->assertSee(__('home.learn_feature_title', [], 'en'), false)
            ->assertSee('data-home-learn-feature', false)
            ->assertSee('images/humans/learner-studying-desk', false);
    }

    public function test_demo_practice_entry_works_and_full_practice_stays_blocked_without_pool(): void
    {
        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);

        $user = $this->learner();

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.karimen'))
            ->assertSuccessful()
            ->assertSee('data-demo-practice-cta', false)
            ->assertSee('data-full-practice-unavailable', false)
            ->assertSee(__('exams.secondary_cta_learn', [], 'en'), false)
            ->assertSee(__('exams.secondary_cta_study', [], 'en'), false)
            ->assertSee(route('learn.study-methods', absolute: false), false);
    }

    public function test_results_page_offers_learning_next_steps(): void
    {
        $user = $this->learner();
        $examType = $this->examTypeWithRules(1, 0, 1);
        $this->publishedTrueFalse($examType, true);
        $service = app(ExamAttemptService::class);
        $attempt = $service->start($user, $examType, ExamAttemptType::Full);
        $question = $attempt->questions->first();
        $service->recordAnswer($attempt, $question->id, 0, true);
        $final = $service->submit($attempt->fresh());

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('exams.attempts.results', $final))
            ->assertSuccessful()
            ->assertSee('data-results-next-steps', false)
            ->assertSee(__('results.open_mistake_vault', [], 'en'), false)
            ->assertSee(__('results.study_pedestrian', [], 'en'), false)
            ->assertSee(__('results.study_intersection', [], 'en'), false)
            ->assertSee(__('results.study_methods', [], 'en'), false)
            ->assertSee(route('learn.study-methods', absolute: false), false)
            ->assertSee(route('mistakes.index', absolute: false), false);
    }

    public function test_dashboard_shows_only_real_user_attempt_stats(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $examType = $this->examTypeWithRules(1, 0, 1);

        ExamAttempt::factory()->create([
            'user_id' => $user->id,
            'exam_type_id' => $examType->id,
            'attempt_type' => ExamAttemptType::Full,
            'status' => ExamAttemptStatus::Submitted,
            'score' => 1,
            'max_score' => 1,
            'submitted_at' => now(),
        ]);
        ExamAttempt::factory()->create([
            'user_id' => $other->id,
            'exam_type_id' => $examType->id,
            'attempt_type' => ExamAttemptType::Full,
            'status' => ExamAttemptStatus::Submitted,
            'score' => 1,
            'max_score' => 1,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertSuccessful()
            ->assertSee(__('panel.completed_full', [], 'en'), false)
            ->assertSee(__('panel.start_practice', [], 'en'), false)
            ->assertSee(__('panel.study_methods', [], 'en'), false)
            ->assertDontSee(__('panel.no_progress_title', [], 'en'), false)
            ->assertSeeInOrder([
                __('panel.completed_full', [], 'en'),
                '1',
            ], false);
    }

    public function test_eight_language_keys_exist_for_new_learner_copy(): void
    {
        $keys = [
            'pages.learn.roadmap_title',
            'pages.learn.journey_step_learn',
            'pages.learn.study_methods_cta',
            'pages.learn.hero_image_alt',
            'home.learn_cta',
            'home.learn_feature_title',
            'panel.start_practice',
            'panel.completed_full',
            'results.next_steps_lead',
            'results.open_mistake_vault',
            'exams.secondary_cta_study',
            'study_methods.title',
            'study_methods.plans.seven.title',
            'study_methods.tips.items.0.title',
        ];

        foreach (['ja', 'en', 'zh', 'vi', 'fil', 'ne', 'bn', 'ko'] as $locale) {
            foreach ($keys as $key) {
                $value = __($key, [], $locale);
                $this->assertNotSame($key, $value, "Missing {$key} for {$locale}");
                $this->assertNotSame('', $value);
            }
        }
    }

    public function test_seo_routes_include_study_methods_and_adsense_stays_disabled(): void
    {
        $this->assertFalse((bool) config('ads.enabled'));
        $this->assertContains('learn.study-methods', config('menkyo.sitemap_routes'));

        $xml = $this->get('/sitemap.xml')->assertSuccessful()->getContent();
        $this->assertStringContainsString('<loc>'.CanonicalUrl::route('learn.study-methods').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.CanonicalUrl::route('learn.index').'</loc>', $xml);
    }

    public function test_imported_questions_remain_unpublished(): void
    {
        $this->assertSame(
            0,
            QuestionImportEntry::query()->where('is_published', true)->count(),
        );

        $this->assertDatabaseCount('question_import_entries', 0);
    }
}
