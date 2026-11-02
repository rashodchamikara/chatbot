<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'tenant_id')) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'ai_agent_id')) {
                $table->foreignId('ai_agent_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('ai_agents')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'channel_connection_id')) {
                $table->foreignId('channel_connection_id')
                    ->nullable()
                    ->after('ai_agent_id')
                    ->constrained('channel_connections')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'contact_id')) {
                $table->foreignId('contact_id')
                    ->nullable()
                    ->after('channel_connection_id')
                    ->constrained('contacts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')
                    ->nullable()
                    ->after('contact_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'external_thread_id')) {
                $table->string('external_thread_id', 191)
                    ->nullable()
                    ->after('assigned_user_id');
            }

            if (!Schema::hasColumn('conversations', 'subject')) {
                $table->string('subject')
                    ->nullable()
                    ->after('external_thread_id');
            }

            if (!Schema::hasColumn('conversations', 'priority')) {
                $table->string('priority', 20)
                    ->default('normal')
                    ->after('subject');
            }

            if (!Schema::hasColumn('conversations', 'unread_count')) {
                $table->unsignedInteger('unread_count')
                    ->default(0)
                    ->after('priority');
            }

            if (!Schema::hasColumn('conversations', 'first_response_at')) {
                $table->timestamp('first_response_at')
                    ->nullable()
                    ->after('unread_count');
            }

            if (!Schema::hasColumn('conversations', 'last_message_at')) {
                $table->timestamp('last_message_at')
                    ->nullable()
                    ->after('first_response_at');
            }

            if (!Schema::hasColumn('conversations', 'last_inbound_at')) {
                $table->timestamp('last_inbound_at')
                    ->nullable()
                    ->after('last_message_at');
            }

            if (!Schema::hasColumn('conversations', 'reply_window_expires_at')) {
                $table->timestamp('reply_window_expires_at')
                    ->nullable()
                    ->after('last_inbound_at');
            }

            if (!Schema::hasColumn('conversations', 'metadata')) {
                $table->json('metadata')
                    ->nullable()
                    ->after('reply_window_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'assigned_user_id')) {
                $table->dropConstrainedForeignId('assigned_user_id');
            }

            if (Schema::hasColumn('conversations', 'contact_id')) {
                $table->dropConstrainedForeignId('contact_id');
            }

            if (Schema::hasColumn('conversations', 'channel_connection_id')) {
                $table->dropConstrainedForeignId('channel_connection_id');
            }

            if (Schema::hasColumn('conversations', 'ai_agent_id')) {
                $table->dropConstrainedForeignId('ai_agent_id');
            }

            if (Schema::hasColumn('conversations', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }

            // last_message_at is deliberately preserved here because older
            // live-agent versions may already contain it.
            $columns = [
                'external_thread_id',
                'subject',
                'priority',
                'unread_count',
                'first_response_at',
                'last_inbound_at',
                'reply_window_expires_at',
                'metadata',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('conversations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
