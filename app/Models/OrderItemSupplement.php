<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemSupplement extends Model
{
    protected $fillable=[
        'order_item_id','supplement_id','quantity','price'
    ];
}
