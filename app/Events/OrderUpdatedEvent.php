<?php


namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderId;
    public $status;
    public $recipientType;
    public $recipientId;

    public function __construct($orderId, $status, $recipientType, $recipientId)
    {
        $this->orderId = $orderId;
        $this->status = $status;
        $this->recipientType = $recipientType;
        $this->recipientId = $recipientId;
    }

    public function broadcastOn()
    {
        return new Channel('pos-channel');
    }

    public function broadcastAs()
    {
        return 'order.updated';
    }
}

