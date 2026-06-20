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
        Schema::create('knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('website_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * pdf, document, spreadsheet, image, text
             */
            $table->string('source_type', 30);

            $table->string('name');
            $table->string('original_name')->nullable();

            $table->string('storage_disk', 50)
                ->default('s3');

            $table->string('storage_path', 2048)
                ->nullable();

            $table->string('extracted_path', 2048)
                ->nullable();

            $table->string('mime_type', 150)
                ->nullable();

            $table->string('extension', 20)
                ->nullable();

            $table->unsignedBigInteger('size_bytes')
                ->default(0);

            $table->char('checksum_sha256', 64)
                ->nullable();

            
            $table->string('status', 30)
                ->default('queued');

            $table->boolean('is_enabled')
                ->default(true);

            /*
             * Used for safe file replacement and re-indexing.
             */
            $table->unsignedInteger('processing_version')
                ->default(1);

            $table->unsignedInteger('active_version')
                ->default(0);

            $table->unsignedInteger('page_count')
                ->nullable();

            $table->unsignedInteger('chunk_count')
                ->default(0);

            $table->unsignedBigInteger('extracted_characters')
                ->default(0);

            $table->unsignedBigInteger('embedding_tokens')
                ->default(0);

            /*
             * Higher priority sources can be preferred.
             */
            $table->smallInteger('priority')
                ->default(0);

            /*
             * Useful for temporary offers and price lists.
             */
            $table->timestamp('valid_from')
                ->nullable();

            $table->timestamp('valid_until')
                ->nullable();

            $table->string('external_job_id')
                ->nullable();

            $table->text('processing_error')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            $table->timestamp('processed_at')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'tenant_id',
                'website_id',
                'status',
            ]);

            $table->index([
                'website_id',
                'is_enabled',
            ]);

            $table->index([
                'website_id',
                'checksum_sha256',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_sources');
    }
};
