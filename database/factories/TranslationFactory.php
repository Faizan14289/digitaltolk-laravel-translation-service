<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Translation;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;
    protected static $counter = 0;

    public function definition(): array
    {
        self::$counter++;

        return [
            'key' => 'app.translation_' . self::$counter,
            'default_value' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
