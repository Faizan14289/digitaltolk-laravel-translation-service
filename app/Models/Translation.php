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

    // Listen for events on the Translation model itself
    protected static function booted()
    {
        // These events relate to the translation *key* or *default_value* changing
        // This affects the base data, so potentially all language exports could be considered stale
        // if the frontend logic relies on the key/default being present even without a specific translation.
        // However, the export only includes key=>translated_text pairs where translated_text exists.
        // So, changes to default_value alone might not directly invalidate language exports unless
        // the export logic was changed to include default_value when translated_text is missing.
        // For the current export logic, we mainly care about language_translations changes.

        // If you decide key/default changes should invalidate all:
        // static::updated(function ($translation) {
        //     // Invalidate all export caches if key or default_value changes
        //     if ($translation->wasChanged(['key', 'default_value'])) {
        //          ExportController::forgetExportCaches(); // Invalidate all
        //          // Or, if you track which languages are affected differently, pass specific locales.
        //     }
        // });
        // static::created(function ($translation) {
        //     // A new key is created, but export only includes it if it has translations.
        //     // So, creation alone doesn't necessarily invalidate existing exports.
        //     // Unless you pre-populate translations or the logic changes.
        // });
        // static::deleted(function ($translation) {
        //     // A key is deleted, so it will be removed from exports.
        //     // Invalidate all exports to reflect this.
        //     ExportController::forgetExportCaches(); // Invalidate all
        // });
    }
}
