<?php

declare(strict_types=1);

namespace rg\injektor\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ImplementedBy
{
    public function __construct(
        public string $className,
        public ?string $named = 'default',
    ) {
    }
}