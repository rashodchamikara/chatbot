<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            if (!Schema::hasColumn('websites', 'realtime_token')) {
                $table->string('realtime_token', 80)->nullable()->after('embed_token');
            }

            if (!Schema::hasColumn('websites', 'live_chat_enabled')) {
                $table->boolean('live_chat_enabled')->default(true)->after('realtime_token');
            }
        });

        DB::table('websites')
            ->whereNull('realtime_token')
            ->orderBy('id')
            ->chunkById(100, function ($websites) {
                foreach ($websites as $website) {
                    DB::table('websites')
                        ->where('id', $website->id)
                        ->update([
                            'realtime_token' => Str::random(64),
                        ]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'agent_status')) {
                $table->string('agent_status')->default('offline')->after('role');
            }

            if (!Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('agent_status');
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'mode')) {
                $table->string('mode')->default('ai')->after('status');
            }

            if (!Schema::hasColumn('conversations', 'realtime_token')) {
                $table->string('realtime_token', 80)->nullable()->after('mode');
            }

            if (!Schema::hasColumn('conversations', 'assigned_agent_id')) {
                $table->foreignId('assigned_agent_id')
                    ->nullable()
                    ->after('realtime_token')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'live_requested_at')) {
                $table->timestamp('live_requested_at')->nullable()->after('assigned_agent_id');
            }

            if (!Schema::hasColumn('conversations', 'live_started_at')) {
                $table->timestamp('live_started_at')->nullable()->after('live_requested_at');
            }

            if (!Schema::hasColumn('conversations', 'live_ended_at')) {
                $table->timestamp('live_ended_at')->nullable()->after('live_started_at');
            }
        });

        DB::table('conversations')
            ->whereNull('realtime_token')
            ->orderBy('id')
            ->chunkById(100, function ($conversations) {
                foreach ($conversations as $conversation) {
                    DB::table('conversations')
                        ->where('id', $conversation->id)
                        ->update([
                            'realtime_token' => Str::random(64),
                        ]);
                }
            });

        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('conversation_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('messages', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('sender');
            }
        });
    }

     public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'is_system')) {
                $table->dropColumn('is_system');
            }

            if (Schema::hasColumn('messages', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            foreach ([
                'live_ended_at',
                'live_started_at',
                'live_requested_at',
            ] as $column) {
                if (Schema::hasColumn('conversations', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('conversations', 'assigned_agent_id')) {
                $table->dropConstrainedForeignId('assigned_agent_id');
            }

            if (Schema::hasColumn('conversations', 'realtime_token')) {
                $table->dropColumn('realtime_token');
            }

            if (Schema::hasColumn('conversations', 'mode')) {
                $table->dropColumn('mode');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }

            if (Schema::hasColumn('users', 'agent_status')) {
                $table->dropColumn('agent_status');
            }
        });

        Schema::table('websites', function (Blueprint $table) {
            if (Schema::hasColumn('websites', 'live_chat_enabled')) {
                $table->dropColumn('live_chat_enabled');
            }

            if (Schema::hasColumn('websites', 'realtime_token')) {
                $table->dropColumn('realtime_token');
            }
        });
    }
};
