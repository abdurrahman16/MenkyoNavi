<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table): void {
            $table->string('proposed_exam_category')->nullable()->after('editorial_categories');
            $table->string('classification_proposal_source')->nullable()->after('proposed_exam_category');
            $table->json('study_topics')->nullable()->after('classification_proposal_source');
            $table->boolean('study_topics_approved')->default(false)->after('study_topics');
            $table->index('proposed_exam_category');
            $table->index('study_topics_approved');
        });
    }

    public function down(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table): void {
            $table->dropIndex(['proposed_exam_category']);
            $table->dropIndex(['study_topics_approved']);
            $table->dropColumn([
                'proposed_exam_category',
                'classification_proposal_source',
                'study_topics',
                'study_topics_approved',
            ]);
        });
    }
};
