<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('statement_slot');
            $table->foreignId('question_statement_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('selected_answer');
            $table->boolean('is_correct');
            $table->unsignedSmallInteger('points_awarded')->default(0);
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(['exam_attempt_question_id', 'statement_slot'], 'exam_attempt_answers_question_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_answers');
    }
};
