<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable=[
      'order_id','type','method','amount'
    ];
    public function order() {
        return $this->belongsTo(Order::class);
    }
}
