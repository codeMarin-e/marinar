<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $to_user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($to_user)
    {
        $to_user->loadMissing('addresses');
        $this->to_user = $to_user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $site = app()->make('Site');
        return $this
            ->from("noreply@".$site->domain, config('app.name'))
            ->view('mails.register_mail', [
                'chUser' => $this->to_user
            ]);
    }
}
