<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Language;
use App\Models\Translation;

// For seeding data if needed

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function export_translations_performance()
    {
        // Pre-populate the database with some data for the test
        $language = Language::factory()->create(['code' => 'es']);
        $translation = Translation::factory()->create(['key' => 'greeting', 'default_value' => 'Hello']);
        $translation->languages()->attach($language->id, ['translated_text' => 'Hola']);


        $start = microtime(true);

        // Use the CORRECT endpoint URL as defined in routes/api.php
        $response = $this->getJson('/api/v1/export/es');

        // ms
        $duration = (microtime(true) - $start) * 1000;
        $response->assertOk(); // Assert status 200
        $this->assertLessThan(500, $duration, "Export API took too long: {$duration}ms");
    }
}
