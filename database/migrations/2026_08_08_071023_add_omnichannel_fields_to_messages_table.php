<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'channel_connection_id')) {
                $table->foreignId('channel_connection_id')
                    ->nullable()
                    ->after('conversation_id')
                    ->constrained('channel_connections')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('messages', 'sender_user_id')) {
                $table->foreignId('sender_user_id')
                    ->nullable()
                    ->after('channel_connection_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('messages', 'external_message_id')) {
                $table->string('external_message_id', 191)
                    ->nullable()
                    ->after('sender_user_id');
            }

            if (!Schema::hasColumn('messages', 'external_reply_to_id')) {
                $table->string('external_reply_to_id', 191)
                    ->nullable()
                    ->after('external_message_id');
            }

            if (!Schema::hasColumn('messages', 'direction')) {
                $table->string('direction', 20)
                    ->nullable()
                    ->after('external_reply_to_id');
            }

            if (!Schema::hasColumn('messages', 'sender_type')) {
                $table->string('sender_type', 20)
                    ->nullable()
                    ->after('direction');
            }

            if (!Schema::hasColumn('messages', 'message_type')) {
                $table->string('message_type', 30)
                    ->default('text')
                    ->after('sender_type');
            }

            if (!Schema::hasColumn('messages', 'payload')) {
                $table->json('payload')
                    ->nullable()
                    ->after('message_type');
            }

            if (!Schema::hasColumn('messages', 'status')) {
                $table->string('status', 30)
                    ->default('pending')
                    ->after('payload');
            }

            if (!Schema::hasColumn('messages', 'provider_status')) {
                $table->string('provider_status', 50)
                    ->nullable()
                    ->after('status');
            }

            if (!Schema::hasColumn('messages', 'error_code')) {
                $table->string('error_code', 100)
                    ->nullable()
                    ->after('provider_status');
            }

            if (!Schema::hasColumn('messages', 'error_message')) {
                $table->text('error_message')
                    ->nullable()
                    ->after('error_code');
            }

            if (!Schema::hasColumn('messages', 'is_ai_generated')) {
                $table->boolean('is_ai_generated')
                    ->default(false)
                    ->after('error_message');
            }

            if (!Schema::hasColumn('messages', 'prompt_tokens')) {
                $table->unsignedInteger('prompt_tokens')
                    ->nullable()
                    ->after('is_ai_generated');
            }

            if (!Schema::hasColumn('messages', 'completion_tokens')) {
                $table->unsignedInteger('completion_tokens')
                    ->nullable()
                    ->after('prompt_tokens');
            }

            if (!Schema::hasColumn('messages', 'provider_created_at')) {
                $table->timestamp('provider_created_at')
                    ->nullable()
                    ->after('completion_tokens');
            }

            if (!Schema::hasColumn('messages', 'sent_at')) {
                $table->timestamp('sent_at')
                    ->nullable()
                    ->after('provider_created_at');
            }

            if (!Schema::hasColumn('messages', 'delivered_at')) {
                $table->timestamp('delivered_at')
                    ->nullable()
                    ->after('sent_at');
            }

            if (!Schema::hasColumn('messages', 'read_at')) {
                $table->timestamp('read_at')
                    ->nullable()
                    ->after('delivered_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'sender_user_id')) {
                $table->dropConstrainedForeignId('sender_user_id');
            }

            if (Schema::hasColumn('messages', 'channel_connection_id')) {
                $table->dropConstrainedForeignId('channel_connection_id');
            }

            // status is deliberately preserved because an older application
            // version may already have a messages.status column.
            $columns = [
                'external_message_id',
                'external_reply_to_id',
                'direction',
                'sender_type',
                'message_type',
                'payload',
                'provider_status',
                'error_code',
                'error_message',
                'is_ai_generated',
                'prompt_tokens',
                'completion_tokens',
                'provider_created_at',
                'sent_at',
                'delivered_at',
                'read_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
