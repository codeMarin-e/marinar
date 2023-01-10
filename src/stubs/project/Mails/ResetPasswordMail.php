<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($url)
    {
        $this->url = $url;
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
            ->subject( trans('mails/reset_password.subject'))
            ->from("noreply@".$site->domain, config('app.name'))
            ->view('mails.reset_password', [
                'url' => $this->url
            ]);
    }
}
