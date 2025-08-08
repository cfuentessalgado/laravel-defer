<?php

namespace CFS\LaravelDefer\Tests\Feature;

use CFS\LaravelDefer\CallableStack;
use CFS\LaravelDefer\Middleware\InvokeCallableStackMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear the static stack before each test
        $reflection = new \ReflectionClass(CallableStack::class);
        $stackProperty = $reflection->getProperty('stack');
        $stackProperty->setAccessible(true);
        $stackProperty->setValue([]);
    }

    public function test_middleware_passes_request_through()
    {
        $middleware = new InvokeCallableStackMiddleware;
        $request = new Request;

        $next = function ($req) {
            return new Response('test response');
        };

        $response = $middleware->handle($request, $next);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('test response', $response->getContent());
    }

    public function test_middleware_executes_deferred_callbacks_on_terminate()
    {
        $executed = false;

        CallableStack::defer(function () use (&$executed) {
            $executed = true;
        });

        $middleware = new InvokeCallableStackMiddleware;
        $request = new Request;
        $response = new Response('', 200);

        $middleware->terminate($request, $response);

        $this->assertTrue($executed);
    }

    public function test_middleware_respects_response_status_for_execution()
    {
        $executed = false;

        CallableStack::defer(function () use (&$executed) {
            $executed = true;
        }, null, false); // not always

        $middleware = new InvokeCallableStackMiddleware;
        $request = new Request;
        $response = new Response('', 500);

        $middleware->terminate($request, $response);

        $this->assertFalse($executed);
    }

    public function test_middleware_executes_always_callbacks_regardless_of_status()
    {
        $executed = false;

        CallableStack::defer(function () use (&$executed) {
            $executed = true;
        }, null, true); // always

        $middleware = new InvokeCallableStackMiddleware;
        $request = new Request;
        $response = new Response('', 500);

        $middleware->terminate($request, $response);

        $this->assertTrue($executed);
    }

    public function test_full_request_lifecycle()
    {
        $executed = [];

        // Simulate adding deferred callbacks during request
        CallableStack::defer(function () use (&$executed) {
            $executed[] = 'callback1';
        });

        CallableStack::defer(function () use (&$executed) {
            $executed[] = 'callback2';
        }, null, true);

        $middleware = new InvokeCallableStackMiddleware;
        $request = new Request;

        // Handle request
        $next = function ($req) {
            return new Response('success', 200);
        };

        $response = $middleware->handle($request, $next);

        // At this point, callbacks should not have executed yet
        $this->assertEmpty($executed);

        // Terminate should execute the callbacks
        $middleware->terminate($request, $response);

        $this->assertEquals(['callback1', 'callback2'], $executed);
    }
}
