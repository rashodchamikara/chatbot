<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * The application now retrieves knowledge by tenant + AI agent.
         * Older installations only have website_id on knowledge_chunks/pages,
         * so add the missing channel-first ownership columns safely.
         */

        if (!Schema::hasColumn('knowledge_pages', 'tenant_id')) {
            Schema::table('knowledge_pages', function (Blueprint $table): void {
                $table->unsignedBigInteger('tenant_id')
                    ->nullable()
                    ->after('id');
            });
        }

        if (!Schema::hasColumn('knowledge_pages', 'ai_agent_id')) {
            Schema::table('knowledge_pages', function (Blueprint $table): void {
                $table->unsignedBigInteger('ai_agent_id')
                    ->nullable()
                    ->after('tenant_id');
            });
        }

        if (!Schema::hasColumn('knowledge_sources', 'ai_agent_id')) {
            Schema::table('knowledge_sources', function (Blueprint $table): void {
                $table->unsignedBigInteger('ai_agent_id')
                    ->nullable()
                    ->after('tenant_id');
            });
        }

        if (!Schema::hasColumn('knowledge_chunks', 'tenant_id')) {
            Schema::table('knowledge_chunks', function (Blueprint $table): void {
                $table->unsignedBigInteger('tenant_id')
                    ->nullable()
                    ->after('id');
            });
        }

        if (!Schema::hasColumn('knowledge_chunks', 'ai_agent_id')) {
            Schema::table('knowledge_chunks', function (Blueprint $table): void {
                $table->unsignedBigInteger('ai_agent_id')
                    ->nullable()
                    ->after('tenant_id');
            });
        }

        /*
         * Backfill ownership for existing crawled website pages.
         */
        DB::statement(<<<'SQL'
            UPDATE knowledge_pages kp
            INNER JOIN websites w
                ON w.id = kp.website_id
            SET
                kp.tenant_id = COALESCE(kp.tenant_id, w.tenant_id),
                kp.ai_agent_id = COALESCE(kp.ai_agent_id, w.ai_agent_id)
            WHERE kp.website_id IS NOT NULL
        SQL);

        /*
         * Existing uploaded sources already have tenant_id in the legacy
         * schema; attach them to the same AI agent as their website.
         */
        DB::statement(<<<'SQL'
            UPDATE knowledge_sources ks
            INNER JOIN websites w
                ON w.id = ks.website_id
            SET
                ks.ai_agent_id = COALESCE(ks.ai_agent_id, w.ai_agent_id)
            WHERE ks.website_id IS NOT NULL
        SQL);

        /*
         * First backfill chunks from their owning knowledge page.
         */
        DB::statement(<<<'SQL'
            UPDATE knowledge_chunks kc
            INNER JOIN knowledge_pages kp
                ON kp.id = kc.knowledge_page_id
            SET
                kc.tenant_id = COALESCE(kc.tenant_id, kp.tenant_id),
                kc.ai_agent_id = COALESCE(kc.ai_agent_id, kp.ai_agent_id)
            WHERE kc.knowledge_page_id IS NOT NULL
        SQL);

        /*
         * Then backfill uploaded-document chunks from knowledge_sources.
         */
        DB::statement(<<<'SQL'
            UPDATE knowledge_chunks kc
            INNER JOIN knowledge_sources ks
                ON ks.id = kc.knowledge_source_id
            SET
                kc.tenant_id = COALESCE(kc.tenant_id, ks.tenant_id),
                kc.ai_agent_id = COALESCE(kc.ai_agent_id, ks.ai_agent_id)
            WHERE kc.knowledge_source_id IS NOT NULL
        SQL);

        /*
         * Final legacy fallback: old crawled chunks may only have website_id.
         */
        DB::statement(<<<'SQL'
            UPDATE knowledge_chunks kc
            INNER JOIN websites w
                ON w.id = kc.website_id
            SET
                kc.tenant_id = COALESCE(kc.tenant_id, w.tenant_id),
                kc.ai_agent_id = COALESCE(kc.ai_agent_id, w.ai_agent_id)
            WHERE kc.website_id IS NOT NULL
        SQL);

        /*
         * Index the agent-scoped lookup used by KnowledgeRetriever.
         * Use a deterministic name and create only when it does not exist.
         */
        $database = DB::getDatabaseName();

        $chunkIndexExists = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'knowledge_chunks')
            ->where('index_name', 'knowledge_chunks_agent_active_idx')
            ->exists();

        if (!$chunkIndexExists) {
            Schema::table('knowledge_chunks', function (Blueprint $table): void {
                $table->index(
                    ['tenant_id', 'ai_agent_id', 'is_active'],
                    'knowledge_chunks_agent_active_idx'
                );
            });
        }

        $pageIndexExists = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'knowledge_pages')
            ->where('index_name', 'knowledge_pages_agent_active_idx')
            ->exists();

        if (!$pageIndexExists) {
            Schema::table('knowledge_pages', function (Blueprint $table): void {
                $table->index(
                    ['tenant_id', 'ai_agent_id', 'is_active'],
                    'knowledge_pages_agent_active_idx'
                );
            });
        }

        $sourceIndexExists = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'knowledge_sources')
            ->where('index_name', 'knowledge_sources_agent_enabled_idx')
            ->exists();

        if (!$sourceIndexExists) {
            Schema::table('knowledge_sources', function (Blueprint $table): void {
                $table->index(
                    ['tenant_id', 'ai_agent_id', 'is_enabled'],
                    'knowledge_sources_agent_enabled_idx'
                );
            });
        }
    }

    public function down(): void
    {
        /*
         * Intentionally left non-destructive.
         *
         * Once WhatsApp/manual knowledge starts using tenant_id + ai_agent_id,
         * dropping these columns on rollback could orphan valid knowledge.
         */
    }
};
