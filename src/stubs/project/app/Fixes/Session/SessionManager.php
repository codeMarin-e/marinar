<?php
    namespace App\Fixes\Session;

    use Illuminate\Session\EncryptedStore;
    use \Illuminate\Session\SessionManager as OriginalSessionManager;


    class SessionManager extends OriginalSessionManager {


        /**
         * Build the session instance.
         *
         * @param  \SessionHandlerInterface  $handler
         * @return \Illuminate\Session\Store
         */
        protected function buildSession($handler)
        {
            return $this->config->get('session.encrypt')
                ? $this->buildEncryptedSession($handler)
                : new Store(
                    $this->config->get('session.cookie'),
                    $handler,
                    $id = null,
                    $this->config->get('session.serialization', 'php')
                );
        }

        /**
         * Build the encrypted session instance.
         *
         * @param  \SessionHandlerInterface  $handler
         * @return \Illuminate\Session\EncryptedStore
         */
        protected function buildEncryptedSession($handler)
        {
            return new EncryptedStore(
                $this->config->get('session.cookie'),
                $handler,
                $this->container['encrypter'],
                $id = null,
                $this->config->get('session.serialization', 'php'),
            );
        }


    }
