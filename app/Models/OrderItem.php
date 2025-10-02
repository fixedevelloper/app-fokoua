<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable=[
      'order_id','product_id','quantity','base_price','type','status'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function supplements()
    {
        return $this->belongsToMany(Supplement::class)
            ->withPivot(['quantity','price'])
            ->withTimestamps();
    }

    public function accompaniments()
    {
        return $this->belongsToMany(Accompaniment::class)
            ->withTimestamps();
    }

}
