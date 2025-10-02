<?php

namespace App\Events;


use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class GenericEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $event;
    public $data;

    public function __construct($event, $data)
    {
        $this->event = $event;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new Channel('pos-channel');
    }

    public function broadcastAs()
    {
        return $this->event;
    }
}

