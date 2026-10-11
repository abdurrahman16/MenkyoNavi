<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type');
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->text('japanese_text');
            $table->json('japanese_reading_data')->nullable();
            $table->boolean('correct_answer')->nullable();
            $table->text('explanation_ja')->nullable();
            $table->foreignId('question_category_id')
                ->nullable()
                ->constrained('question_categories')
                ->nullOnDelete();
            $table->foreignId('source_reference_id')
                ->nullable()
                ->constrained('source_references')
                ->nullOnDelete();
            $table->string('source_page_number')->nullable();
            $table->string('content_review_status')->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->unsignedSmallInteger('points')->default(1);
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index(['is_demo', 'status']);
            $table->index('question_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
