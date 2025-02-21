<?php

namespace App\Events;

use App\Models\Event;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublishedEventNotification implements ShouldBroadcast // 👈 Changed "extends" to "implements"
{
    use Dispatchable, SerializesModels;

    public $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function broadcastOn()
    {
        return new Channel('events');
    }

    public function broadcastAs()
    {
        return 'new-event-created';
    }

    public function broadcastWith()
    {
        return [
            'message' => 'A new event has been release: '.$this->event->title,
            'event' => $this->event,
        ];
    }
}
