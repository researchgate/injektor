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
use rg\injektor\attributes\ProvidedBy;

#[ImplementedBy(DICTestAttributedInterfaceImpl::class)]
interface DICTestAttributedInterface {

}

#[ImplementedBy(DICTestAttributedInterfaceNamedConfigImpl::class, name: 'default')]
#[ImplementedBy(DICTestAttributedInterfaceNamedConfigImplOne::class, name: 'implOne')]
#[ImplementedBy(DICTestAttributedInterfaceNamedConfigImplTwo::class, name: 'implTwo')]
interface DICTestAttributedInterfaceNamedConfig {

}

#[ProvidedBy(DICTestProviderForAnnotatedInterface::class, name: 'impl1', params: ['name' => 'impl1'])]
#[ProvidedBy(DICTestProviderForAnnotatedInterface::class, name: 'impl2', params: ['name' => 'impl2'])]
interface DICTestProvidedAnnotatedInterface {

}

#[ProvidedBy(DICSimpleTestProviderForAnnotatedInterface::class)]
interface DICTestSimpleProvidedAnnotatedInterface {

}
