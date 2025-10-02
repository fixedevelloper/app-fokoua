<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'order_id',
        'title',
        'message',
        'status',
        'sent_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
