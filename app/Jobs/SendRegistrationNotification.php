<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\User;
use App\Notifications\ParticipantRegistrationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendRegistrationNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;

    protected $organizer;

    protected $participant;

    protected $action;

    public function __construct(Event $event, User $organizer, User $participant, string $action)
    {
        $this->event = $event;
        $this->organizer = $organizer;
        $this->participant = $participant;
        $this->action = $action; // 'register' or 'unregister'
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Send notification to organizer
            Notification::send(
                $this->organizer,
                new ParticipantRegistrationNotification(
                    $this->event,
                    $this->participant,
                    $this->action
                )
            );
        } catch (\Exception $e) {
            Log::error('Failed to send registration notification: '.$e->getMessage());
        }
    }
}
