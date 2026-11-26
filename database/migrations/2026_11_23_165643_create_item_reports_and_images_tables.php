<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_no', 30)->unique();
            $table->string('type', 10);
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handling_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('custody_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('item_name', 150);
            $table->text('description');
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('color', 60)->nullable();
            $table->text('distinguishing_features')->nullable();
            $table->date('occurred_date');
            $table->time('occurred_time')->nullable();
            $table->foreignId('barangay_id')->constrained('barangays')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('location_details', 255)->nullable();
            $table->boolean('reward_offered')->default(false);
            $table->string('reward_details', 255)->nullable();
            $table->json('contact_preferences')->nullable();
            $table->text('contact_phone_enc')->nullable();
            $table->string('finder_name', 150)->nullable();
            $table->text('finder_contact_enc')->nullable();
            $table->text('remarks')->nullable();
            $table->string('verification_status', 20)->default('pending');
            $table->string('item_status', 25)->default('open');
            $table->boolean('is_confidential')->default(false);
            $table->boolean('is_public')->default(false);
            $table->string('photo_exception_reason', 255)->nullable();
            $table->foreignId('photo_exception_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('source_locale', 10)->default('en');
            $table->text('search_text')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_public', 'type', 'item_status', 'occurred_date'], 'item_reports_public_filter_idx');
            $table->index(['handling_organization_id', 'verification_status'], 'item_reports_handling_verif_idx');
            $table->index(['custody_organization_id', 'item_status'], 'item_reports_custody_status_idx');
            $table->index(['category_id', 'barangay_id'], 'item_reports_cat_brgy_idx');
            $table->index(['reporter_user_id', 'type'], 'item_reports_reporter_type_idx');
            $table->fullText('search_text', 'item_reports_search_text_fulltext');
        });

        DB::statement("ALTER TABLE item_reports ADD CONSTRAINT chk_item_reports_type CHECK (type IN ('lost', 'found'))");
        DB::statement("ALTER TABLE item_reports ADD CONSTRAINT chk_item_reports_verification_status CHECK (verification_status IN ('pending', 'verified', 'needs_revision', 'rejected', 'withdrawn'))");
        DB::statement("ALTER TABLE item_reports ADD CONSTRAINT chk_item_reports_item_status CHECK (item_status IN ('open', 'resolved', 'closed', 'awaiting_turnover', 'in_custody', 'claim_pending', 'returned', 'disposal_pending', 'disposed'))");

        Schema::create('item_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_report_id')->constrained('item_reports')->cascadeOnDelete();
            $table->string('private_path', 500);
            $table->string('public_path', 500)->nullable();
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('sha256', 64);
            $table->string('validation_status', 20)->default('review');
            $table->json('validation_result')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['item_report_id', 'is_primary']);
            $table->index('validation_status');
        });

        DB::statement("ALTER TABLE item_images ADD CONSTRAINT chk_item_images_validation_status CHECK (validation_status IN ('passed', 'review', 'rejected', 'overridden'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('item_images');
        Schema::dropIfExists('item_reports');
    }
};
