<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Message implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $conversation;
    public $message;
    public $user;
    public function __construct($conversation, $message)
    {
        $this->conversation = $conversation;
        $this->user = $message->user;
        $this->message = $message;
    }
    public function broadcastOn(): array
    {
        // Log::info("Broadcasting");

        return [
            'conversation.' . $this->conversation->id
        ];
    }
    public function broadcastAs()
    {
        return 'message';
    }
}
