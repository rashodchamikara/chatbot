<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table): void {
            if (!Schema::hasColumn('ai_agents', 'is_default')) {
                $table->boolean('is_default')
                    ->default(false)
                    ->after('status')
                    ->index();
            }
        });

        Schema::table('knowledge_sources', function (Blueprint $table): void {
            if (!Schema::hasColumn('knowledge_sources', 'ai_agent_id')) {
                $table->foreignId('ai_agent_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('ai_agents')
                    ->nullOnDelete();
            }
        });

        Schema::table('knowledge_pages', function (Blueprint $table): void {
            if (!Schema::hasColumn('knowledge_pages', 'tenant_id')) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('knowledge_pages', 'ai_agent_id')) {
                $table->foreignId('ai_agent_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('ai_agents')
                    ->nullOnDelete();
            }
        });

        Schema::table('knowledge_chunks', function (Blueprint $table): void {
            if (!Schema::hasColumn('knowledge_chunks', 'tenant_id')) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('knowledge_chunks', 'ai_agent_id')) {
                $table->foreignId('ai_agent_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('ai_agents')
                    ->nullOnDelete();
            }
        });

        /*
         * A channel-first workspace must allow knowledge and leads without a website.
         * Laravel 10+ / 11+ / 12 can modify these columns natively with change().
         */
        Schema::table('knowledge_sources', function (Blueprint $table): void {
            $table->unsignedBigInteger('website_id')->nullable()->change();
        });

        Schema::table('knowledge_pages', function (Blueprint $table): void {
            $table->unsignedBigInteger('website_id')->nullable()->change();
        });

        Schema::table('knowledge_chunks', function (Blueprint $table): void {
            $table->unsignedBigInteger('website_id')->nullable()->change();
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->unsignedBigInteger('website_id')->nullable()->change();
        });

        Schema::table('knowledge_chunks', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'ai_agent_id', 'is_active'],
                'knowledge_chunks_agent_active_idx'
            );
        });

        Schema::table('knowledge_pages', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'ai_agent_id', 'is_active'],
                'knowledge_pages_agent_active_idx'
            );
        });

        Schema::table('knowledge_sources', function (Blueprint $table): void {
            $table->index(
                ['tenant_id', 'ai_agent_id', 'is_enabled'],
                'knowledge_sources_agent_enabled_idx'
            );
        });

        /*
         * Pick one existing agent per tenant as the default agent.
         */
        DB::statement(<<<'SQL'
            UPDATE ai_agents a
            INNER JOIN (
                SELECT tenant_id, MIN(id) AS agent_id
                FROM ai_agents
                GROUP BY tenant_id
            ) defaults
                ON defaults.agent_id = a.id
            SET a.is_default = 1
        SQL);

        /*
         * Backfill existing website-owned knowledge to the website's AI agent.
         */
        DB::statement(<<<'SQL'
            UPDATE knowledge_pages kp
            INNER JOIN websites w ON w.id = kp.website_id
            SET
                kp.tenant_id = w.tenant_id,
                kp.ai_agent_id = w.ai_agent_id
            WHERE kp.website_id IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE knowledge_sources ks
            INNER JOIN websites w ON w.id = ks.website_id
            SET ks.ai_agent_id = w.ai_agent_id
            WHERE ks.website_id IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE knowledge_chunks kc
            INNER JOIN knowledge_pages kp ON kp.id = kc.knowledge_page_id
            SET
                kc.tenant_id = kp.tenant_id,
                kc.ai_agent_id = kp.ai_agent_id
            WHERE kc.knowledge_page_id IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE knowledge_chunks kc
            INNER JOIN knowledge_sources ks ON ks.id = kc.knowledge_source_id
            SET
                kc.tenant_id = ks.tenant_id,
                kc.ai_agent_id = ks.ai_agent_id
            WHERE kc.knowledge_source_id IS NOT NULL
        SQL);

        /*
         * Fallback for any legacy chunk that has only website_id populated.
         */
        DB::statement(<<<'SQL'
            UPDATE knowledge_chunks kc
            INNER JOIN websites w ON w.id = kc.website_id
            SET
                kc.tenant_id = COALESCE(kc.tenant_id, w.tenant_id),
                kc.ai_agent_id = COALESCE(kc.ai_agent_id, w.ai_agent_id)
            WHERE kc.website_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('knowledge_chunks', function (Blueprint $table): void {
            $table->dropIndex('knowledge_chunks_agent_active_idx');
        });

        Schema::table('knowledge_pages', function (Blueprint $table): void {
            $table->dropIndex('knowledge_pages_agent_active_idx');
        });

        Schema::table('knowledge_sources', function (Blueprint $table): void {
            $table->dropIndex('knowledge_sources_agent_enabled_idx');
        });

        /*
         * Deliberately do not make website_id NOT NULL again. A rollback after
         * WhatsApp-only usage could otherwise destroy valid channel-first data.
         */
        Schema::table('knowledge_chunks', function (Blueprint $table): void {
            if (Schema::hasColumn('knowledge_chunks', 'ai_agent_id')) {
                $table->dropConstrainedForeignId('ai_agent_id');
            }

            if (Schema::hasColumn('knowledge_chunks', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }
        });

        Schema::table('knowledge_pages', function (Blueprint $table): void {
            if (Schema::hasColumn('knowledge_pages', 'ai_agent_id')) {
                $table->dropConstrainedForeignId('ai_agent_id');
            }

            if (Schema::hasColumn('knowledge_pages', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }
        });

        Schema::table('knowledge_sources', function (Blueprint $table): void {
            if (Schema::hasColumn('knowledge_sources', 'ai_agent_id')) {
                $table->dropConstrainedForeignId('ai_agent_id');
            }
        });

        Schema::table('ai_agents', function (Blueprint $table): void {
            if (Schema::hasColumn('ai_agents', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
