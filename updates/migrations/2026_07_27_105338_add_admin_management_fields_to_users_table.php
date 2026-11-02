<?PHP 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 50)
                    ->default('agent')
                    ->index()
                    ->after('password');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status', 30)
                    ->default('active')
                    ->index()
                    ->after('role');
            }

            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')
                    ->nullable()
                    ->after('status');
            }

            if (!Schema::hasColumn('users', 'suspended_by')) {
                $table->foreignId('suspended_by')
                    ->nullable()
                    ->after('suspended_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')
                    ->nullable()
                    ->after('suspended_by');
            }

            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'suspended_by')) {
                $table->dropConstrainedForeignId('suspended_by');
            }

            if (Schema::hasColumn('users', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }

            $table->dropColumn([
                'role',
                'status',
                'suspended_at',
                'last_login_at',
            ]);

            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};