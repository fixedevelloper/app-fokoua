<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemAccompaniment extends Model
{
    protected $fillable=[
      'order_item_id','accompaniment_id','quantity','price'
    ];
}
