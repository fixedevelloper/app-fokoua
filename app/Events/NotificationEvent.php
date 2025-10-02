<?php


namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcastNow
{
    use  SerializesModels;

    public $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        logger()->info('broadcastOn called', [
            'notification' => $this->notification,
            'recipient_type' => $this->notification->recipient_type,
            'recipient_id' => $this->notification->recipient_id,
        ]);
        // Canal privé par type et ID
       // return new PrivateChannel("app_notifications.{$this->notification->recipient_type}.{$this->notification->recipient_id}");
        return  new Channel("app_notifications.{$this->notification->recipient_type}.{$this->notification->recipient_id}");
    }

    public function broadcastAs()
    {
        return 'app_notifications.received';
    }
}
