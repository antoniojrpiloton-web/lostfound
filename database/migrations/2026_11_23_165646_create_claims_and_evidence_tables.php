<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_no', 30)->unique();
            $table->foreignId('item_report_id')->constrained('item_reports')->restrictOnDelete();
            $table->foreignId('lost_report_id')->nullable()->constrained('item_reports')->nullOnDelete();
            $table->foreignId('claimant_user_id')->constrained('users')->restrictOnDelete();
            $table->text('ownership_description');
            $table->text('distinguishing_details')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->unsignedBigInteger('completed_item_key')->nullable()->storedAs("IF(status = 'completed', item_report_id, NULL)")->unique('claims_completed_item_unique');
            $table->timestamp('turnover_scheduled_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_remarks')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index(['item_report_id', 'status']);
            $table->index(['claimant_user_id', 'status']);
        });

        DB::statement("ALTER TABLE claims ADD CONSTRAINT chk_claims_status CHECK (status IN ('submitted', 'under_review', 'info_requested', 'approved', 'rejected', 'completed', 'cancelled', 'withdrawn'))");

        Schema::create('claim_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            $table->string('private_path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->char('sha256', 64);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('claim_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            $table->string('action', 50);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['claim_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_histories');
        Schema::dropIfExists('claim_evidence');
        Schema::dropIfExists('claims');
    }
};
