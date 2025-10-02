<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class OrderResource extends JsonResource
{
    /**
     * Transforme l'Order en tableau pour l'API.
     */
    public function toArray($request)
    {
        $start = $this->created_at; // ou $this->started_at si tu as un champ
        $prepTime = $this->preparing_time; // en minutes
        $now = Carbon::now();

        // Temps écoulé en minutes
        $elapsed = $start ? $start->diffInMinutes($now) : 0;

        // Temps restant (jamais négatif)
        $remainingTime = max($prepTime - $elapsed, 0);
        return [
            'id'            => $this->id,

            // ⚡️ Ajoute les champs attendus côté Kotlin
            'orderNumber'   => $this->id, // ou une autre logique si tu as un numéro distinct
            'customerName'  => $this->customer_name ?? '',

            // created_at → time
            'time'          => $this->created_at ? $this->created_at->toDateTimeString() : '',

            'remainingTime' =>(int) $remainingTime, // si tu n’as pas encore cette logique

            // Liste des items (avec une resource imbriquée si tu veux)
            'items'         => OrderItemResource::collection($this->items),

            // grand_total → subtotal
            'subtotal'      => (double) $this->grand_total,
            'drinktotal'      => (double) $this->total_drink,
            'foodtotal'      => (double) $this->total_food,
            'tax'           => 0.0,
            'rounding'      => 0.0,

            'status'        => $this->status,
            'statusPayment'        => $this->status_payment,

            'table_id'      => $this->table_id,
        ];
    }

}
