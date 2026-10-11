<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table) {
            $table->string('answer_key_status')->default('verification_pending')->after('is_published');
            $table->text('answer_key_notes')->nullable()->after('answer_key_status');
            $table->string('answer_key_reference')->nullable()->after('answer_key_notes');
            $table->timestamp('answer_key_verified_at')->nullable()->after('answer_key_reference');
            $table->string('content_rights_status')->default('unclear')->after('answer_key_verified_at');
            $table->text('content_rights_notes')->nullable()->after('content_rights_status');
            $table->json('editorial_issues')->nullable()->after('content_rights_notes');
            $table->json('publication_blockers')->nullable()->after('editorial_issues');
            $table->foreignId('promoted_question_id')
                ->nullable()
                ->after('publication_blockers')
                ->constrained('questions')
                ->nullOnDelete();
            $table->timestamp('promoted_at')->nullable()->after('promoted_question_id');

            $table->index('answer_key_status');
            $table->index('content_rights_status');
            $table->index('promoted_question_id');
        });
    }

    public function down(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promoted_question_id');
            $table->dropColumn([
                'answer_key_status',
                'answer_key_notes',
                'answer_key_reference',
                'answer_key_verified_at',
                'content_rights_status',
                'content_rights_notes',
                'editorial_issues',
                'publication_blockers',
                'promoted_at',
            ]);
        });
    }
};
