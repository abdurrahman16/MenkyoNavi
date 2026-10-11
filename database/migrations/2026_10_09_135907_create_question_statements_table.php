<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order');
            $table->text('japanese_text');
            $table->json('japanese_reading_data')->nullable();
            $table->boolean('correct_answer');
            $table->text('explanation_ja')->nullable();
            $table->timestamps();

            $table->unique(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_statements');
    }
};
