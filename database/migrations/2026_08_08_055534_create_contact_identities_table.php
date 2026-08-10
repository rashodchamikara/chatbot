<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_identities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->foreignId('channel_connection_id')
                ->nullable()
                ->constrained('channel_connections')
                ->nullOnDelete();

            $table->string('type', 30);
            $table->string('provider', 50);
            $table->string('external_id', 191);

            $table->string('display_name')->nullable();
            $table->string('username', 191)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();

            $table->json('metadata')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'contact_id'],
                'contact_identity_contact_lookup'
            );

            $table->index(
                ['tenant_id', 'type', 'provider', 'external_id'],
                'contact_identity_external_lookup'
            );

            $table->unique(
                ['channel_connection_id', 'external_id'],
                'contact_identity_connection_external_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_identities');
    }
};
