<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name'];

    public function translations()
    {
        return $this->belongsToMany(Translation::class, 'language_translations')
            ->withPivot('translated_text')
            ->withTimestamps();
    }
}
