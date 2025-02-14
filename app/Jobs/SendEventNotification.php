<?php

namespace App\Jobs;

use App\Mail\EventNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEventNotification implements ShouldQueue
{
    use Queueable;

    protected $event;

    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(new EventNotificationMail($this->event));

    }
}
