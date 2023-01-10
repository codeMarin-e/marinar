<?php

    namespace Marinar\Marinar\Fixes\Events;

    use \Illuminate\Events\Dispatcher as BaseDispatcher;

    class Dispatcher extends BaseDispatcher {


        protected function createClassCallable($listener)
        {
            [$class, $method] = is_array($listener)
                ? $listener
                : $this->parseClassCallable($listener);

            if ($class::hasMacro($method)) {
                if ($this->handlerShouldBeQueued($class)) {
                    return $this->createQueuedHandlerCallable($class, $method);
                }

                $listener = $this->container->make($class);

                return $this->handlerShouldBeDispatchedAfterDatabaseTransactions($listener)
                    ? $this->createCallbackForListenerRunningAfterCommits($listener, $method)
                    : [$listener, $method];
            }
            return parent::createClassCallable($listener);
        }

        function loadFromParentObj( $parentObj )
        {
            $objValues = get_object_vars($parentObj); // return array of object values
            foreach($objValues AS $key=>$value)
            {
                $this->$key = $value;
            }
        }
    }
