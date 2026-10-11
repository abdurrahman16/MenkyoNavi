<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_key');
            $table->string('source_filename');
            $table->string('source_relative_path');
            $table->string('file_checksum', 64);
            $table->string('source_locale', 16)->default('en');
            $table->string('status')->default('staged');
            $table->unsignedInteger('entry_count')->default(0);
            $table->unsignedInteger('parse_error_count')->default(0);
            $table->unsignedInteger('duplicate_across_sets_count')->default(0);
            $table->unsignedInteger('same_wording_different_answer_count')->default(0);
            $table->unsignedInteger('flagged_for_review_count')->default(0);
            $table->unsignedInteger('media_review_count')->default(0);
            $table->json('group_counts')->nullable();
            $table->json('parse_errors')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique('source_key');
            $table->index('file_checksum');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_import_batches');
    }
};
