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
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'qualified_at')) {
                $table->timestamp('qualified_at')
                    ->nullable()
                    ->after('extra_data');
            }

            if (!Schema::hasColumn('leads', 'contacted_at')) {
                $table->timestamp('contacted_at')
                    ->nullable()
                    ->after('qualified_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'qualified_at')) {
                $table->dropColumn('qualified_at');
            }

            if (Schema::hasColumn('leads', 'contacted_at')) {
                $table->dropColumn('contacted_at');
            }
        });
    }
};
