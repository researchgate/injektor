<?php

declare(strict_types=1);

namespace rg\injektor\attributes;

/**
 * This class represents a parameter with a name and a value in the context of #[Inject(overwriteParams: [])] attribute.
 */
class Param
{
    public function __construct(
        public string $name,
        public mixed $value,
    ) {
    }
}