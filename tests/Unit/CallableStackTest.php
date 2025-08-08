<?php

namespace CFS\LaravelDefer\Tests\Unit;

use CFS\LaravelDefer\CallableStack;
use CFS\LaravelDefer\DeferredCallable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class CallableStackTest extends TestCase
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

    public function test_defer_adds_callable_to_stack()
    {
        $closure = fn () => 'test';
        $deferred = CallableStack::defer($closure, 'test-name', true);

        $this->assertInstanceOf(DeferredCallable::class, $deferred);
        $this->assertSame($closure, $deferred->closure);
        $this->assertEquals('test-name', $deferred->name);
        $this->assertTrue($deferred->always);
    }

    public function test_defer_generates_uuid_when_no_name_provided()
    {
        $closure = fn () => 'test';
        $deferred = CallableStack::defer($closure);

        $this->assertNotNull($deferred->name);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $deferred->name);
    }

    public function test_flush_executes_closures_on_success_response()
    {
        $executed1 = false;
        $executed2 = false;

        CallableStack::defer(function () use (&$executed1) {
            $executed1 = true;
        });

        CallableStack::defer(function () use (&$executed2) {
            $executed2 = true;
        });

        $request = new Request;
        $response = new Response('', 200);

        CallableStack::flush($request, $response);

        $this->assertTrue($executed1);
        $this->assertTrue($executed2);
    }

    public function test_flush_skips_closures_on_error_response_unless_always()
    {
        $executed1 = false;
        $executed2 = false;

        CallableStack::defer(function () use (&$executed1) {
            $executed1 = true;
        }, null, false);

        CallableStack::defer(function () use (&$executed2) {
            $executed2 = true;
        }, null, true); // always execute

        $request = new Request;
        $response = new Response('', 500);

        CallableStack::flush($request, $response);

        $this->assertFalse($executed1);
        $this->assertTrue($executed2);
    }

    public function test_flush_clears_stack_after_execution()
    {
        CallableStack::defer(fn () => null);

        $request = new Request;
        $response = new Response('', 200);

        CallableStack::flush($request, $response);

        // Add another callable to verify stack was cleared
        $executed = false;
        CallableStack::defer(function () use (&$executed) {
            $executed = true;
        });

        CallableStack::flush($request, $response);

        $this->assertTrue($executed);
    }

    public function test_multiple_defers_accumulate_in_stack()
    {
        $executed = [];

        CallableStack::defer(function () use (&$executed) {
            $executed[] = 1;
        });

        CallableStack::defer(function () use (&$executed) {
            $executed[] = 2;
        });

        CallableStack::defer(function () use (&$executed) {
            $executed[] = 3;
        });

        $request = new Request;
        $response = new Response('', 200);

        CallableStack::flush($request, $response);

        $this->assertEquals([1, 2, 3], $executed);
    }
}
