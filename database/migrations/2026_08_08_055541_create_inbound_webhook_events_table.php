<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_webhook_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('channel_connection_id')
                ->nullable()
                ->constrained('channel_connections')
                ->nullOnDelete();

            $table->string('provider', 50);
            $table->string('event_type', 100)->nullable();
            $table->string('external_event_id', 191)->nullable();
            $table->string('payload_hash', 64);

            $table->longText('payload');
            $table->json('headers')->nullable();

            $table->string('status', 30)
                ->default('received');

            $table->unsignedInteger('attempts')->default(0);

            $table->timestamp('received_at');
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                ['provider', 'payload_hash'],
                'webhook_provider_payload_hash_unique'
            );

            $table->index(
                ['channel_connection_id', 'received_at'],
                'webhook_connection_received_lookup'
            );

            $table->index(
                ['provider', 'status', 'received_at'],
                'webhook_provider_status_lookup'
            );

            $table->index(
                ['status', 'received_at'],
                'webhook_processing_lookup'
            );

            $table->index(
                ['provider', 'external_event_id'],
                'webhook_external_event_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_webhook_events');
    }
};
