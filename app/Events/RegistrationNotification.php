<?php

namespace App\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RegistrationNotification implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $event;

    public $user;

    public $action;

    public function __construct(Event $event, User $user, string $action)
    {
        $this->event = $event;
        $this->user = $user;
        $this->action = $action; // 'register' or 'unregister'

    }

    public function broadcastOn()
    {
        // i disabled this until fix authorization
        // return new PrivateChannel('organizer.' . $this->event->organizer_id);
        return new Channel('organizer.'.$this->event->organizer_id);
    }

    public function broadcastAs()
    {
        return 'new-event-registration';
    }

    public function broadcastWith()
    {
        \Log::info('Broadcasting on channel: organizer.'.$this->event->organizer_id);

        $actionText = $this->action === 'register' ? 'registered for' : 'unregistered from';

        return [
            'message' => ''.$this->user->name.' has  been '.$actionText.' for: '.$this->event->title,
            'event' => $this->event,
        ];
    }
}
