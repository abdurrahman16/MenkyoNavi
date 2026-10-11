<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table) {
            $table->json('source_metadata')->nullable()->after('review_flags');
        });
    }

    public function down(): void
    {
        Schema::table('question_import_entries', function (Blueprint $table) {
            $table->dropColumn('source_metadata');
        });
    }
};
