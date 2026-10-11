<?php

namespace Tests\Feature\Translations;

use App\Models\QuestionImportEntry;
use App\Services\Translations\HonmenIllustrationAuditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HonmenIllustrationAuditTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_honmen_audit_reports_inline_sign_references_without_inventing_illustrated_groups(): void
    {
        QuestionImportEntry::factory()->count(3)->create([
            'exam_family' => 'honmen',
            'source_group' => 'honmen_dl_1',
            'sign_key' => 'pedbike',
            'figure_caption' => 'Pedestrian crossing sign',
        ]);

        QuestionImportEntry::factory()->create([
            'exam_family' => 'honmen',
            'source_group' => 'honmen_dl_1',
            'sign_key' => null,
            'figure_caption' => null,
            'original_question_number' => 40,
        ]);

        $report = app(HonmenIllustrationAuditor::class)->audit();

        $this->assertSame(4, $report['honmen_entry_count']);
        $this->assertSame(3, $report['entries_with_sign_key']);
        $this->assertSame(0, $report['illustrated_group_records_found']);
        $this->assertFalse($report['source_has_illustrated_groups']);
        $this->assertTrue($report['source_has_only_inline_sign_references']);
        $this->assertSame(5, $report['engine_requires_illustrated_groups']);
        $this->assertFalse($report['mock_exam_ready']);
        $this->assertTrue(collect($report['completeness_notes'])->contains(
            fn (string $note): bool => str_contains($note, 'Do not convert ordinary sign questions'),
        ));
    }
}
