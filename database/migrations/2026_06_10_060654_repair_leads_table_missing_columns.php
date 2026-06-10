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
            if (!Schema::hasColumn('leads', 'country')) {
                $table->string('country')->nullable();
            }

            if (!Schema::hasColumn('leads', 'preferred_contact_time')) {
                $table->string('preferred_contact_time')->nullable();
            }

            if (!Schema::hasColumn('leads', 'product_interest')) {
                $table->string('product_interest')->nullable();
            }

            if (!Schema::hasColumn('leads', 'lead_score')) {
                $table->integer('lead_score')->default(0);
            }

            if (!Schema::hasColumn('leads', 'status')) {
                $table->string('status')->default('new');
            }

            if (!Schema::hasColumn('leads', 'extra_data')) {
                $table->json('extra_data')->nullable();
            }

            if (!Schema::hasColumn('leads', 'qualified_at')) {
                $table->timestamp('qualified_at')->nullable();
            }

            if (!Schema::hasColumn('leads', 'contacted_at')) {
                $table->timestamp('contacted_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'country')) {
                $table->dropColumn('country');
            }

            if (Schema::hasColumn('leads', 'preferred_contact_time')) {
                $table->dropColumn('preferred_contact_time');
            }

            if (Schema::hasColumn('leads', 'product_interest')) {
                $table->dropColumn('product_interest');
            }

            if (Schema::hasColumn('leads', 'lead_score')) {
                $table->dropColumn('lead_score');
            }

            if (Schema::hasColumn('leads', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('leads', 'extra_data')) {
                $table->dropColumn('extra_data');
            }

            if (Schema::hasColumn('leads', 'qualified_at')) {
                $table->dropColumn('qualified_at');
            }

            if (Schema::hasColumn('leads', 'contacted_at')) {
                $table->dropColumn('contacted_at');
            }
        });
    }
};
