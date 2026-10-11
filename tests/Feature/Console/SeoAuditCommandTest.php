<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeoAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_audit_command_is_read_only_and_reports_key_sections(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->artisan('menkyo:seo-audit')
            ->assertSuccessful()
            ->expectsOutputToContain('MenkyoNavi technical SEO audit (read-only)')
            ->expectsOutputToContain('Sitemap')
            ->expectsOutputToContain('language_seo_limitation')
            ->expectsOutputToContain('draft_translations_noindex');

        foreach ($queries as $sql) {
            $this->assertDoesNotMatchRegularExpression(
                '/\b(insert|update|delete|replace|alter|drop|truncate)\b/i',
                $sql,
                'SEO audit must not mutate the database: '.$sql,
            );
        }
    }

    public function test_seo_audit_json_output_includes_inventory(): void
    {
        $this->withoutMockingConsoleOutput();

        $exitCode = Artisan::call('menkyo:seo-audit', ['--json' => true]);
        $output = Artisan::output();
        $payload = json_decode($output, true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertTrue($payload['read_only'] ?? false);
        $this->assertGreaterThan(0, $payload['sitemap']['entry_count'] ?? 0);
        $this->assertStringContainsString('learn.road-signs', $output);
    }
}
