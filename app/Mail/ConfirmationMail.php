<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct($user)
    {
        $this->user = $user; // Pass user data to the email
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->view('emails.confirmation') // Specify the Blade view
            ->with([
                'name' => $this->user->name, // Pass additional data to the view
            ]);
    }
}
