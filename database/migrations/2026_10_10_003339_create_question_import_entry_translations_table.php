<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_import_entry_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_import_entry_id')
                ->constrained('question_import_entries')
                ->cascadeOnDelete();
            $table->string('locale', 16);
            $table->text('question_text')->nullable();
            $table->text('explanation')->nullable();
            $table->text('figure_caption')->nullable();
            $table->text('nuance_note')->nullable();
            $table->json('japanese_reading_data')->nullable();
            $table->string('status')->default('pending_translation');
            $table->json('quality_flags')->nullable();
            $table->timestamp('translated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('translator_note')->nullable();
            $table->timestamps();

            $table->unique(['question_import_entry_id', 'locale'], 'qi_entry_translations_entry_locale_unique');
            $table->index('locale');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_entry_translations');
    }
};
