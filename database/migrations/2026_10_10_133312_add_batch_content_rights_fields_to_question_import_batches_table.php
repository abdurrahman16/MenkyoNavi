<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_import_batches', function (Blueprint $table) {
            $table->string('content_rights_status', 64)
                ->default('no_evidence_submitted')
                ->after('summary');
            $table->string('claimed_creator')->nullable()->after('content_rights_status');
            $table->string('origin_url', 2000)->nullable()->after('claimed_creator');
            $table->text('acquisition_notes')->nullable()->after('origin_url');
            $table->text('license_notes')->nullable()->after('acquisition_notes');
            $table->text('permission_notes')->nullable()->after('license_notes');
            $table->text('reuse_scope_notes')->nullable()->after('permission_notes');
            $table->text('figure_rights_notes')->nullable()->after('reuse_scope_notes');
            $table->json('rights_evidence')->nullable()->after('figure_rights_notes');
            $table->string('rights_document_path')->nullable()->after('rights_evidence');
            $table->string('rights_document_original_name')->nullable()->after('rights_document_path');
            $table->foreignId('content_rights_reviewed_by')
                ->nullable()
                ->after('rights_document_original_name')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('content_rights_reviewed_at')->nullable()->after('content_rights_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('question_import_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('content_rights_reviewed_by');
            $table->dropColumn([
                'content_rights_status',
                'claimed_creator',
                'origin_url',
                'acquisition_notes',
                'license_notes',
                'permission_notes',
                'reuse_scope_notes',
                'figure_rights_notes',
                'rights_evidence',
                'rights_document_path',
                'rights_document_original_name',
                'content_rights_reviewed_at',
            ]);
        });
    }
};
