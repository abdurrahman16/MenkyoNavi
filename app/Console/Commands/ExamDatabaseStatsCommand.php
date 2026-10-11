<?php

namespace App\Console\Commands;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\QuestionStatementTranslation;
use App\Models\QuestionTranslation;
use Illuminate\Console\Command;

class ExamDatabaseStatsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'menkyo:exam-stats';

    /**
     * @var string
     */
    protected $description = 'Show development-only statistics for the exam question database';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production.');

            return self::FAILURE;
        }

        $publishedOrReviewed = Question::query()
            ->whereIn('status', [
                QuestionStatus::Published->value,
                QuestionStatus::Reviewed->value,
            ])
            ->count();

        $illustratedGroups = Question::query()
            ->where('type', QuestionType::IllustratedGroup->value)
            ->count();

        $this->info('MenkyoNavi exam database stats (development)');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Exam types', ExamType::query()->count()],
                ['Categories', QuestionCategory::query()->count()],
                ['Questions (all)', Question::query()->count()],
                ['Questions (demo)', Question::query()->where('is_demo', true)->count()],
                ['Questions (published/reviewed)', $publishedOrReviewed],
                ['Question translations', QuestionTranslation::query()->count()],
                ['Statement translations', QuestionStatementTranslation::query()->count()],
                ['Illustrated question groups', $illustratedGroups],
            ]
        );

        $this->comment('Demo/sample questions remain unpublished until content review.');

        return self::SUCCESS;
    }
}
