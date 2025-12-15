<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'default_value'];

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'language_translations')
            ->withPivot('translated_text')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Boot the model and register observers for cache invalidation
     */
    protected static function booted()
    {
        // When a translation is created, updated, or deleted, we need to invalidate
        // the export cache for all languages that have translations for this key
        
        static::created(function ($translation) {
            self::invalidateExportCaches($translation);
        });

        static::updated(function ($translation) {
            self::invalidateExportCaches($translation);
        });

        static::deleted(function ($translation) {
            self::invalidateExportCaches($translation);
        });
    }

    /**
     * Invalidate export caches for all languages associated with this translation
     *
     * @param Translation $translation
     * @return void
     */
    protected static function invalidateExportCaches($translation)
    {
        // Get all languages that have translations for this key
        $languageCodes = $translation->languages()->pluck('code');
        
        foreach ($languageCodes as $locale) {
            \Cache::forget("translations:export:{$locale}");
        }
        
        // If no specific languages found, clear all caches to be safe
        if ($languageCodes->isEmpty()) {
            \App\Http\Controllers\Api\ExportController::clearAllExportCaches();
        }
    }
}
