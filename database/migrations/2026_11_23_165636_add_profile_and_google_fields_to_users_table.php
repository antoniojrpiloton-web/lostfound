<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('google_id', 191)->nullable()->unique()->after('id');
            $table->string('avatar_url', 500)->nullable()->after('email');
            $table->string('locale', 10)->default('en')->after('avatar_url');
            $table->string('status', 20)->default('active')->after('locale');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('password')->nullable()->change();
            $table->index('status');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_status CHECK (status IN ('active', 'suspended'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CHECK chk_users_status');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'avatar_url', 'locale', 'status', 'last_login_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
