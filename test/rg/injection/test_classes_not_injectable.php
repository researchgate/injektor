<?php
/*
 * This file is part of rg\injection.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injection;

abstract class DICTestAbstractClass {
}

interface DICTestInterface {
}

/**
 * @implementedBy rg\injection\DICTestAnnotatedInterfaceImpl
 */
interface DICTestAnnotatedInterface {
}

/**
 * @implementedBy default rg\injection\DICTestAnnotatedInterfaceNamedConfigImpl
 * @implementedBy implOne rg\injection\DICTestAnnotatedInterfaceNamedConfigImplOne
 * @implementedBy implTwo rg\injection\DICTestAnnotatedInterfaceNamedConfigImplTwo
 */
interface DICTestAnnotatedInterfaceNamedConfig {

}

/**
 * @providedBy impl1 rg\injection\DICTestProvider {"name" : "impl1"}
 * @providedBy impl2 rg\injection\DICTestProvider {"name" : "impl2"}
 */
interface DICTestProvidedInterface {

}

interface DICTestProvidedInterfaceNoConfig {

}
/**
 * @providedBy rg\injection\DICSimpleTestProvider
 */
interface DICTestSimpleProvidedInterface {

}

/**
 * @providedBy rg\injection\DICTestProvidedInterfaceImpl1
 */
interface DICTestInvalidProvidedInterface {

}