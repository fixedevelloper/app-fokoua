<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable=[
        'table_id','cashier_id','status','total_food','total_drink','grand_total','server_id','preparing_time','status_payment'
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function table() {
        return $this->belongsTo(Table::class);
    }
    public function items() {
        return $this->hasMany(OrderItem::class);
    }
    public function payments() {
        return $this->hasMany(Payment::class);
    }
}
