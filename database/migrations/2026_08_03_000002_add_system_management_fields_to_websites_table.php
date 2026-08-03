<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            if (!Schema::hasColumn('websites', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('embed_token')->index();
            }
            if (!Schema::hasColumn('websites', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('websites', 'suspended_by')) {
                $table->foreignId('suspended_by')->nullable()->after('suspended_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('websites', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            if (Schema::hasColumn('websites', 'suspended_by')) {
                $table->dropConstrainedForeignId('suspended_by');
            }
            if (Schema::hasColumn('websites', 'suspended_at')) {
                $table->dropColumn('suspended_at');
            }
            if (Schema::hasColumn('websites', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
