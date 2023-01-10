<?php
    namespace Marinar\Marinar\Fixes\Session;

    use \Illuminate\Session\EncryptedStore as OriginalEncryptedStore;

    class EncryptedStore extends OriginalEncryptedStore {
        use StoreFix;
    }
