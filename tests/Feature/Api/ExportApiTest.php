<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Translation;
use App\Models\Language;

class ExportApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_exports_translations_for_a_locale()
    {
        $language = Language::factory()->create(['code' => 'es']);
        $translation = Translation::factory()->create(['key' => 'greeting', 'default_value' => 'Hello']);
        $translation->languages()->attach($language->id, ['translated_text' => 'Hola']);

        // Performance test example
        // $start = microtime(true);
        $response = $this->getJson('/api/v1/export/es');
        // $end = microtime(true);
        // $this->assertLessThan(0.5, $end - $start, 'Export response time exceeded 500ms');

        $response->assertStatus(200);
        $response->assertExactJson(['greeting' => 'Hola']);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_locale()
    {
        $response = $this->getJson('/api/v1/export/xx');
        $response->assertStatus(404);
    }
}
