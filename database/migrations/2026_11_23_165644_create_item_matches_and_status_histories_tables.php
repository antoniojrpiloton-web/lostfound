<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_report_id')->constrained('item_reports')->cascadeOnDelete();
            $table->string('dimension', 20);
            $table->string('from_status', 25)->nullable();
            $table->string('to_status', 25);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['item_report_id', 'created_at']);
            $table->index(['related_type', 'related_id']);
        });

        DB::statement("ALTER TABLE report_status_histories ADD CONSTRAINT chk_report_status_histories_dimension CHECK (dimension IN ('verification', 'item'))");

        Schema::create('item_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lost_report_id')->constrained('item_reports')->cascadeOnDelete();
            $table->foreignId('found_report_id')->constrained('item_reports')->cascadeOnDelete();
            $table->decimal('score', 5, 4);
            $table->json('factors');
            $table->string('algorithm_version', 30);
            $table->string('status', 20)->default('suggested');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();
            $table->timestamps();

            $table->unique(['lost_report_id', 'found_report_id'], 'item_matches_pair_unique');
            $table->index(['status', 'score']);
        });

        DB::statement("ALTER TABLE item_matches ADD CONSTRAINT chk_item_matches_status CHECK (status IN ('suggested', 'possible', 'dismissed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('item_matches');
        Schema::dropIfExists('report_status_histories');
    }
};
