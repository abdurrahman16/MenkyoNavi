<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table) {
            $table->string('accuracy_evidence_status', 40)
                ->default('not_checked')
                ->after('answer_key_verified_at');
            $table->string('legal_topic')->nullable()->after('accuracy_evidence_status');
            $table->json('accuracy_evidence_sources')->nullable()->after('legal_topic');
            $table->text('accuracy_verification_notes')->nullable()->after('accuracy_evidence_sources');
            $table->foreignId('accuracy_reviewed_by')
                ->nullable()
                ->after('accuracy_verification_notes')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('accuracy_reviewed_at')->nullable()->after('accuracy_reviewed_by');
            $table->string('figure_review_status', 40)
                ->default('not_applicable')
                ->after('figure_caption');
            $table->text('figure_review_notes')->nullable()->after('figure_review_status');
        });
    }

    public function down(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accuracy_reviewed_by');
            $table->dropColumn([
                'accuracy_evidence_status',
                'legal_topic',
                'accuracy_evidence_sources',
                'accuracy_verification_notes',
                'accuracy_reviewed_at',
                'figure_review_status',
                'figure_review_notes',
            ]);
        });
    }
};
