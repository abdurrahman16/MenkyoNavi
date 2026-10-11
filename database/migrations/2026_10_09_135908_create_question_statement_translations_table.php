<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_statement_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_statement_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 16);
            $table->text('statement_text');
            $table->text('explanation')->nullable();
            $table->text('nuance_note')->nullable();
            $table->string('review_status')->default('pending');
            $table->timestamps();

            $table->unique(['question_statement_id', 'locale'], 'q_statement_locale_unique');
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_statement_translations');
    }
};
