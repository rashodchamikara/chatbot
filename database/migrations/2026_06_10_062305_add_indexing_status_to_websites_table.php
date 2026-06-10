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
        Schema::table('websites', function (Blueprint $table) {
            if (!Schema::hasColumn('websites', 'indexing_status')) {
                $table->string('indexing_status')
                    ->default('pending')
                    ->after('is_active');
            }

            if (!Schema::hasColumn('websites', 'indexing_started_at')) {
                $table->timestamp('indexing_started_at')
                    ->nullable()
                    ->after('indexing_status');
            }

            if (!Schema::hasColumn('websites', 'indexing_completed_at')) {
                $table->timestamp('indexing_completed_at')
                    ->nullable()
                    ->after('indexing_started_at');
            }

            if (!Schema::hasColumn('websites', 'indexing_error')) {
                $table->text('indexing_error')
                    ->nullable()
                    ->after('indexing_completed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            if (Schema::hasColumn('websites', 'indexing_status')) {
                $table->dropColumn('indexing_status');
            }

            if (Schema::hasColumn('websites', 'indexing_started_at')) {
                $table->dropColumn('indexing_started_at');
            }

            if (Schema::hasColumn('websites', 'indexing_completed_at')) {
                $table->dropColumn('indexing_completed_at');
            }

            if (Schema::hasColumn('websites', 'indexing_error')) {
                $table->dropColumn('indexing_error');
            }
        });
    }
};
