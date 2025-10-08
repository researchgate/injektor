<?php

declare(strict_types=1);

namespace rg\injektor\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER)]
class Inject
{
    public function __construct(
        /**
         * This can only be used in the context of a class property or method parameter.
         */
        public ?string $named = null,
        /**
         * This can only be used in the context of a class property or method parameter.
         * @var Param[] $overwriteParams
         */
        public array   $overwriteParams = [],
    ) {
    }
}