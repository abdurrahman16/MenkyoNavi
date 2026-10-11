<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_import_duplicate_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')
                ->constrained('question_import_entries')
                ->cascadeOnDelete();
            $table->foreignId('related_entry_id')
                ->constrained('question_import_entries')
                ->cascadeOnDelete();
            $table->string('similarity_reason');
            $table->string('decision')->default('needs_additional_review');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['entry_id', 'related_entry_id'], 'qi_dup_review_pair_unique');
            $table->index('decision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_duplicate_reviews');
    }
};
