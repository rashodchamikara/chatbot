<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('channel_connections', 'website_id')) {
            Schema::table('channel_connections', function (Blueprint $table): void {
                $table->unsignedBigInteger('website_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        /*
         * Intentionally left nullable. Once standalone WhatsApp rows exist,
         * making website_id NOT NULL again would invalidate production data.
         */
    }
};
