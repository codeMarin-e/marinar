<?php

namespace App\Notifications;

use App\Mails\ContactMail;
use App\Mails\RegisterUserMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Marinar\SmsChannel\Models\FrontSms;
use Marinar\SmsChannel\Models\FrontSmsChannel;

class ContactNotification extends Notification
{
    use Queueable;
    public $sendData;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($sendData)
    {
        $this->sendData = $sendData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $return = ( new ContactMail($this->sendData))
            ->subject(($this->sendData['subject']?? 'Contact'));
        if(config('app.testing')) {
            $return = $return
                ->bcc([
                    config("app.testing_mail")
                ]);
        } else {
            $return = $return
                ->bcc([
                    app()->make('Site')->aVar('mail')
                ]);
        }
        return $return;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
