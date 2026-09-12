<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('channel_connections', function (Blueprint $table) {
        //     $table->id();

        //     $table->foreignId('tenant_id')
        //         ->constrained('tenants')
        //         ->cascadeOnDelete();

        //     $table->foreignId('ai_agent_id')
        //         ->constrained('ai_agents')
        //         ->cascadeOnDelete();

        //     // Only website-based channel connections need this field.
        //     $table->foreignId('website_id')
        //         ->nullable()
        //         ->constrained('websites')
        //         ->nullOnDelete();

        //     $table->string('type', 30)->index();
        //     $table->string('provider', 50)->index();
        //     $table->string('name');

        //     $table->string('status', 30)
        //         ->default('pending')
        //         ->index();

        //     $table->string('external_account_id', 191)->nullable();
        //     $table->string('external_sender_id', 191)->nullable();

        //     // Website chat does not require a webhook, so this may be null.
        //     // Providers that use webhooks should populate it with a ULID.
        //     $table->string('webhook_key', 26)
        //         ->nullable()
        //         ->unique();

        //     $table->longText('credentials')->nullable();
        //     $table->json('settings')->nullable();

        //     $table->timestamp('connected_at')->nullable();
        //     $table->timestamp('last_webhook_at')->nullable();
        //     $table->timestamp('last_health_check_at')->nullable();

        //     $table->text('last_error')->nullable();

        //     $table->timestamps();

        //     $table->index(
        //         ['tenant_id', 'ai_agent_id', 'type', 'status'],
        //         'channel_connection_lookup'
        //     );

        //     $table->unique(
        //         ['website_id', 'type'],
        //         'channel_website_type_unique'
        //     );

        //     $table->unique(
        //         ['type', 'provider', 'external_sender_id'],
        //         'channel_external_sender_unique'
        //     );
        // });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_connections');
    }
};
