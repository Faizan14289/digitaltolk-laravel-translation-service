<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

// Make sure the User model exists and uses HasFactory
use App\Models\Translation;
use App\Models\Language;
use App\Models\Tag;
use Laravel\Sanctum\Sanctum;
use DB;

class TranslationApiTest extends TestCase
{
    // Use RefreshDatabase to rollback DB changes after each test
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user for authentication
        $this->user = User::factory()->create();
    }

    // --- LIST / INDEX ---

    /** @test */
    public function it_can_list_translations_with_pagination()
    {
        Sanctum::actingAs($this->user);
        Translation::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/translations');

        $response->assertStatus(200);
        // Assert structure, count etc.
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'key', 'default_value', 'translations', 'tags', 'created_at', 'updated_at'] // Check structure
            ],
            'links', // Check pagination links are present
            'meta'  // Check pagination meta is present
        ]);
        // Basic count check (should be 5 or less on the first page)
        $this->assertLessThanOrEqual(15, count($response->json('data'))); // Default pagination is 15

        // Assert performance (basic example)
        // $start = microtime(true);
        // $response = $this->getJson('/api/v1/translations');
        // $end = microtime(true);
        // $duration = $end - $start;
        // $this->assertLessThan(0.2, $duration, 'List response time exceeded 200ms. Duration: ' . $duration . 's');
    }

    // --- SEARCH / FILTER ---

    /** @test */
    public function it_can_filter_translations_by_tag()
    {
        Sanctum::actingAs($this->user);
        $tag = Tag::factory()->create(['name' => 'search_tag']);
        $translationWithTag = Translation::factory()->create(['key' => 'key.with.tag']);
        $translationWithoutTag = Translation::factory()->create(['key' => 'key.without.tag']);
        $translationWithTag->tags()->attach($tag);

        $response = $this->getJson('/api/v1/translations?tag=search_tag');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data'); // Should only return 1 item
        $response->assertJsonFragment(['id' => $translationWithTag->id]);
        $response->assertJsonMissing(['id' => $translationWithoutTag->id]);
    }

    /** @test */
    public function it_can_filter_translations_by_key()
    {
        Sanctum::actingAs($this->user);
        $translationMatch = Translation::factory()->create(['key' => 'specific.key.to.find']);
        $translationNoMatch = Translation::factory()->create(['key' => 'another.different.key']);

        $response = $this->getJson('/api/v1/translations?key=specific.key.to.find');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $translationMatch->id]);
        $response->assertJsonMissing(['id' => $translationNoMatch->id]);
    }

    /** @test */
    /** @test */
    public function it_can_filter_translations_by_content_in_default_value()
    {
        // 1. Ensure the user is authenticated for the request
        Sanctum::actingAs($this->user);

        // 2. Define the search term
        $searchTerm = 'UniqueDefaultContent123';

        // 3. Create the test data using factories
        $translationMatch = Translation::factory()->create(['default_value' => 'Start ' . $searchTerm . ' End']);
        $translationNoMatch = Translation::factory()->create(['default_value' => 'Completely Different']);

        // --- DEBUGGING SECTION ---
        // Let's verify the data was created as expected in the database
        $this->assertDatabaseHas('translations', ['id' => $translationMatch->id, 'default_value' => 'Start ' . $searchTerm . ' End']);
        $this->assertDatabaseHas('translations', ['id' => $translationNoMatch->id, 'default_value' => 'Completely Different']);

        // Let's also check what the database query inside the controller *should* find
        // Replicate the core part of the controller's content search logic for debugging
        $manualQueryResults = DB::table('translations')
            ->where('default_value', 'like', "%{$searchTerm}%")
            ->pluck('id')
            ->toArray();

        // Assert that our manual query finds the matching ID
        $this->assertContains($translationMatch->id, $manualQueryResults, 'Manual DB query did not find the matching translation ID.');
        $this->assertNotContains($translationNoMatch->id, $manualQueryResults, 'Manual DB query incorrectly found the non-matching translation ID.');

        // --- END DEBUGGING SECTION ---

        // 4. Make the API request
        $response = $this->getJson('/api/v1/translations?content=' . urlencode($searchTerm));

        // 5. Assert the response status
        $response->assertStatus(200);

        // --- DEBUGGING THE RESPONSE ---
        // Get the raw response data to inspect
        $responseData = $response->json();
        // Log the response data for inspection (requires logging to be configured)
        // \Log::debug('Filter Test Response Data:', $responseData);

        // Explicitly check the structure and content
        $this->assertArrayHasKey('data', $responseData, 'Response JSON does not have a \'data\' key.');
        $this->assertIsArray($responseData['data'], '\'data\' key in response is not an array.');

        // Check the count assertion with a more descriptive message
        $actualCount = count($responseData['data']);
        $this->assertEquals(1, $actualCount, "Expected 1 translation in response, but found {$actualCount}. Response data: " . json_encode($responseData));

        // Check if the expected ID is present
        $foundIds = collect($responseData['data'])->pluck('id')->toArray();
        $this->assertContains($translationMatch->id, $foundIds, "Response data does not contain the expected translation ID ({$translationMatch->id}). Found IDs: " . json_encode($foundIds));

        // Check if the unexpected ID is absent
        $this->assertNotContains($translationNoMatch->id, $foundIds, "Response data incorrectly contains the non-matching translation ID ({$translationNoMatch->id}). Found IDs: " . json_encode($foundIds));

        // --- ORIGINAL ASSERTIONS (should now pass if debugging assertions pass) ---
        // $response->assertJsonCount(1, 'data'); // Replaced by explicit check above
        // $response->assertJsonFragment(['id' => $translationMatch->id]); // Replaced by explicit check above
        // $response->assertJsonMissing(['id' => $translationNoMatch->id]); // Replaced by explicit check above
    }


    // --- SHOW / GET SINGLE ---

    /** @test */
    public function it_can_show_a_single_translation()
    {
        Sanctum::actingAs($this->user);
        $translation = Translation::factory()->create();
        // Add a language translation and tags for completeness
        $language = Language::factory()->create(['code' => 'fr']);
        $tag = Tag::factory()->create(['name' => 'single_test']);
        $translation->languages()->attach($language->id, ['translated_text' => 'Bonjour']);
        $translation->tags()->attach($tag);

        $response = $this->getJson("/api/v1/translations/{$translation->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id', 'key', 'default_value', 'translations', 'tags', 'created_at', 'updated_at'
            ]
        ]);
        $response->assertJsonFragment([
            'id' => $translation->id,
            'key' => $translation->key,
            'default_value' => $translation->default_value,
            'translations' => ['fr' => 'Bonjour'], // Check specific translation
            'tags' => ['single_test'] // Check specific tag
        ]);
    }

    /** @test */
    public function it_returns_404_when_showing_nonexistent_translation()
    {
        Sanctum::actingAs($this->user);
        $nonExistentId = 999999;

        $response = $this->getJson("/api/v1/translations/{$nonExistentId}");

        $response->assertStatus(404);
    }

    // --- CREATE / STORE ---

    /** @test */
    public function it_can_create_a_new_translation()
    {
        Sanctum::actingAs($this->user);
        $language = Language::factory()->create(['code' => 'fr']);
        $tag = Tag::factory()->create(['name' => 'mobile']);

        $data = [
            'key' => 'test.create.key',
            'default_value' => 'Default Text for Creation',
            'translations' => ['fr' => 'Texte Francais Cree'],
            'tags' => ['mobile']
        ];

        $response = $this->postJson('/api/v1/translations', $data);

        // --- TEMPORARY DEBUGGING ---
        // Print the raw response status and body to see what the API actually returned
        // dump('Response Status: ' . $response->status());
        // dump('Response Content: ' . $response->getContent());

        // Or log it
        \Log::debug('CREATE TEST RESPONSE', [
            'status' => $response->status(),
            'headers' => $response->headers->all(),
            'content' => $response->getContent()
        ]);
        // --- END TEMPORARY DEBUGGING ---

        $response->assertStatus(201); // Created

        // If the status is 201, check the structure
        // If the status is NOT 201 (e.g., 422, 500), this will fail and show the status,
        // but the log above will show the content explaining *why*.
        $response->assertJsonStructure([
            'data' => [
                'id', 'key', 'default_value', 'translations', 'tags', 'created_at', 'updated_at'
            ]
        ]);

        $response->assertJsonFragment([
            'key' => 'test.create.key',
            'default_value' => 'Default Text for Creation',
            'translations' => ['fr' => 'Texte Francais Cree'],
            'tags' => ['mobile']
        ]);

        // Assert it's in the database
        $this->assertDatabaseHas('translations', ['key' => 'test.create.key']);
        $createdTranslation = Translation::where('key', 'test.create.key')->first();
        $this->assertNotNull($createdTranslation);
        $this->assertEquals('Texte Francais Cree', $createdTranslation->languages->firstWhere('code', 'fr')->pivot->translated_text);
        $this->assertTrue($createdTranslation->tags->contains('name', 'mobile'));
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/translations', []); // Empty data

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['key', 'default_value']); // Check specific errors
    }

    /** @test */
    public function it_validates_unique_key_on_create()
    {
        Sanctum::actingAs($this->user);
        $existingTranslation = Translation::factory()->create(['key' => 'unique.key.test']);

        $data = [
            'key' => 'unique.key.test', // Duplicate key
            'default_value' => 'Some Value',
        ];

        $response = $this->postJson('/api/v1/translations', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['key']);
    }

    // --- UPDATE / PUT/PATCH ---

    /** @test */
    public function it_can_update_an_existing_translation()
    {
        Sanctum::actingAs($this->user);
        $translation = Translation::factory()->create([
            'key' => 'original.key',
            'default_value' => 'Original Value'
        ]);
        $languageFr = Language::factory()->create(['code' => 'fr']);
        $languageEs = Language::factory()->create(['code' => 'es']);
        $tagOld = Tag::factory()->create(['name' => 'old_tag']);
        $tagNew = Tag::factory()->create(['name' => 'new_tag']);

        $translation->languages()->attach($languageFr->id, ['translated_text' => 'Ancien Texte']);
        $translation->tags()->attach($tagOld);

        $updateData = [
            'key' => 'updated.key',
            'default_value' => 'Updated Value',
            'translations' => [
                'fr' => 'Texte Mis a Jour', // Update existing
                'es' => 'Texto Actualizado'  // Add new
            ],
            'tags' => ['new_tag'] // Replace old tags
        ];

        $response = $this->putJson("/api/v1/translations/{$translation->id}", $updateData);

        $response->assertStatus(200); // OK
        $response->assertJsonFragment([
            'key' => 'updated.key',
            'default_value' => 'Updated Value',
            'translations' => [
                'fr' => 'Texte Mis a Jour',
                'es' => 'Texto Actualizado'
            ],
            'tags' => ['new_tag']
        ]);
        $response->assertJsonMissing(['tags' => ['old_tag']]); // Ensure old tag is gone

        // Assert database changes
        $this->assertDatabaseHas('translations', ['id' => $translation->id, 'key' => 'updated.key', 'default_value' => 'Updated Value']);
        $this->assertDatabaseMissing('translations', ['key' => 'original.key']); // Old key gone

        $updatedTranslation = $translation->fresh(); // Refresh model from DB
        $this->assertEquals('Texte Mis a Jour', $updatedTranslation->languages->firstWhere('code', 'fr')->pivot->translated_text);
        $this->assertEquals('Texto Actualizado', $updatedTranslation->languages->firstWhere('code', 'es')->pivot->translated_text);
        $this->assertFalse($updatedTranslation->tags->contains('name', 'old_tag'));
        $this->assertTrue($updatedTranslation->tags->contains('name', 'new_tag'));
    }

    /** @test */
    public function it_validates_fields_on_update()
    {
        Sanctum::actingAs($this->user);
        $translation = Translation::factory()->create();

        $invalidData = [
            'key' => '', // Invalid: required if present
            'default_value' => '' // Invalid: required if present
        ];

        $response = $this->putJson("/api/v1/translations/{$translation->id}", $invalidData);

        $response->assertStatus(422);
        // Assert that validation errors are present for both fields
        $response->assertJsonValidationErrors(['key', 'default_value']);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_translation()
    {
        Sanctum::actingAs($this->user);
        $nonExistentId = 999999;
        $data = ['key' => 'doesnt.matter'];

        $response = $this->putJson("/api/v1/translations/{$nonExistentId}", $data);

        $response->assertStatus(404);
    }

    // --- DELETE / DESTROY ---

    /** @test */
    public function it_can_delete_a_translation()
    {
        Sanctum::actingAs($this->user);
        $translation = Translation::factory()->create();

        $response = $this->deleteJson("/api/v1/translations/{$translation->id}");

        $response->assertStatus(204); // No Content

        // Assert it's deleted from the database
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
        // Also check pivot tables are cleaned up (cascading deletes in migrations should handle this)
        // This is implicitly tested by the database state, but you could query language_translations/taggables if needed.
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_translation()
    {
        Sanctum::actingAs($this->user);
        $nonExistentId = 999999;

        $response = $this->deleteJson("/api/v1/translations/{$nonExistentId}");

        $response->assertStatus(404);
    }

    // --- AUTHENTICATION ---

    /** @test */
    public function it_requires_authentication_to_access_crud_endpoints()
    {
        // Do NOT call Sanctum::actingAs()

        // Test List
        $response = $this->getJson('/api/v1/translations');
        $response->assertStatus(401); // Unauthorized

        // Test Show
        $translation = Translation::factory()->create();
        $response = $this->getJson("/api/v1/translations/{$translation->id}");
        $response->assertStatus(401);

        // Test Create
        $response = $this->postJson('/api/v1/translations', []);
        $response->assertStatus(401);

        // Test Update
        $response = $this->putJson("/api/v1/translations/{$translation->id}", []);
        $response->assertStatus(401);

        // Test Delete
        $response = $this->deleteJson("/api/v1/translations/{$translation->id}");
        $response->assertStatus(401);
    }

}
