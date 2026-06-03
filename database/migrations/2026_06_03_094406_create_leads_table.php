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
       Schema::create('leads', function (Blueprint $table) {

            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('website_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('conversation_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name')->nullable();

            $table->string('email')->nullable();

            $table->string('phone')->nullable();

            $table->string('country')->nullable();

            $table->string('preferred_contact_time')->nullable();

            $table->string('product_interest')->nullable();

            $table->integer('lead_score')
                ->default(0);

            $table->string('status')
                ->default('new');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
