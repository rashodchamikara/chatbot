<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('knowledge_chunks', function (Blueprint $table) {
            $table->unsignedBigInteger('knowledge_page_id')
                ->nullable()
                ->change();
        });

        Schema::table('knowledge_chunks', function (Blueprint $table) {
            $table->foreignId('knowledge_source_id')
                ->nullable()
                ->after('knowledge_page_id')
                ->constrained('knowledge_sources')
                ->cascadeOnDelete();

            $table->unsignedInteger('processing_version')
                ->default(1)
                ->after('chunk_index');

            $table->unsignedInteger('token_count')
                ->nullable()
                ->after('processing_version');

            $table->unsignedInteger('page_number')
                ->nullable()
                ->after('token_count');

            $table->string('section_title')
                ->nullable()
                ->after('page_number');

            $table->char('content_hash', 64)
                ->nullable()
                ->after('section_title');

            $table->json('metadata')
                ->nullable()
                ->after('content_hash');

            $table->boolean('is_active')
                ->default(true)
                ->after('metadata');

            $table->timestamp('embedded_at')
                ->nullable()
                ->after('is_active');

            $table->index(
                [
                    'knowledge_source_id',
                    'processing_version',
                    'is_active',
                ],
                'knowledge_chunks_source_version_active_idx'
            );

            $table->index(
                [
                    'website_id',
                    'is_active',
                ],
                'knowledge_chunks_website_active_idx'
            );
        });

        /*
         * Existing crawler chunks should remain active.
         */
        DB::table('knowledge_chunks')
            ->whereNull('is_active')
            ->update([
                'is_active' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_chunks', function (Blueprint $table) {
            $table->dropForeign([
                'knowledge_source_id',
            ]);

            $table->dropIndex(
                'knowledge_chunks_source_version_active_idx'
            );

            $table->dropIndex(
                'knowledge_chunks_website_active_idx'
            );

            $table->dropColumn([
                'knowledge_source_id',
                'processing_version',
                'token_count',
                'page_number',
                'section_title',
                'content_hash',
                'metadata',
                'is_active',
                'embedded_at',
            ]);
        });
    }
};
