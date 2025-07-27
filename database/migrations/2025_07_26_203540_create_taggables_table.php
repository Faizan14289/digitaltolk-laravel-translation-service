<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('taggable_id');
            $table->string('taggable_type');
            $table->index(['taggable_id', 'taggable_type']); // Index for performance
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
