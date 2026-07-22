<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_sources', function (Blueprint $table) {
            if (!Schema::hasColumn('knowledge_sources', 'extracted_storage_path')) {
                $table->string('extracted_storage_path', 2048)
                    ->nullable()
                    ->after('storage_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_sources', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_sources', 'extracted_storage_path')) {
                $table->dropColumn('extracted_storage_path');
            }
        });
    }
};
