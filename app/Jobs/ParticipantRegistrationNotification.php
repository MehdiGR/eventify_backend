<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParticipantRegistrationNotification extends Notification
{
    use Queueable;

    protected $event;

    protected $participant;

    protected $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event, User $participant, string $action)
    {
        $this->event = $event;
        $this->participant = $participant;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $action = $this->action === 'register' ? 'registered for' : 'unregistered from';

        return (new MailMessage)
            ->subject("Participant {$action} your event: {$this->event->title}")
            ->line("{$this->participant->name} has {$action} your event.")
            ->line("Event: {$this->event->title}")
            ->line("Date: {$this->event->start_date}")
            ->action('View Event Details', url("/events/{$this->event->id}"));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $action = $this->action === 'register' ? 'registered for' : 'unregistered from';

        return [
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'participant_id' => $this->participant->id,
            'participant_name' => $this->participant->name,
            'action' => $this->action,
            'message' => "{$this->participant->name} has {$action} your event: {$this->event->title}",
        ];
    }
}
