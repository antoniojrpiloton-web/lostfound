<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('translatable_type', 120);
            $table->unsignedBigInteger('translatable_id');
            $table->string('field', 60);
            $table->string('locale', 10);
            $table->char('source_hash', 64);
            $table->text('translated_text');
            $table->string('provider', 40)->nullable();
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'field', 'locale'], 'translations_unique_target');
            $table->index(['translatable_type', 'translatable_id'], 'translations_morph_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
