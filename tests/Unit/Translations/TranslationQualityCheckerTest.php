<?php

namespace Tests\Unit\Translations;

use App\Models\QuestionImportEntry;
use App\Services\Translations\TranslationQualityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranslationQualityCheckerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_flags_missing_text_wrong_locale_unsafe_markup_and_untranslated_english(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Overtaking is prohibited within 30 meters before a railway crossing.',
            'english_explanation' => 'This prevents accidents at railway crossings.',
            'figure_caption' => 'Railway crossing diagram',
        ]);

        $checker = new TranslationQualityChecker;

        $flags = $checker->check($entry, [
            'locale' => 'xx',
            'question_text' => '',
            'explanation' => null,
            'figure_caption' => null,
        ]);

        $this->assertContains('wrong_language_code', $flags);
        $this->assertContains('missing_translation_text', $flags);
        $this->assertContains('missing_explanation', $flags);
        $this->assertContains('missing_figure_caption', $flags);

        $unsafe = $checker->check($entry, [
            'locale' => 'ja',
            'question_text' => '<script>alert(1)</script>危険',
            'explanation' => '説明',
            'figure_caption' => '図',
        ]);

        $this->assertContains('unsafe_markup_question_text', $unsafe);
        $this->assertContains('unexpected_html_question_text', $unsafe);

        $untranslated = $checker->check($entry, [
            'locale' => 'ja',
            'question_text' => $entry->english_question,
            'explanation' => $entry->english_explanation,
            'figure_caption' => '図',
        ]);

        $this->assertContains('untranslated_english_question', $untranslated);

        $numbers = $checker->check($entry, [
            'locale' => 'ja',
            'question_text' => '踏切の手前50メートル以内では追い越し禁止。',
            'explanation' => '事故防止のため。',
            'figure_caption' => '図',
        ]);

        $this->assertContains('potentially_altered_numbers', $numbers);
    }

    #[Test]
    public function test_accepts_unicode_translations_that_preserve_numbers(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Overtaking is prohibited within 30 meters before a railway crossing.',
            'english_explanation' => 'This prevents accidents.',
        ]);

        $flags = (new TranslationQualityChecker)->check($entry, [
            'locale' => 'bn',
            'question_text' => 'রেলক্রসিংয়ের আগে 30 meters-এর মধ্যে ওভারটেক নিষিদ্ধ।',
            'explanation' => 'দুর্ঘটনা রোধ করতে।',
        ]);

        $this->assertNotContains('invalid_unicode_question_text', $flags);
        $this->assertNotContains('potentially_altered_numbers', $flags);
        $this->assertNotContains('missing_translation_text', $flags);
    }

    #[Test]
    public function test_flags_missing_negation_when_english_prohibition_is_dropped(): void
    {
        $entry = QuestionImportEntry::factory()->create([
            'english_question' => 'Vehicles must not overtake at a pedestrian crossing.',
            'english_explanation' => 'Overtaking is not allowed there.',
        ]);

        $flags = (new TranslationQualityChecker)->check($entry, [
            'locale' => 'ja',
            'question_text' => '車両は横断歩道で追い越しをする。',
            'explanation' => 'そこでは追い越しが認められる。',
        ]);

        $this->assertContains('missing_negation', $flags);
        $this->assertContains('missing_negation_explanation', $flags);

        $ok = (new TranslationQualityChecker)->check($entry, [
            'locale' => 'ja',
            'question_text' => '車両は横断歩道で追い越しをしてはならない。',
            'explanation' => 'そこでは追い越しは認められない。',
        ]);

        $this->assertNotContains('missing_negation', $ok);
        $this->assertNotContains('missing_negation_explanation', $ok);

        $chinese = (new TranslationQualityChecker)->check($entry, [
            'locale' => 'zh',
            'question_text' => '车辆不得在人行横道超车。',
            'explanation' => '那里不允许超车。',
        ]);

        $this->assertNotContains('missing_negation', $chinese);
        $this->assertNotContains('missing_negation_explanation', $chinese);
    }
}
