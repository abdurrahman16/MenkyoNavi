<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_import_review_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_import_entry_id')
                ->constrained('question_import_entries')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('action');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['question_import_entry_id', 'created_at']);
            $table->index(['user_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_review_audits');
    }
};
