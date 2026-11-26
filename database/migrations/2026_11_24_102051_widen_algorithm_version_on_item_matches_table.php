<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_matches', function (Blueprint $table): void {
            $table->string('algorithm_version', 100)->default('meta-v1')->change();
        });
    }

    public function down(): void
    {
        Schema::table('item_matches', function (Blueprint $table): void {
            $table->string('algorithm_version', 32)->default('meta-v1')->change();
        });
    }
};
