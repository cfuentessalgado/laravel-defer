<?php

namespace CFS\LaravelDefer\Tests\Unit;

use CFS\LaravelDefer\CallableStack;
use CFS\LaravelDefer\DeferredCallable;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure helpers are loaded
        require_once __DIR__.'/../../src/helpers.php';

        // Clear the static stack before each test
        $reflection = new \ReflectionClass(CallableStack::class);
        $stackProperty = $reflection->getProperty('stack');
        $stackProperty->setAccessible(true);
        $stackProperty->setValue([]);
    }

    public function test_defer_callable_function_exists()
    {
        $this->assertTrue(function_exists('deferCallable'));
    }

    public function test_defer_callable_function_returns_deferred_callable()
    {
        $closure = fn () => 'test';
        $result = deferCallable($closure, 'test-name', true);

        $this->assertInstanceOf(DeferredCallable::class, $result);
        $this->assertSame($closure, $result->closure);
        $this->assertEquals('test-name', $result->name);
        $this->assertTrue($result->always);
    }

    public function test_defer_callable_function_with_defaults()
    {
        $closure = fn () => 'test';
        $result = deferCallable($closure);

        $this->assertInstanceOf(DeferredCallable::class, $result);
        $this->assertSame($closure, $result->closure);
        $this->assertNotNull($result->name); // Should have generated UUID
        $this->assertFalse($result->always);
    }

    public function test_defer_callable_function_delegates_to_callable_stack()
    {
        $executed = false;

        deferCallable(function () use (&$executed) {
            $executed = true;
        });

        // Manually flush to test the deferred callable was added
        $request = new \Illuminate\Http\Request;
        $response = new \Illuminate\Http\Response('', 200);

        CallableStack::flush($request, $response);

        $this->assertTrue($executed);
    }

    public function test_multiple_defer_callable_calls()
    {
        $executed = [];

        deferCallable(function () use (&$executed) {
            $executed[] = 1;
        });

        deferCallable(function () use (&$executed) {
            $executed[] = 2;
        });

        deferCallable(function () use (&$executed) {
            $executed[] = 3;
        });

        // Manually flush to test all deferred callables were added
        $request = new \Illuminate\Http\Request;
        $response = new \Illuminate\Http\Response('', 200);

        CallableStack::flush($request, $response);

        $this->assertEquals([1, 2, 3], $executed);
    }
}
