<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('status', 30)
                ->default('active')
                ->index();

            $table->longText('instructions')->nullable();
            $table->string('default_language', 20)->default('en');

            $table->json('model_settings')->nullable();
            $table->json('handover_settings')->nullable();
            $table->json('business_hours')->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'status'],
                'ai_agents_tenant_status_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agents');
    }
};
