<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public array $payload;
    public int $kitchenId;

    public function __construct(array $payload, int $kitchenId)
    {
        $this->payload = $payload;
        $this->kitchenId = $kitchenId;
    }

    // Channel public ou privé selon ton choix (ici public pour l'exemple)
    public function broadcastOn()
    {
        return new Channel("kds.{$this->kitchenId}");
        //return new PrivateChannel("kds.{$this->kitchenId}");
    }

    public function broadcastWith()
    {
        return [
            'event' => 'order.created',
            'data' => $this->payload,
        ];
    }

    public function broadcastAs()
    {
        return 'order.created';
    }
}

