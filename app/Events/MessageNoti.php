<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $user_id;
    public $message;
    public function __construct($user_id,$message)
    {
        $this->user_id = $user_id;
        $this->message = $message;
    }
    public function broadcastOn(): array
    {
        return [
            'notification_message.' . $this->user_id
        ];
    }
    public function broadcastAs()
    {
        return 'count';
    }
}
