<?PHP
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'messages',
            function (Blueprint $table) {
                $table->unique(
                    [
                        'channel_connection_id',
                        'external_message_id',
                    ],
                    'messages_connection_external_message_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'messages',
            function (Blueprint $table) {
                $table->dropUnique(
                    'messages_connection_external_message_unique'
                );
            }
        );
    }
};