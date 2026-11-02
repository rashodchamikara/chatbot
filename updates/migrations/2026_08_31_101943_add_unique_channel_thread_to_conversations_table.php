<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'conversations',
            function (
                Blueprint $table
            ): void {
                $table->unique(
                    [
                        'channel_connection_id',
                        'external_thread_id',
                    ],
                    'conversations_channel_thread_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'conversations',
            function (
                Blueprint $table
            ): void {
                $table->dropUnique(
                    'conversations_channel_thread_unique'
                );
            }
        );
    }
};