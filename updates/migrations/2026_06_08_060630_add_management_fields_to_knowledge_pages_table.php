<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('knowledge_pages', function (Blueprint $table) {
            $table->string('source_type')
                ->default('crawler')
                ->after('type');

            $table->boolean('is_active')
                ->default(true)
                ->after('is_indexed');

            $table->timestamp('indexed_at')
                ->nullable()
                ->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_pages', function (Blueprint $table) {
            $table->dropColumn('source_type');
            $table->dropColumn('is_active');
            $table->dropColumn('indexed_at');
        });
    }
};
