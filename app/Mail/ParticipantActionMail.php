<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ParticipantActionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $event;

    public $participant;

    public $action;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, User $participant, string $action)
    {
        $this->event = $event;
        $this->participant = $participant;
        $this->action = $action; // 'register' or 'unregister'
    }

    public function build()
    {
        $actionText = $this->action === 'register' ? 'registered for' : 'unregistered from';
        $subject = "{$this->participant->name} has {$actionText} your event: {$this->event->name}";

        return $this->view('emails.participant_action')
            ->subject($subject)
            ->view('emails.new_event_notification_participant')
            ->with([
                'eventName' => $this->event->name,
                'eventDate' => $this->event->start_date,
                'eventLocation' => $this->event->location,
                'participantName' => $this->participant->name,
                'action' => $this->action,
                'actionText' => $actionText,
            ]);
    }
}
