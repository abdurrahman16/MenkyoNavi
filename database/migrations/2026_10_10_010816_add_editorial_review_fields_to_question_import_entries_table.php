<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table) {
            $table->json('editorial_categories')->nullable()->after('exam_family');
            $table->boolean('classification_approved')->default(false)->after('editorial_categories');
            $table->foreignId('classification_approved_by')
                ->nullable()
                ->after('classification_approved')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('classification_approved_at')->nullable()->after('classification_approved_by');
            $table->string('editorial_workflow_status')->default('needs_review')->after('classification_approved_at');
            $table->text('editorial_notes')->nullable()->after('editorial_workflow_status');
            $table->boolean('proposed_correct_answer')->nullable()->after('answer_key_verified_at');
            $table->text('proposed_answer_notes')->nullable()->after('proposed_correct_answer');
            $table->text('content_rights_evidence')->nullable()->after('content_rights_notes');
            $table->foreignId('content_rights_reviewed_by')
                ->nullable()
                ->after('content_rights_evidence')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('content_rights_reviewed_at')->nullable()->after('content_rights_reviewed_by');

            $table->index('editorial_workflow_status');
            $table->index('classification_approved');
        });
    }

    public function down(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('classification_approved_by');
            $table->dropConstrainedForeignId('content_rights_reviewed_by');
            $table->dropIndex(['editorial_workflow_status']);
            $table->dropIndex(['classification_approved']);
            $table->dropColumn([
                'editorial_categories',
                'classification_approved',
                'classification_approved_at',
                'editorial_workflow_status',
                'editorial_notes',
                'proposed_correct_answer',
                'proposed_answer_notes',
                'content_rights_evidence',
                'content_rights_reviewed_at',
            ]);
        });
    }
};
