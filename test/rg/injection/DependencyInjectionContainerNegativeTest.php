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

include_once 'test_classes.php';

class DependencyInjectionContainerNegativeTest extends \PHPUnit_Framework_TestCase {

    public function testGetInstanceWithInvalidParameterInjectionThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Expected tag @var not found in doc comment.');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassNoParamTypeHint');
    }

    public function testGetInstanceWithPrivateParameterInjectionThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Property two must not be private for property injection.');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassPrivateProperty');
    }

    public function testAbstractInstanceThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Can not instanciate abstract class rg\injection\DICTestAbstractClass');

        $config = new Configuration(null, '');
        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestAbstractClass');
    }

    public function testGetInstanceWithWrongConfiguredParameterThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Invalid argument without class typehint class: [rg\injection\DICTestClassNoTypeHint] method: [__construct] argument: [two]');
        $config = new Configuration(null, '');
        $config->setClassConfig('rg\injection\DICTestClassNoTypeHint', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
            ),
        ));

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint');
    }

    public function testCallNotInjectableMethodThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'not injectable');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $dic->callMethodOnObject($instance, 'getSomethingNotInjectible');
    }

    public function testCallMethodWithoutTypehintOnObjectThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'not injectable');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $dic->callMethodOnObject($instance, 'noTypeHint');
    }

    public function testCallUndefinedMethodThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Method undefined not found in rg\injection\DICTestClassOne');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $dic->callMethodOnObject($instance, 'undefined');
    }

    public function testCallMagicMethodThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'not allowed to call magic method');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $dic->callMethodOnObject($instance, '__get');
    }

    public function testGetInstanceOfNotInjectableClassThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'not injectable');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassNoInject');
    }

    public function testGetInstanceOfClassWithoNoTypeHintThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Invalid argument without class typehint class: [rg\injection\DICTestClassNoTypeHint] method: [__construct] argument: [one]');

        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint');
    }

    public function testUsingNonProviderAsProviderThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Provider class rg\injection\DICTestProvidedInterfaceImpl1 specified in rg\injection\DICTestInvalidProvidedInterface does not implement rg\injection\provider');
        $config = new Configuration(null, '');
        $dic = $this->getContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestInvalidProvidedInterface');
    }

    public function testGetConfiguredObjectInstance() {
        $config = new Configuration(null, '');
        $dic = $this->getContainer($config);

        $expectedInstance = new \stdClass();

        $dic->getConfig()->setClassConfig('rg\injection\DICTestInterfaceDependency', array(
            'instance' => $expectedInstance
        ));

        $actualInstance = $dic->getInstanceOfClass('rg\injection\DICTestInterfaceDependency');

        $this->assertSame($expectedInstance, $actualInstance);
    }

    public function testGetConfiguredObjectInstanceInDependency() {
        $config = new Configuration(null, '');

        $dic = $this->getContainer($config);

        $expectedInstance = new DICTestClassThree();

        $dic->getConfig()->setClassConfig('rg\injection\DICTestClassThree', array(
            'instance' => $expectedInstance
        ));

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instance);

        $this->assertInstanceOf('rg\injection\DICTestClassTwo', $instance->two);
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
