<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Language;

class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        static $codes = ['en', 'fr', 'es', 'de', 'it', 'pt'];
        static $names = ['English', 'French', 'Spanish', 'German', 'Italian', 'Portuguese'];
        static $index = 0;
        $code = $codes[$index % count($codes)];
        $name = $names[$index % count($names)];
        $index++;
        return ['code' => $code, 'name' => $name,];
    }
}
