<?php

namespace CFS\LaravelDefer\Tests\Unit;

use CFS\LaravelDefer\DeferredCallable;
use PHPUnit\Framework\TestCase;

class DeferredCallableTest extends TestCase
{
    public function test_can_create_deferred_callable_with_closure()
    {
        $closure = fn () => 'test';
        $deferred = new DeferredCallable($closure, 'test-name', true);

        $this->assertSame($closure, $deferred->closure);
        $this->assertEquals('test-name', $deferred->name);
        $this->assertTrue($deferred->always);
    }

    public function test_can_create_deferred_callable_with_defaults()
    {
        $deferred = new DeferredCallable;

        $this->assertNull($deferred->closure);
        $this->assertNull($deferred->name);
        $this->assertFalse($deferred->always);
    }

    public function test_closure_can_be_executed()
    {
        $executed = false;
        $closure = function () use (&$executed) {
            $executed = true;
        };

        $deferred = new DeferredCallable($closure);
        call_user_func($deferred->closure);

        $this->assertTrue($executed);
    }
}
