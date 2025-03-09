<?php

namespace CFS\LaravelDefer;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CallableStack
{
    /**
     * The stack of deferred callables.
     *
     * @var array
     */
    protected static $stack = [];

    /**
     * Defer the given callable until the application is done handling the current request.
     */
    public static function defer(?callable $callback = null, ?string $name = null, bool $always = false): DeferredCallable
    {
        $deferred = new DeferredCallable(
            closure: $callback,
            always: $always,
            name: $name ?? Str::uuid(),
        );

        static::$stack[] = $deferred;

        return $deferred;
    }

    public static function flush(Request $request, Response $response)
    {
        foreach (static::$stack as $deferred) {
            if ($deferred->always || $response->status() < 400) {
                call_user_func($deferred->closure);
            }
        }

        static::$stack = [];
    }
}
