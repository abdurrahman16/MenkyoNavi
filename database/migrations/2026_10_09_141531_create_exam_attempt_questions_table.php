<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('question_uuid');
            $table->unsignedSmallInteger('position');
            $table->string('question_type');
            $table->string('scoring_mode');
            $table->unsignedSmallInteger('scoring_weight');
            $table->unsignedSmallInteger('points_awarded')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->json('content_snapshot');
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'position']);
            $table->unique(['exam_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_questions');
    }
};
