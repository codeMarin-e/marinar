<?php

    namespace App\Notifications;

    use App\Mails\ResetPasswordMail;
    use Illuminate\Notifications\Messages\MailMessage;
    use Illuminate\Notifications\Notification;
    use Illuminate\Support\Facades\Lang;

    class ResetPassword extends Notification
    {
        /**
         * The password reset token.
         *
         * @var string
         */
        public $token;

        /**
         * Create a notification instance.
         *
         * @param  string  $token
         * @return void
         */
        public function __construct($token)
        {
            $this->token = $token;
        }

        /**
         * Get the notification's channels.
         *
         * @param  mixed  $notifiable
         * @return array|string
         */
        public function via($notifiable)
        {
            return ['mail'];
        }

        /**
         * Build the mail representation of the notification.
         *
         * @param  mixed  $notifiable
         * @return \Illuminate\Notifications\Messages\MailMessage
         */
        public function toMail($notifiable)
        {
            $url = url(route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $return = (new ResetPasswordMail($url))
                ->to($notifiable->email);
            if(config('app.testing')) {
                $return = $return
                    ->bcc([
                        config("app.testing_mail")
                    ]);
            }
            return $return;

            return $this->buildMailMessage($url);
        }
    }
