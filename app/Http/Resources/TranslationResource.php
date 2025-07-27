<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TranslationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'default_value' => $this->default_value,
            'translations' => $this->languages->mapWithKeys(fn($lang) => [$lang->code => $lang->pivot->translated_text]),
            'tags' => $this->tags->pluck('name'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
