<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'method'=>$this->method,
            'amount'=>$this->amount,
            'orderNumber'   => $this->order->id,
            'orderTable'   => $this->order->table_id,
            'orderTotal'   => $this->order->grand_total,
            'cashier'   => $this->id,
            'time'          => $this->created_at ? $this->created_at->toTimeString() : '',
            'date'          => $this->created_at ? $this->created_at->toDateString() : '',
        ];
    }
}
