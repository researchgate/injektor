<?php
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injektor\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class ImplementedBy
{
    public function __construct(public string $className, public ?string $name = null)
    {
    }
}
