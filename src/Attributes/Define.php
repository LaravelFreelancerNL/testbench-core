<?php

namespace Orchestra\Testbench\Attributes;

use Attribute;
use Orchestra\Testbench\Contracts\Attributes\Resolvable as ResolvableContract;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Define implements ResolvableContract
{
    /**
     * Construct a new attribute.
     *
     * @param  string  $group
     * @param  string  $method
     */
    public function __construct(
        public string $group,
        public string $method
    ) {
        //
    }

    /**
     * Resolve the actual attribute class.
     *
     * @return \Orchestra\Testbench\Contracts\Attributes\Actionable|\Orchestra\Testbench\Contracts\Attributes\Invokable|null
     */
    public function resolve(): ?object
    {
        switch (strtolower($this->group)) {
            case 'env':
                return new DefineEnvironment($this->method);
            case 'db':
                return new DefineDatabase($this->method);
            case 'route':
                return new DefineRoute($this->method);
            default:
                return null;
        }
    }
}
