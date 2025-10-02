<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transforme un Product en tableau JSON pour l'API.
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'type'          => $this->type ?? '',

            // ⚡️ Important : caster en double/float
            'price'         => (double) $this->price,

            // ⚡️ Correspond exactement à @SerializedName("image_url")
            'image_url'     => $this->image_url
                ? asset('storage/'.$this->image_url)
                : '',

            // ⚡️ Correspond exactement à @SerializedName("is_active")
            // Laravel renvoie parfois 0/1 → on force un booléen
            'is_active'     => (bool) $this->is_active,

            // ⚡️ Correspond exactement à @SerializedName("category_id")
            'category_id'   => $this->category_id,

            // ⚡️ Correspond exactement à @SerializedName("category_name")
            'category_name' => optional($this->category)->name ?? '',

            // Valeur par défaut comme dans Kotlin
            'status'        => 'free',
        ];
    }
}
