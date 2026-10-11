<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 16);
            $table->text('question_text');
            $table->text('explanation')->nullable();
            $table->text('nuance_note')->nullable();
            $table->string('review_status')->default('pending');
            $table->timestamps();

            $table->unique(['question_id', 'locale']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_translations');
    }
};
