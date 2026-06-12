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
            if (!Schema::hasColumn('websites', 'chatbot_name')) {
                $table->string('chatbot_name')->nullable()->after('name');
            }

            if (!Schema::hasColumn('websites', 'chatbot_theme')) {
                $table->string('chatbot_theme')->default('blue')->after('chatbot_name');
            }

            if (!Schema::hasColumn('websites', 'chatbot_avatar')) {
                $table->string('chatbot_avatar')->nullable()->after('chatbot_theme');
            }

            if (!Schema::hasColumn('websites', 'chatbot_instructions')) {
                $table->longText('chatbot_instructions')->nullable()->after('chatbot_avatar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            if (Schema::hasColumn('websites', 'chatbot_instructions')) {
                $table->dropColumn('chatbot_instructions');
            }

            if (Schema::hasColumn('websites', 'chatbot_avatar')) {
                $table->dropColumn('chatbot_avatar');
            }

            if (Schema::hasColumn('websites', 'chatbot_theme')) {
                $table->dropColumn('chatbot_theme');
            }

            if (Schema::hasColumn('websites', 'chatbot_name')) {
                $table->dropColumn('chatbot_name');
            }
        });
    }
};
