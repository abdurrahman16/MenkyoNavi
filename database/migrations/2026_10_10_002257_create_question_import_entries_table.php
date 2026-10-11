<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_import_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_import_batch_id')
                ->constrained('question_import_batches')
                ->cascadeOnDelete();
            $table->string('source_group');
            $table->string('exam_family');
            $table->unsignedSmallInteger('original_question_number');
            $table->text('english_question');
            $table->boolean('correct_answer');
            $table->text('english_explanation');
            $table->string('sign_key')->nullable();
            $table->text('figure_caption')->nullable();
            $table->string('source_entry_checksum', 64);
            $table->string('content_fingerprint', 64);
            $table->string('question_text_fingerprint', 64);
            $table->string('import_status')->default('staged');
            $table->string('media_availability')->default('none');
            $table->string('duplicate_kind')->default('none');
            $table->foreignId('duplicate_of_entry_id')
                ->nullable()
                ->constrained('question_import_entries')
                ->nullOnDelete();
            $table->json('review_flags')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(
                ['question_import_batch_id', 'source_group', 'original_question_number'],
                'qi_entries_batch_group_number_unique'
            );
            $table->index('source_entry_checksum');
            $table->index('content_fingerprint');
            $table->index('question_text_fingerprint');
            $table->index(['exam_family', 'source_group']);
            $table->index('import_status');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_entries');
    }
};
