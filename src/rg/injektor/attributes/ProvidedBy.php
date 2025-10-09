<?php

declare(strict_types=1);

namespace rg\injektor\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ProvidedBy
{
    public function __construct(
        public string $className,
        public ?string $named = 'default',
        /** @var Param[] $overwriteParams */
        public array $overwriteParams = [],
    ) {
    }
}