<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use App\Http\Resources\TranslationResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Tag;
use Illuminate\Support\Facades\Log;
// For syncing tags
use App\Models\Language;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Translation Management Service API",
 *      description="API for managing translations across multiple languages and contexts (tags)",
 *      @OA\Contact(email="support@example.com"),
 *      @OA\License(name="Apache 2.0", url="http://www.apache.org/licenses/LICENSE-2.0.html")
 * )
 *
 * @OA\Server(url=L5_SWAGGER_CONST_HOST, description="API Server")
 *
 * @OA\SecurityScheme(
 *      securityScheme="sanctum",
 *      type="apiKey",
 *      in="header",
 *      name="Authorization",
 *      description="Laravel Sanctum Token (Bearer <token>)"
 * )
 *
 * @OA\Schema(
 *     schema="TranslationResource",
 *     type="object",
 *     title="Translation Resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="key", type="string", example="messages.welcome"),
 *     @OA\Property(property="default_value", type="string", example="Welcome!"),
 *     @OA\Property(property="translations", type="object", example={"es": "¡Bienvenido!", "fr": "Bienvenue !"}),
 *     @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"web", "homepage"}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

class TranslationController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/translations",
     *      operationId="getTranslationsList",
     *      tags={"Translations"},
     *      summary="Get list of translations",
     *      description="Returns paginated list of translations. Supports filtering by tag, key, or content.",
     *      security={ {"sanctum": {} }},
     *      @OA\Parameter(name="tag", in="query", description="Filter by tag name", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="key", in="query", description="Filter by translation key (partial)", required=false, @OA\Schema(type="string")),
     *      @OA\Parameter(name="content", in="query", description="Filter by content (default_value or translated_text)", required=false, @OA\Schema(type="string")),
     *      @OA\Response(response=200, description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TranslationResource")),
     *              @OA\Property(property="links", type="object"), @OA\Property(property="meta", type="object")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden")
     * )
     */

    public function index(Request $request)
    {
        // Log::debug('Index request received (Join version)', ['filters' => $request->all()]); // Debug

        $query = Translation::with(['languages', 'tags']); // Still eager load for the resource

        if ($request->has('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('name', $request->tag));
        }

        if ($request->has('key')) {
            $query->where('key', 'like', "%{$request->key}%");
        }

        if ($request->has('content')) {
            $content = $request->input('content');

            // ONLY search default_value. No OR, no whereHas for languages.
            $query->where('default_value', 'like', "%{$content}%");
        }

        $translations = $query->paginate(15);
        // Log::debug('Query executed (Join version), results count: ' . $translations->count()); // Debug

        return TranslationResource::collection($translations);
    }

    /**
     * @OA\Post(
     *      path="/api/v1/translations",
     *      operationId="storeTranslation",
     *      tags={"Translations"},
     *      summary="Create a new translation",
     *      description="Creates a new translation key with default value, associated languages/translations, and tags.",
     *      security={ {"sanctum": {} }},
     *      @OA\RequestBody(required=true,
     *          @OA\JsonContent(
     *              required={"key", "default_value"},
     *              @OA\Property(property="key", type="string", example="messages.welcome"),
     *              @OA\Property(property="default_value", type="string", example="Welcome!"),
     *              @OA\Property(property="translations", type="object", example={"es": "¡Bienvenido!", "fr": "Bienvenue !"}),
     *              @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"web", "homepage"})
     *          )
     *      ),
     *      @OA\Response(response=201, description="Successful creation",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/TranslationResource"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */

    /**
     * Store a newly created translation in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse // Return type hint is fine
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|unique:translations',
            'default_value' => 'required|string',
            'translations' => 'array',
            'translations.*' => 'string',
            'tags' => 'array',
            'tags.*' => 'string|exists:tags,name',
        ]);

        if ($validator->fails()) {
            // Consider using Log::debug or Log::info for less critical info in production
            Log::debug('Validation failed in store', ['errors' => $validator->errors(), 'data' => $request->all()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        Log::debug('Validation passed in store', ['data' => $data]);

        try {
            $translationData = [
                'key' => $data['key'],
                'default_value' => $data['default_value'],
            ];

            $translation = Translation::create($translationData);
            Log::debug('Translation created', ['translation_id' => $translation->id]);

            // Handle language translations
            if (isset($data['translations'])) {
                $this->syncTranslations($translation, $data['translations']);
                Log::debug('Translations synced');
            }

            // Handle tags - Fetch Tag models first
            if (isset($data['tags'])) {
                $tags = Tag::whereIn('name', $data['tags'])->get();
                Log::debug('Tags found for sync', ['tags' => $tags->pluck('name')]);
                $translation->tags()->sync($tags);
                Log::debug('Tags synced');
            }

            // CRITICAL: Reload the model with relationships to ensure the resource has the latest data
            // fresh() already eager loads the specified relationships
            $translation = $translation->fresh(['languages', 'tags']);
            Log::debug('Translation fresh loaded', ['translation' => $translation]);

            // --- REMOVE THIS LINE ---
            // $translation->load(['languages', 'tags']); // Redundant after fresh()

            // Return the resource wrapped in a JSON response with 201 status
            // The TranslationResource should format it correctly, including the 'data' wrapper.
            $resource = new TranslationResource($translation);
            return response()->json(['data' => $resource->toArray($request)], 201);


        } catch (\Exception $e) {
            // Log the full exception trace for easier debugging
            Log::error('Error creating translation', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data ?? null // Log data if it was validated
            ]);
            // Consider a more generic message for the user in production
            return response()->json(['error' => 'An error occurred while creating the translation.'], 500);
        }
    }

    /**
     * @OA\Get(
     *      path="/api/v1/translations/{id}",
     *      operationId="getTranslationById",
     *      tags={"Translations"},
     *      summary="Get translation by ID",
     *      description="Returns a single translation resource.",
     *      security={ {"sanctum": {} }},
     *      @OA\Parameter(name="id", description="Translation ID", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/TranslationResource"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Translation not found")
     * )
     */
    public function show(Translation $translation)
    {
        return new TranslationResource($translation->load(['languages', 'tags']));
    }


    /**
     * @OA\Put(
     *      path="/api/v1/translations/{id}",
     *      operationId="updateTranslation",
     *      tags={"Translations"},
     *      summary="Update a translation",
     *      description="Updates an existing translation key, default value, associated languages/translations, or tags.",
     *      security={ {"sanctum": {} }},
     *      @OA\Parameter(name="id", description="Translation ID", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\RequestBody(required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="key", type="string", example="messages.greeting", nullable=true),
     *              @OA\Property(property="default_value", type="string", example="Greetings!", nullable=true),
     *              @OA\Property(property="translations", type="object", example={"es": "¡Saludos!", "fr": "Salutations !"}, nullable=true),
     *              @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"web", "greeting"}, nullable=true)
     *          )
     *      ),
     *      @OA\Response(response=200, description="Successful update",
     *          @OA\JsonContent(@OA\Property(property="data", ref="#/components/schemas/TranslationResource"))
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Translation not found"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */

    public function update(Request $request, Translation $translation): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'sometimes|required|unique:translations,key,' . $translation->id,
            'default_value' => 'sometimes|required|string',
            'translations' => 'array',
            'translations.*' => 'string',
            'tags' => 'array',
            'tags.*' => 'string|exists:tags,name',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $translation->update($request->only(['key', 'default_value']));

        if (isset($data['translations'])) {
            $this->syncTranslations($translation, $data['translations']);
        }
        if (isset($data['tags'])) {
            $translation->tags()->sync(Tag::whereIn('name', $data['tags'])->pluck('id'));
        }
        return response()->json(new TranslationResource($translation->fresh(['languages', 'tags'])));
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/translations/{id}",
     *      operationId="deleteTranslation",
     *      tags={"Translations"},
     *      summary="Delete a translation",
     *      description="Removes a translation and its associated language entries.",
     *      security={ {"sanctum": {} }},
     *      @OA\Parameter(name="id", description="Translation ID", required=true, in="path", @OA\Schema(type="integer")),
     *      @OA\Response(response=204, description="Successful deletion"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Translation not found")
     * )
     */
    public function destroy(Translation $translation): JsonResponse
    {
        $translation->delete();
        return response()->json(null, 204);
    }

    private function syncTranslations(Translation $translation, array $translations)
    {
        // Get language codes that are keys in the input array
        $languageCodes = array_keys($translations);
        // Fetch Language models keyed by their code for easy lookup
        $languages = \App\Models\Language::whereIn('code', $languageCodes)->get()->keyBy('code');
        \Log::debug('Languages found for sync', ['codes' => $languageCodes, 'languages' => $languages->pluck('id', 'code')]); // Debug log

        $syncData = [];
        foreach ($translations as $code => $text) {
            // Only sync if the language code exists in our database
            if (isset($languages[$code])) {
                $syncData[$languages[$code]->id] = ['translated_text' => $text];
            } else {
                \Log::warning("Language code '{$code}' not found, skipping sync for this translation.");
            }
        }
        \Log::debug('Sync data prepared', ['syncData' => $syncData]); // Debug log
        // Sync the languages and their translated texts
        $translation->languages()->sync($syncData);
    }
}
