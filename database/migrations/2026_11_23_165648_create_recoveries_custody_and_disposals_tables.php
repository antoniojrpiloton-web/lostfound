<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_recoveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_report_id')->unique()->constrained('item_reports')->restrictOnDelete();
            $table->foreignId('claim_id')->unique()->constrained('claims')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('claimant_user_id')->constrained('users')->restrictOnDelete();
            $table->string('received_by_name', 150);
            $table->string('id_type', 60);
            $table->string('id_last4', 10);
            $table->timestamp('turnover_at');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_outcome', 30);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('item_custody_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_report_id')->constrained('item_reports')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignId('from_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('action', 20);
            $table->string('received_from', 150)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->useCurrent();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['item_report_id', 'recorded_at']);
        });

        DB::statement("ALTER TABLE item_custody_records ADD CONSTRAINT chk_item_custody_records_action CHECK (action IN ('received', 'transferred', 'released', 'disposed'))");

        Schema::create('item_disposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_report_id')->unique()->constrained('item_reports')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->date('eligible_on');
            $table->string('status', 20)->default('eligible');
            $table->string('method', 60)->nullable();
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('disposed_on')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['eligible_on', 'status']);
        });

        DB::statement("ALTER TABLE item_disposals ADD CONSTRAINT chk_item_disposals_status CHECK (status IN ('eligible', 'proposed', 'approved', 'completed', 'cancelled'))");

        Schema::create('item_disposal_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_disposal_id')->constrained('item_disposals')->cascadeOnDelete();
            $table->string('private_path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_disposal_documents');
        Schema::dropIfExists('item_disposals');
        Schema::dropIfExists('item_custody_records');
        Schema::dropIfExists('item_recoveries');
    }
};
