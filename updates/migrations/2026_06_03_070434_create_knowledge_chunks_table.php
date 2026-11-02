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
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('knowledge_page_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('website_id')
                ->constrained()
                ->onDelete('cascade');

            $table->text('chunk_text');

            $table->json('embedding')->nullable();

            $table->integer('chunk_index')->default(0);

            $table->timestamps();

            $table->index('website_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
