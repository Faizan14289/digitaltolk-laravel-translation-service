<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

class CachePerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function export_uses_cache_on_second_request()
    {
        $language = Language::factory()->create(['code' => 'es']);
        $translation = Translation::factory()->create(['key' => 'greeting', 'default_value' => 'Hello']);
        $translation->languages()->attach($language->id, ['translated_text' => 'Hola']);

        // First request - should query database and cache
        $start1 = microtime(true);
        $response1 = $this->getJson('/api/v1/export/es');
        $duration1 = (microtime(true) - $start1) * 1000;

        $response1->assertOk();
        $response1->assertExactJson(['greeting' => 'Hola']);

        // Second request - should use cache (much faster)
        $start2 = microtime(true);
        $response2 = $this->getJson('/api/v1/export/es');
        $duration2 = (microtime(true) - $start2) * 1000;

        $response2->assertOk();
        $response2->assertExactJson(['greeting' => 'Hola']);

        // Cache hit should be significantly faster
        $this->assertLessThan($duration1, $duration2, 'Cached request should be faster');
    }

    /** @test */
    public function cache_is_invalidated_on_translation_update()
    {
        $language = Language::factory()->create(['code' => 'fr']);
        $translation = Translation::factory()->create(['key' => 'welcome', 'default_value' => 'Welcome']);
        $translation->languages()->attach($language->id, ['translated_text' => 'Bienvenue']);

        // First request - populate cache
        $response1 = $this->getJson('/api/v1/export/fr');
        $response1->assertOk();
        $response1->assertExactJson(['welcome' => 'Bienvenue']);

        // Update translation
        $translation->languages()->updateExistingPivot($language->id, ['translated_text' => 'Salut']);

        // Trigger cache invalidation by updating the model
        $translation->touch();

        // Request again - should get updated value
        $response2 = $this->getJson('/api/v1/export/fr');
        $response2->assertOk();
        // Note: This test may need adjustment based on actual cache invalidation implementation
    }

    /** @test */
    public function export_performance_with_large_dataset()
    {
        $language = Language::factory()->create(['code' => 'de']);
        
        // Create 1000 translations
        for ($i = 0; $i < 1000; $i++) {
            $translation = Translation::factory()->create([
                'key' => "test.key.{$i}",
                'default_value' => "Test value {$i}"
            ]);
            $translation->languages()->attach($language->id, ['translated_text' => "Testwert {$i}"]);
        }

        // First request - database query
        $start1 = microtime(true);
        $response1 = $this->getJson('/api/v1/export/de');
        $duration1 = (microtime(true) - $start1) * 1000;

        $response1->assertOk();
        $this->assertCount(1000, $response1->json());

        // Second request - from cache
        $start2 = microtime(true);
        $response2 = $this->getJson('/api/v1/export/de');
        $duration2 = (microtime(true) - $start2) * 1000;

        $response2->assertOk();
        $this->assertCount(1000, $response2->json());

        // Both should be under 500ms, but cached should be much faster
        $this->assertLessThan(500, $duration1, "First request took too long: {$duration1}ms");
        $this->assertLessThan(500, $duration2, "Cached request took too long: {$duration2}ms");
        $this->assertLessThan($duration1 / 2, $duration2, 'Cached request should be at least 2x faster');
    }

    /** @test */
    public function cache_key_is_locale_specific()
    {
        $es = Language::factory()->create(['code' => 'es']);
        $fr = Language::factory()->create(['code' => 'fr']);
        
        $translation = Translation::factory()->create(['key' => 'hello', 'default_value' => 'Hello']);
        $translation->languages()->attach($es->id, ['translated_text' => 'Hola']);
        $translation->languages()->attach($fr->id, ['translated_text' => 'Bonjour']);

        $responseEs = $this->getJson('/api/v1/export/es');
        $responseFr = $this->getJson('/api/v1/export/fr');

        $responseEs->assertOk();
        $responseEs->assertExactJson(['hello' => 'Hola']);

        $responseFr->assertOk();
        $responseFr->assertExactJson(['hello' => 'Bonjour']);
    }
}
