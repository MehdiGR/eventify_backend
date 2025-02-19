<?php

namespace App\Jobs;

use App\Mail\NewEventNotificationMail;
use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewEventNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $event;

    /**
     * Create a new job instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Fetch all users who should be notified (e.g., participants)
        $users = User::where('role', 'PARTICIPANT')->get();

        foreach ($users as $user) {
            // Send email notification
            Mail::to($user->email)->send(new NewEventNotificationMail($this->event));
        }
    }
}
