<?php
    namespace App\Traits;

    use App\Notifications\ResetPassword;
    use App\Notifications\VerifyEmail;
    use Illuminate\Database\Eloquent\Casts\Attribute;
    use Illuminate\Support\Facades\Hash;
    use Laravel\Fortify\TwoFactorAuthenticatable;
    use Spatie\Permission\Traits\HasRoles;

    trait MarinarUserTrait {

        use AddVariable;
        use MarinarUserAddressableTrait;
        use HasRoles;
        use TwoFactorAuthenticatable;
        use MacroableModel;

        public static function bootMarinarUserTrait() {
            static::$addonFillable[] = 'email_for_confirm';
            static::$addonFillable[] = 'type';
            static::$addonFillable[] = 'active';
            static::$addonFillable[] = 'site_id';
        }

        public function sendEmailVerificationNotification() {
            $this->notify(new VerifyEmail);
        }

        public function sendPasswordResetNotification($token) {
            $this->notify(new ResetPassword($token));
        }

        public static function cryptPassword($password) {
            return Hash::make($password);
        }


        /**
         * Get the email address that should be used for verification.
         *
         * @return string
         */
        public function getEmailForVerification() {
            return $this->email_for_confirm;
        }

//    protected function setEmailAttribute($value) {
//        dd($value);
//        $this->getAddress()->update(['email' => $value]);
//        return $value;
//    }

        protected function email() : Attribute {
            return Attribute::set(function($value) {
                if($this->exists)
                    $this->getAddress()->update(['email' => $value]);
                return $value;
            });
        }

        /**
         * Mark the given user's email as verified.
         *
         * @return bool
         */
        public function markEmailAsVerified()
        {
            $this->forceFill([
                'email' => $this->email_for_confirm,
                'email_for_confirm' => null,
                'email_verified_at' => $this->freshTimestamp(),
            ])->save();

            return $this;
        }
    }
