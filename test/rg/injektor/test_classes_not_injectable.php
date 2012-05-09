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

abstract class DICTestAbstractClass {

}

interface DICTestInterface {

}

/**
 * @implementedBy rg\injektor\DICTestAnnotatedInterfaceImpl
 */
interface DICTestAnnotatedInterface {

}

/**
 * @implementedBy default rg\injektor\DICTestAnnotatedInterfaceNamedConfigImpl
 * @implementedBy implOne rg\injektor\DICTestAnnotatedInterfaceNamedConfigImplOne
 * @implementedBy implTwo rg\injektor\DICTestAnnotatedInterfaceNamedConfigImplTwo
 */
interface DICTestAnnotatedInterfaceNamedConfig {

}

/**
 * @providedBy impl1 rg\injektor\DICTestProvider {"name" : "impl1"}
 * @providedBy impl2 rg\injektor\DICTestProvider {"name" : "impl2"}
 */
interface DICTestProvidedInterface {

}

interface DICTestProvidedInterfaceNoConfig {

}

/**
 * @providedBy rg\injektor\DICSimpleTestProvider
 */
interface DICTestSimpleProvidedInterface {

}

/**
 * @providedBy rg\injektor\DICTestProvidedInterfaceImpl1
 */
interface DICTestInvalidProvidedInterface {

}