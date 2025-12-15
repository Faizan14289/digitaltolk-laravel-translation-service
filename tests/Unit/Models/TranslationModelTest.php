<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Translation;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class TranslationModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_languages_relationship()
    {
        $translation = Translation::factory()->create();
        $language = Language::factory()->create(['code' => 'es']);
        
        $translation->languages()->attach($language->id, ['translated_text' => 'Hola']);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $translation->languages);
        $this->assertEquals(1, $translation->languages->count());
        $this->assertEquals('es', $translation->languages->first()->code);
    }

    /** @test */
    public function it_has_tags_relationship()
    {
        $translation = Translation::factory()->create();
        $tag = Tag::factory()->create(['name' => 'web']);
        
        $translation->tags()->attach($tag);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $translation->tags);
        $this->assertEquals(1, $translation->tags->count());
        $this->assertEquals('web', $translation->tags->first()->name);
    }

    /** @test */
    public function it_invalidates_cache_on_create()
    {
        Cache::shouldReceive('forget')->once();
        
        $language = Language::factory()->create(['code' => 'fr']);
        $translation = Translation::factory()->create();
        $translation->languages()->attach($language->id, ['translated_text' => 'Bonjour']);
    }

    /** @test */
    public function it_invalidates_cache_on_update()
    {
        $translation = Translation::factory()->create(['key' => 'test.key']);
        $language = Language::factory()->create(['code' => 'es']);
        $translation->languages()->attach($language->id, ['translated_text' => 'Hola']);
        
        Cache::shouldReceive('forget')->with('translations:export:es')->atLeast()->once();
        
        $translation->update(['default_value' => 'Updated value']);
    }

    /** @test */
    public function it_invalidates_cache_on_delete()
    {
        $translation = Translation::factory()->create();
        $language = Language::factory()->create(['code' => 'de']);
        $translation->languages()->attach($language->id, ['translated_text' => 'Hallo']);
        
        Cache::shouldReceive('forget')->with('translations:export:de')->atLeast()->once();
        
        $translation->delete();
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $translation = new Translation();
        
        $this->assertEquals(['key', 'default_value'], $translation->getFillable());
    }

    /** @test */
    public function it_can_retrieve_translated_text_from_pivot()
    {
        $translation = Translation::factory()->create();
        $language = Language::factory()->create(['code' => 'it']);
        
        $translation->languages()->attach($language->id, ['translated_text' => 'Ciao']);
        
        $translation = $translation->fresh(['languages']);
        $this->assertEquals('Ciao', $translation->languages->first()->pivot->translated_text);
    }
}
