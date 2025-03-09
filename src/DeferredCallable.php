<?php

namespace CFS\LaravelDefer;

class DeferredCallable
{
    public function __construct(
        public ?callable $closure = null,
        public ?string $name = null,
        public bool $always = false,

    ) {}
}
