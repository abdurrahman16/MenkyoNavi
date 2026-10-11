<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_type_id')->constrained()->cascadeOnDelete();
            $table->string('attempt_type');
            $table->string('status');
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedSmallInteger('question_count');
            $table->unsignedSmallInteger('max_score');
            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('passing_score');
            $table->boolean('passed')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('current_position')->default(1);
            $table->boolean('counts_toward_analytics')->default(true);
            $table->string('locale', 12)->default('en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
