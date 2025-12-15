<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            // Add index on key column for faster lookups and searches
            $table->index('key', 'translations_key_index');
        });

        Schema::table('languages', function (Blueprint $table) {
            // Add index on code column for faster locale lookups in export
            $table->index('code', 'languages_code_index');
        });

        Schema::table('tags', function (Blueprint $table) {
            // Add index on name column for faster tag filtering
            $table->index('name', 'tags_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('translations', function (Blueprint $table) {
            $table->dropIndex('translations_key_index');
        });

        Schema::table('languages', function (Blueprint $table) {
            $table->dropIndex('languages_code_index');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex('tags_name_index');
        });
    }
};
