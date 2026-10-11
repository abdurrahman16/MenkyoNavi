<?php

namespace Tests\Feature\Exams;

use Database\Seeders\DemoQuestionSeeder;
use Database\Seeders\ExamTypeSeeder;
use Database\Seeders\QuestionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamLandingPracticePanelTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    public function test_karimen_landing_renders_prominent_practice_panel_with_demo_cta(): void
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
            ->assertSee('data-practice-panel="featured"', false)
            ->assertSee(__('exams.practice_exam', [], 'en'), false)
            ->assertSee(__('exams.fact_questions', [], 'en'), false)
            ->assertSee(__('exams.fact_minutes', [], 'en'), false)
            ->assertSee('50', false)
            ->assertSee('30', false)
            ->assertSee('45/50', false)
            ->assertSee('data-demo-practice-cta', false)
            ->assertSee(__('exams.full_practice_unavailable', [], 'en'), false)
            ->assertSee('data-full-practice-unavailable', false)
            ->assertSee(__('exams.secondary_cta_rules', [], 'en'), false)
            ->assertSee(__('exams.secondary_cta_signs', [], 'en'), false)
            ->assertSee('data-demo-disclaimer', false)
            ->assertDontSee('rahman.just16@gmail.com', false)
            ->assertDontSee('owner@menkyo-navi.test', false);
    }

    public function test_shared_hubs_use_featured_practice_panel_pattern(): void
    {
        $this->seed(ExamTypeSeeder::class);

        foreach ([route('exams.karimen'), route('exams.gaimen-kirikae'), route('exams.honmen')] as $uri) {
            $this->withSession(['locale' => 'ja'])
                ->get($uri)
                ->assertSuccessful()
                ->assertSee('data-practice-panel="featured"', false)
                ->assertSee(__('exams.practice_exam', [], 'ja'), false);
        }
    }

    public function test_japanese_default_localization_on_karimen_practice_panel(): void
    {
        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);

        $this->get(route('exams.karimen'))
            ->assertSuccessful()
            ->assertSee(__('exams.practice_exam', [], 'ja'), false)
            ->assertSee(__('exams.demo_practice_heading', [], 'ja'), false);
    }
}
