<?php

namespace Tests\Unit\Resources;

use Tests\TestCase;
use App\Models\Translation;
use App\Models\Language;
use App\Models\Tag;
use App\Http\Resources\TranslationResource;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TranslationResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_transforms_translation_correctly()
    {
        $translation = Translation::factory()->create([
            'key' => 'test.key',
            'default_value' => 'Test Value'
        ]);
        
        $language = Language::factory()->create(['code' => 'es']);
        $tag = Tag::factory()->create(['name' => 'mobile']);
        
        $translation->languages()->attach($language->id, ['translated_text' => 'Valor de Prueba']);
        $translation->tags()->attach($tag);
        
        $translation = $translation->fresh(['languages', 'tags']);
        
        $resource = new TranslationResource($translation);
        $array = $resource->toArray(request());
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('key', $array);
        $this->assertArrayHasKey('default_value', $array);
        $this->assertArrayHasKey('translations', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        
        $this->assertEquals('test.key', $array['key']);
        $this->assertEquals('Test Value', $array['default_value']);
        $this->assertEquals(['es' => 'Valor de Prueba'], $array['translations']->toArray());
        $this->assertEquals(['mobile'], $array['tags']->toArray());
    }

    /** @test */
    public function it_handles_translation_without_languages()
    {
        $translation = Translation::factory()->create();
        $translation = $translation->fresh(['languages', 'tags']);
        
        $resource = new TranslationResource($translation);
        $array = $resource->toArray(request());
        
        $this->assertEmpty($array['translations']);
    }

    /** @test */
    public function it_handles_translation_without_tags()
    {
        $translation = Translation::factory()->create();
        $translation = $translation->fresh(['languages', 'tags']);
        
        $resource = new TranslationResource($translation);
        $array = $resource->toArray(request());
        
        $this->assertEmpty($array['tags']);
    }

    /** @test */
    public function it_formats_multiple_languages_correctly()
    {
        $translation = Translation::factory()->create();
        
        $es = Language::factory()->create(['code' => 'es']);
        $fr = Language::factory()->create(['code' => 'fr']);
        $de = Language::factory()->create(['code' => 'de']);
        
        $translation->languages()->attach($es->id, ['translated_text' => 'Español']);
        $translation->languages()->attach($fr->id, ['translated_text' => 'Français']);
        $translation->languages()->attach($de->id, ['translated_text' => 'Deutsch']);
        
        $translation = $translation->fresh(['languages', 'tags']);
        
        $resource = new TranslationResource($translation);
        $array = $resource->toArray(request());
        
        $this->assertCount(3, $array['translations']);
        $this->assertEquals('Español', $array['translations']['es']);
        $this->assertEquals('Français', $array['translations']['fr']);
        $this->assertEquals('Deutsch', $array['translations']['de']);
    }
}
