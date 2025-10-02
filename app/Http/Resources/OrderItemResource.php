<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'type' => $this->type,
            'productId' => $this->product_id,
            'quantity'  => $this->quantity,
            'status'  => $this->status,
            'price'     => (double) $this->base_price,
            'product'     => new ProductResource($this->whenLoaded('product')),
            'supplements' => $this->supplements,
            'extras'=>'extras'
        ];
    }
}

