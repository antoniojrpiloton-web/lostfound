<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_embeddings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_image_id')->unique()->constrained('item_images')->cascadeOnDelete();
            $table->foreignId('item_report_id')->constrained('item_reports')->cascadeOnDelete();
            $table->string('model_version', 100);
            $table->unsignedSmallInteger('dimensions');
            $table->json('vector');
            $table->timestamps();

            $table->index(['item_report_id', 'model_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_embeddings');
    }
};
