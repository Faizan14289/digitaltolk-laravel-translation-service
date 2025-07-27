<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExportController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v1/export/{locale}",
     *      operationId="exportTranslations",
     *      tags={"Export"},
     *      summary="Export translations for a locale",
     *      description="Returns a JSON object of key-value pairs for the specified locale. Optimized for performance (< 500ms).",
     *      @OA\Parameter(name="locale", description="Language code (e.g., en, fr, es)", required=true, in="path", @OA\Schema(type="string")),
     *      @OA\Response(response=200, description="Successful operation",
     *          @OA\JsonContent(type="object", example={"messages.welcome": "Welcome!", "messages.error": "An error occurred."})
     *      ),
     *      @OA\Response(response=404, description="Language not found")
     * )
     */
    public function export(string $locale): JsonResponse
    {
        $language = Language::where('code', $locale)->first();
        if (!$language) {
            return response()->json(['error' => 'Language not found'], 404);
        }

        // Efficient query: Join and pluck directly into the desired format
        $translations = $language->translations()
            ->join('language_translations as lt', 'translations.id', '=', 'lt.translation_id')
            ->where('lt.language_id', $language->id)
            ->select('translations.key', 'lt.translated_text')
            ->pluck('lt.translated_text', 'translations.key')
            ->toArray();

        return response()->json($translations);
    }
}
