<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_question_mistakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('last_exam_attempt_id')->nullable()->constrained('exam_attempts')->nullOnDelete();
            $table->unsignedInteger('incorrect_count')->default(1);
            $table->timestamp('last_incorrect_at');
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_question_mistakes');
    }
};
