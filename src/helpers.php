<?php

use CFS\LaravelDefer\CallableStack;
use CFS\LaravelDefer\DeferredCallable;

if (! function_exists('deferCallable')) {

    /**
     * Defer execution of the given callback.
     */
    function deferCallable(?callable $callback, ?string $name = null, bool $always = false): DeferredCallable
    {
        return CallableStack::defer($callback, $name, $always);
    }
}
