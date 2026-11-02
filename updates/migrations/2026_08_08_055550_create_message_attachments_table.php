<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('message_id')
                ->constrained('messages')
                ->cascadeOnDelete();

            $table->string('external_attachment_id', 191)->nullable();
            $table->string('type', 30)->default('file');
            $table->string('mime_type', 150)->nullable();
            $table->string('original_name')->nullable();
            $table->string('storage_disk', 50)->nullable();
            $table->string('storage_path', 1024)->nullable();
            $table->text('external_url')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum', 128)->nullable();

            $table->string('status', 30)->default('pending');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(
                ['message_id', 'type'],
                'message_attachment_type_lookup'
            );

            $table->index(
                ['external_attachment_id'],
                'message_external_attachment_lookup'
            );

            $table->index(
                ['status', 'created_at'],
                'message_attachment_status_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
