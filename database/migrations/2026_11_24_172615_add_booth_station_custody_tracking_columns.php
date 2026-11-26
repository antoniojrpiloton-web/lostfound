<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_reports', function (Blueprint $table): void {
            $table->string('custody_stage', 32)->nullable()->after('item_status')->index();
            $table->string('booth_location', 180)->nullable()->after('custody_stage');
            $table->timestamp('station_transferred_at')->nullable()->after('booth_location');
        });

        Schema::table('claims', function (Blueprint $table): void {
            $table->string('pickup_mode', 32)->nullable()->after('turnover_scheduled_at');
            $table->string('pickup_location', 180)->nullable()->after('pickup_mode');
        });
    }

    public function down(): void
    {
        Schema::table('item_reports', function (Blueprint $table): void {
            $table->dropIndex(['custody_stage']);
            $table->dropColumn(['custody_stage', 'booth_location', 'station_transferred_at']);
        });

        Schema::table('claims', function (Blueprint $table): void {
            $table->dropColumn(['pickup_mode', 'pickup_location']);
        });
    }
};
