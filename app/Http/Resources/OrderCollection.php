<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    /**
     * Transforme la collection en tableau JSON pour l'API.
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,  // chaque élément sera transformé via OrderResource automatiquement
        ];
    }

    /**
     * Ajouter les métadonnées de pagination
     */
    public function with($request)
    {
        return [
            'status' => 'success',
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page'    => $this->lastPage(),
                'per_page'     => $this->perPage(),
                'total'        => $this->total(),
            ],
        ];
    }
}
