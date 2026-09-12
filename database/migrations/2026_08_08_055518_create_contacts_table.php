<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('company')->nullable();

            $table->string('status', 30)
                ->default('active')
                ->index();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(
                ['tenant_id', 'email'],
                'contacts_tenant_email_index'
            );

            $table->index(
                ['tenant_id', 'phone'],
                'contacts_tenant_phone_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
