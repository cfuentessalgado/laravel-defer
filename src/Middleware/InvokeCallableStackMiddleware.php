<?php

namespace CFS\LaravelDefer\Middleware;

use CFS\LaravelDefer\CallableStack;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvokeCallableStackMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        CallableStack::flush($request, $response);
    }
}
