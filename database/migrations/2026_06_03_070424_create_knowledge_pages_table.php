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
        Schema::create('knowledge_pages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('website_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('url', 1000);
            $table->string('title')->nullable();
            $table->string('type')->default('page'); 
            // page, product, blog, whitepaper, faq

            $table->longText('content')->nullable();
            $table->string('content_hash')->nullable();

            $table->boolean('is_indexed')->default(false);

            $table->timestamps();

            $table->index('website_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_pages');
    }
};
