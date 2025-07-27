<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Translation;
use App\Models\Language;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class TranslationSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    public function run(): void
    {
        $languageIds = Language::pluck('id')->toArray();
        $tagIds = Tag::pluck('id')->toArray();

        ini_set('memory_limit', '2G'); // increase to 2GB

        if (empty($languageIds)) {
            echo "No languages found. Please seed languages first.\n";
            return;
        }

        $totalRecords = 100000; // Reduced for better performance
        $batchSize = 1000; // Smaller batch size
        echo "Starting seeding of $totalRecords records...\n";

        // Get the current max ID to properly track inserted records
        $startingId = DB::table('translations')->max('id') ?? 0;

        for ($batch = 0; $batch < ceil($totalRecords / $batchSize); $batch++) {
            $insertTranslations = [];
            $insertLanguageTranslations = [];
            $insertTaggables = [];
            $currentBatchSize = min($batchSize, $totalRecords - ($batch * $batchSize));

            // Generate translation data
            for ($i = 0; $i < $currentBatchSize; $i++) {
                $translation = Translation::factory()->make();
                $insertTranslations[] = [
                    'key' => $translation->key,
                    'default_value' => $translation->default_value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($insertTranslations)) {
                DB::table('translations')->insert($insertTranslations);

                // Calculate the actual inserted IDs
                $firstId = $startingId + ($batch * $batchSize) + 1;
                $lastId = $firstId + count($insertTranslations) - 1;
                $translationIds = range($firstId, $lastId);

                // Create related records
                foreach ($translationIds as $index => $transId) {
                    // Attach languages
                    foreach ($languageIds as $langId) {
                        if (rand(0, 1)) { // 50% chance
                            $insertLanguageTranslations[] = [
                                'language_id' => $langId,
                                'translation_id' => $transId,
                                'translated_text' => $this->faker->sentence(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    // Attach tags
                    if (!empty($tagIds) && rand(0, 2) == 0) {
                        $randomTagId = $tagIds[array_rand($tagIds)];
                        $insertTaggables[] = [
                            'tag_id' => $randomTagId,
                            'taggable_id' => $transId,
                            'taggable_type' => Translation::class,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Insert related records
                if (!empty($insertLanguageTranslations)) {
                    DB::table('language_translations')->insert($insertLanguageTranslations);
                }
                if (!empty($insertTaggables)) {
                    DB::table('taggables')->insert($insertTaggables);
                }
            }

            echo "Batch " . ($batch + 1) . " completed.\n";
            // Add small delay to prevent memory issues
            usleep(50000); // 50ms
        }

        echo "Seeding of $totalRecords records complete.\n";
    }
}
