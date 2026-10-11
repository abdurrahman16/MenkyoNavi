<?php

namespace Tests\Feature\Console\Commands;

use App\Models\QuestionImportEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportKarimenHtmlCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_defaults_to_dry_run_when_neither_flag_is_provided(): void
    {
        $exit = Artisan::call('menkyo:import-karimen-html', [
            '--file' => base_path('tests/Fixtures/Imports/karimen_mini_valid.html'),
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Defaulting to --dry-run', Artisan::output());
        $this->assertDatabaseCount('question_import_entries', 0);
    }

    #[Test]
    public function test_rejects_dry_run_and_commit_together(): void
    {
        $exit = Artisan::call('menkyo:import-karimen-html', [
            '--file' => base_path('tests/Fixtures/Imports/karimen_mini_valid.html'),
            '--dry-run' => true,
            '--commit' => true,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Choose either --dry-run or --commit', Artisan::output());
        $this->assertSame(0, QuestionImportEntry::query()->count());
    }
}
