<?php

namespace App\Notifications;

use App\Mails\ContactMail;
use App\Mails\OrderOverviewMail;
use App\Mails\RegisterUserMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Marinar\SmsChannel\Models\FrontSms;
use Marinar\SmsChannel\Models\FrontSmsChannel;

class OrderOverviewNotification extends Notification
{
    use Queueable;
    public $overviewData;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($overviewData)
    {
        $this->overviewData = $overviewData;
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
        $this->overviewData['subject'] = isset($this->overviewData['subject']) ?
            str_replace(['[ORDER_ID]'], [ $this->overviewData['order']->id ], $this->overviewData['subject']) :
            'Order #'.$this->overviewData['order']->id;

        $bcc = array();
        if(!config('testing')) {
            $bcc[] = 'noreply@test.test';
        }

        return ( new OrderOverviewMail($this->overviewData))
            //->to($notifiable->email)
            ->bcc($bcc)
            ->subject($this->overviewData['subject']);
//            ->bcc([
//                'marin@dev.nddesign.no'
//            ]);
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
