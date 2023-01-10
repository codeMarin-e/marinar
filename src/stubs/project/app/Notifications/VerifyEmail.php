<?php
    namespace App\Notifications;

    use App\Mails\VerifyEmailMail;
    use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;

    class VerifyEmail extends BaseVerifyEmail
    {
        public function toMail($notifiable) {
            $verificationUrl = $this->verificationUrl($notifiable);
            $return = (new VerifyEmailMail($notifiable, $verificationUrl))
                ->to($notifiable->email);
            if(config('app.testing')) {
                $return = $return
                    ->bcc([
                        config("app.testing_mail")
                    ]);
            }
            return $return;

        }
    }
