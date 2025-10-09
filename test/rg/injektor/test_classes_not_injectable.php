<?php
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injektor;

use rg\injektor\attributes\ImplementedBy;
use rg\injektor\attributes\Param;
use rg\injektor\attributes\ProvidedBy;

abstract class DICTestAbstractClass {

}

interface DICTestInterface {

}

#[ImplementedBy(className: DICTestAnnotatedInterfaceImpl::class)]
interface DICTestAnnotatedInterface {

}

#[ImplementedBy(className: DICTestAnnotatedInterfaceNamedConfigImpl::class)]
#[ImplementedBy(className: DICTestAnnotatedInterfaceNamedConfigImplOne::class, named: 'implOne')]
#[ImplementedBy(className: DICTestAnnotatedInterfaceNamedConfigImplTwo::class, named: 'implTwo')]
interface DICTestAnnotatedInterfaceNamedConfig {

}

#[ProvidedBy(className: DICTestProvider::class, named: 'impl1', overwriteParams: [
    new Param('name', 'impl1')
])]
#[ProvidedBy(className: DICTestProvider::class, named: 'impl2', overwriteParams: [
    new Param('name', 'impl2')
])]
interface DICTestProvidedInterface {

}

interface DICTestProvidedInterfaceNoConfig {

}

#[ProvidedBy(className: DICSimpleTestProvider::class)]
interface DICTestSimpleProvidedInterface {

}

#[ProvidedBy(className: DICTestProvidedInterfaceImpl1::class)]
interface DICTestInvalidProvidedInterface {

}