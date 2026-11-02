<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void
    {
        foreach (['channel_connections', 'conversations', 'messages', 'inbound_webhook_events'] as $table) {
            if (!Schema::hasTable($table)) throw new RuntimeException("Existing installation required: missing {$table}.");
        }
        $indexes = Schema::getIndexes('inbound_webhook_events');
        $dedup = collect($indexes)->contains(fn ($i) => $i['unique'] && count($i['columns']) === 2
            && !array_diff(['provider', 'payload_hash'], $i['columns']));
        if (!$dedup) throw new RuntimeException('Add the unique webhook provider/payload_hash index before installing Messenger.');
        // This release must not silently import an unknown, previously installed Facebook connector.
        if (DB::table('channel_connections')->where('type', 'facebook')->exists()) {
            throw new RuntimeException('Existing Facebook connections found. Reconcile their Page ownership before this migration.');
        }
        Schema::create('messenger_oauth_grants', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->unsignedBigInteger('user_id')->index();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->unsignedBigInteger('ai_agent_id');
            $t->string('state_hash', 64)->unique();
            $t->string('session_hash', 64);
            $t->longText('pages')->nullable();
            $t->string('status', 20)->default('pending');
            $t->timestamp('expires_at')->index();
            $t->timestamps();
        });
        Schema::create('messenger_page_claims', function (Blueprint $t) {
            $t->string('page_id', 64)->primary();
            $t->foreignId('channel_connection_id')->unique()->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('tenant_id')->index();
        });
        // Provider event time is separate from local webhook processing time.
        Schema::table('conversations', function (Blueprint $t) {
            $t->timestamp('messenger_last_user_message_at')->nullable();
            $t->unsignedBigInteger('website_id')->nullable()->change();
        });
    }
    public function down(): void
    {
        if (DB::table('channel_connections')->where('type', 'facebook')->exists()) {
            throw new RuntimeException('Remove/archive Facebook connections before rolling back Messenger schema.');
        }
        Schema::dropIfExists('messenger_page_claims');
        Schema::dropIfExists('messenger_oauth_grants');
        Schema::table('conversations', fn (Blueprint $t) => $t->dropColumn('messenger_last_user_message_at'));
        // website_id remains nullable: other external channels also require this.
    }
};
