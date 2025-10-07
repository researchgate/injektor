<?php

declare(strict_types=1);

namespace rg\injektor\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Inject
{
}