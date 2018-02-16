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

include_once 'test_classes.php';

class DependencyInjectionContainerNegativeTest extends \PHPUnit\Framework\TestCase {

    public function testGetInstanceWithInvalidParameterInjectionThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'Expected tag @var not found in doc comment.');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injektor\DICTestClassNoParamTypeHint');
    }

    public function testGetInstanceWithPrivateParameterInjectionThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'Property two must not be private for property injection.');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injektor\DICTestClassPrivateProperty');
    }

    public function testAbstractInstanceThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'Can not instantiate abstract class rg\injektor\DICTestAbstractClass');

        $config = new Configuration(null, '');
        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injektor\DICTestAbstractClass');
    }

    public function testGetInstanceWithWrongConfiguredParameterThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'Invalid argument without class typehint class: [rg\injektor\DICTestClassNoTypeHint] method: [__construct] argument: [two]');
        $config = new Configuration(null, '');
        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHint', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
            ),
        ));

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint');
    }

    public function testCallNotInjectableMethodThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'not injectable');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $dic->callMethodOnObject($instance, 'getSomethingNotInjectible');
    }

    public function testCallMethodWithoutTypehintOnObjectThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'not injectable');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $dic->callMethodOnObject($instance, 'noTypeHint');
    }

    public function testCallUndefinedMethodThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'Method undefined not found in rg\injektor\DICTestClassOne');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $dic->callMethodOnObject($instance, 'undefined');
    }

    public function testCallMagicMethodThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'not allowed to call magic method');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $dic->callMethodOnObject($instance, '__get');
    }

    public function testGetInstanceOfNotInjectableClassThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'not injectable');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injektor\DICTestClassNoInject');
    }

    public function testGetInstanceOfClassWithoNoTypeHintThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'Invalid argument without class typehint class: [rg\injektor\DICTestClassNoTypeHint] method: [__construct] argument: [one]');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint');
    }

    public function testUsingNonProviderAsProviderThrowsException() {
        $this->setExpectedException('rg\injektor\InjectionException', 'Provider class rg\injektor\DICTestProvidedInterfaceImpl1 specified in rg\injektor\DICTestInvalidProvidedInterface does not implement rg\injektor\provider');
        $config = new Configuration(null, '');
        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injektor\DICTestInvalidProvidedInterface');
    }

    public function testGetConfiguredObjectInstance() {
        $config = new Configuration(null, '');
        $dic = $this->getContainer($config);

        $expectedInstance = new \stdClass();

        $dic->getConfig()->setClassConfig('rg\injektor\DICTestInterfaceDependency', array(
            'instance' => $expectedInstance
        ));

        $actualInstance = $dic->getInstanceOfClass('rg\injektor\DICTestInterfaceDependency');

        $this->assertSame($expectedInstance, $actualInstance);
    }

    public function testGetConfiguredObjectInstanceInDependency() {
        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $expectedInstance = new DICTestClassThree();

        $dic->getConfig()->setClassConfig('rg\injektor\DICTestClassThree', array(
            'instance' => $expectedInstance
        ));

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance);

        $this->assertInstanceOf('rg\injektor\DICTestClassTwo', $instance->two);
        $this->assertSame($expectedInstance, $instance->three);
        $this->assertSame($expectedInstance, $instance->two->three);
        $this->assertSame($expectedInstance, $instance->getFour());
    }

    /**
     * @param Configuration $config
     * @return DependencyInjectionContainer
     */
    public function getContainer(Configuration $config) {
        return new DependencyInjectionContainer($config);
    }
}
