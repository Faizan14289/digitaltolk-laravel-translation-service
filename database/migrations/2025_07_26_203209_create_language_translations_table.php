<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('language_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->onDelete('cascade');
            $table->foreignId('translation_id')->constrained()->onDelete('cascade');
            $table->text('translated_text');
            $table->unique(['language_id', 'translation_id']); // Ensure uniqueness
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_translations');
    }
};
