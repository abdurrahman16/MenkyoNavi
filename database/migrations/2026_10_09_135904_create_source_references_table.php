<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_references', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('issuing_organization')->nullable();
            $table->string('source_type');
            $table->string('original_url')->nullable();
            $table->string('edition_or_publication_date')->nullable();
            $table->string('page_number')->nullable();
            $table->date('last_verified_at')->nullable();
            $table->string('copyright_reuse_status');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_references');
    }
};
