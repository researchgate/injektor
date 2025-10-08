<?php

declare(strict_types=1);

namespace rg\injektor\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class Arg
{
    public function __construct(
        public string $name,
        public mixed $value,
    ) {
    }
}