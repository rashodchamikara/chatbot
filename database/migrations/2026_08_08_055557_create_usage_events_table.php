<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('ai_agent_id')
                ->nullable()
                ->constrained('ai_agents')
                ->nullOnDelete();

            $table->foreignId('channel_connection_id')
                ->nullable()
                ->constrained('channel_connections')
                ->nullOnDelete();

            $table->foreignId('conversation_id')
                ->nullable()
                ->constrained('conversations')
                ->nullOnDelete();

            $table->foreignId('message_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();

            $table->string('event_type', 50);
            $table->string('provider', 50)->nullable();

            $table->unsignedBigInteger('quantity')->default(1);
            $table->string('unit', 30)->default('event');

            $table->string('idempotency_key', 191)
                ->nullable()
                ->unique();

            $table->timestamp('occurred_at');
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'event_type', 'occurred_at'],
                'usage_tenant_event_lookup'
            );

            $table->index(
                ['channel_connection_id', 'occurred_at'],
                'usage_channel_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
