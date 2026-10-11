<?php

namespace Tests\Feature;

use Database\Seeders\DemoQuestionSeeder;
use Database\Seeders\ExamTypeSeeder;
use Database\Seeders\QuestionCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Exams\CreatesPracticeExams;
use Tests\TestCase;

class NavigationHomeTest extends TestCase
{
    use CreatesPracticeExams;
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function homeLabels(): array
    {
        return [
            'ja' => ['ja', 'ホーム'],
            'en' => ['en', 'Home'],
            'zh' => ['zh', '首页'],
            'vi' => ['vi', 'Trang chủ'],
            'fil' => ['fil', 'Home'],
            'ne' => ['ne', 'गृहपृष्ठ'],
            'bn' => ['bn', 'হোম'],
            'ko' => ['ko', '홈'],
        ];
    }

    public function test_desktop_and_mobile_navigation_contain_home_link(): void
    {
        $response = $this->get('/')->assertSuccessful();
        $html = $response->getContent();
        $homeLabel = __('navigation.home', [], 'ja');

        $this->assertStringContainsString($homeLabel, $html);
        $this->assertStringContainsString(route('home'), $html);
        $this->assertStringContainsString('id="mobile-menu"', $html);
        $this->assertSame(2, preg_match_all('/'.preg_quote($homeLabel, '/').'/', $html));
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'href="'.route('home').'"'));
        $this->assertStringContainsString(__('navigation.exams', [], 'ja'), $html);
        $this->assertStringContainsString(__('navigation.learn', [], 'ja'), $html);
        $this->assertStringContainsString(__('navigation.road_signs', [], 'ja'), $html);
        $this->assertStringContainsString(__('navigation.guides', [], 'ja'), $html);
        $this->assertStringContainsString(__('navigation.faq', [], 'ja'), $html);
        $this->assertStringContainsString(__('navigation.about', [], 'ja'), $html);
    }

    public function test_home_nav_is_active_only_on_homepage(): void
    {
        $homeLabel = preg_quote(__('navigation.home', [], 'ja'), '/');

        $home = $this->get('/')->assertSuccessful()->getContent();
        $this->assertMatchesRegularExpression(
            '/class="[^"]*nav-link[^"]*is-active[^"]*"[\s\S]{0,80}?'.$homeLabel.'/',
            $home
        );

        $exams = $this->get('/exams')->assertSuccessful()->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/class="[^"]*nav-link[^"]*is-active[^"]*"[\s\S]{0,80}?'.$homeLabel.'/',
            $exams
        );
        $this->assertStringContainsString('href="'.route('home').'"', $exams);
    }

    #[DataProvider('homeLabels')]
    public function test_home_navigation_label_exists_for_each_locale(string $locale, string $label): void
    {
        $this->assertSame($label, __('navigation.home', [], $locale));

        $this->from('/')
            ->post(route('locale.switch', $locale))
            ->assertRedirect('/');

        $this->get('/')
            ->assertSuccessful()
            ->assertSee($label, false);
    }

    public function test_language_switching_still_updates_navigation_labels(): void
    {
        $this->from('/')
            ->post(route('locale.switch', 'en'))
            ->assertRedirect('/');

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Home', false)
            ->assertSee('Exams', false)
            ->assertSee('Learn', false);

        $this->from('/')
            ->post(route('locale.switch', 'ko'))
            ->assertRedirect('/');

        $this->get('/')
            ->assertSuccessful()
            ->assertSee('홈', false)
            ->assertSee('학과 시험', false);
    }

    public function test_colorful_practice_panel_keeps_disabled_full_practice_state(): void
    {
        $this->seed([
            ExamTypeSeeder::class,
            QuestionCategorySeeder::class,
            DemoQuestionSeeder::class,
        ]);

        $user = $this->learner();

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/exams/karimen')
            ->assertSuccessful()
            ->assertSee('data-practice-panel="featured"', false)
            ->assertSee('btn-gold', false)
            ->assertSee('exam-fact-chip--questions', false)
            ->assertSee('data-full-practice-unavailable', false)
            ->assertSee(__('exams.full_practice_unavailable', [], 'en'), false);
    }

    public function test_road_signs_hub_remains_functional_with_color_updates(): void
    {
        $this->get('/road-signs')
            ->assertSuccessful()
            ->assertSee('data-road-sign-card', false)
            ->assertSee('filter-chip-active', false)
            ->assertSee('data-sign-recognition-practice', false)
            ->assertSee('learn-card--vermilion', false);
    }
}
