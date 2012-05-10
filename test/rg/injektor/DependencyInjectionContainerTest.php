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

class DependencyInjectionContainerTest extends \PHPUnit_Framework_TestCase {

    public function testGetInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance);

        $this->assertInstanceOf('rg\injektor\DICTestClassTwo', $instance->two);
        $this->assertInstanceOf('rg\injektor\DICTestClassThree', $instance->three);
        $this->assertInstanceOf('rg\injektor\DICTestClassThree', $instance->two->three);
        $this->assertInstanceOf('rg\injektor\DICTestClassThree', $instance->getFour());
    }

    public function testGetInstanceOverWriteValues() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instanceTwo = new DICTestClassTwo(new DICTestClassThree());
        $instanceTwo->three = 'foo';

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne', array(
            'two' => $instanceTwo
        ));

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance);

        $this->assertEquals($instanceTwo, $instance->two);
    }

    public function testGetInstanceOverWriteValuesWithNull() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne', array(
            'three' => null
        ));

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance);

        $this->assertNull($instance->three);
    }

    public function testGetInstanceWithParameterInjectionAndDoubledAnnotationTakesFirstOne() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassPropertyDoubledAnnotation');

        $this->assertInstanceOf('rg\injektor\DICTestClassPropertyDoubledAnnotation', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance->two);
    }

    public function testGetConfiguredInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestAbstractClass', array(
            'class' => 'rg\injektor\DICTestClassOneConfigured',
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAbstractClass');

        $this->assertInstanceOf('rg\injektor\DICTestClassOneConfigured', $instance);
    }

    public function testGetDifferentInstancesWithTwoCalls() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceTwo);

        $this->assertFalse($instanceOne === $instanceTwo);
    }

    public function testDontGetSingletonInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassOne', array(
            'singleton' => false
        ));

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceTwo);

        $this->assertTrue($instanceOne !== $instanceTwo);
    }

    public function testGetSingletonInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassOne', array(
            'singleton' => true
        ));

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceTwo);

        $this->assertTrue($instanceOne === $instanceTwo);
    }

    public function testGetDifferentSingletonInstancesBecauseOfDifferentParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHint', array(
            'singleton' => true
        ));

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint', array('one' => 'one', 'two' => 'two'));
        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint', array('one' => 'three', 'two' => 'four'));
        $instanceThree = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint', array('one' => 'one', 'two' => 'two'));

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instanceOne);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instanceTwo);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instanceThree);

        $this->assertFalse($instanceOne === $instanceTwo);
        $this->assertTrue($instanceOne === $instanceThree);
    }

    public function testGetInstanceOfRealSingleton() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestSingleton', array(
            'singleton' => true
        ));
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestSingleton');

        $this->assertInstanceOf('rg\injektor\DICTestSingleton', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance->instance);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance->injectedProperty);
        $this->assertEquals('foo', $instance->foo);
    }

    public function testGetInstanceWithoutConstructor() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoConstructor');

        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance);
    }

    public function testGetInstanceWithConfiguredParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHint', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
                'two' => array(
                    'value' => 123
                )
            ),
        ));

        array(
            'keywords' => array(
                123,
                456
            )
        );

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint');

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals(123, $instance->two);
    }

    public function testGetInstanceWithConfiguredClassParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHint', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
                'two' => array(
                    'class' => 'rg\injektor\DICTestClassOne'
                )
            ),
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint');

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance->two);
    }

    public function testGetInstanceWithConfiguredAndOptionalClassParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHintOptionalArgument', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
            ),
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHintOptionalArgument', array(
        ));

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHintOptionalArgument', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals('bar', $instance->two);
        $this->assertEquals(array(), $instance->ar);
    }

    public function testGetInstanceWithConfiguredAndOptionalClassParametersSomeGiven() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHintOptionalArgument', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
            ),
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHintOptionalArgument', array(
            'ar' => array('abc')
        ));

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHintOptionalArgument', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals('bar', $instance->two);
        $this->assertEquals(array('abc'), $instance->ar);
    }

    public function testGetInstanceWithConfiguredAndDefaultClassParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHint', array(
            'params' => array(
                'two' => array(
                    'class' => 'rg\injektor\DICTestClassOne'
                ),
            ),
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint', array(
            'one' => 'foo'
        ));

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance->two);
    }

    public function testCallMethodOnObject() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $actual = $dic->callMethodOnObject($instance, 'getSomething');

        $this->assertEquals('barfoo', $actual);
    }

    public function testCallMethodWithMixedParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $actual = $dic->callMethodOnObject($instance, 'getSomethingTwo', array(
            'three' => new DICTestClassThree()
        ));

        $this->assertEquals('barfoo', $actual);
    }

    public function testCallMethodWithoutParametersOnObject() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassTwo');

        $actual = $dic->callMethodOnObject($instance, 'getSomething');

        $this->assertEquals('bar', $actual);
    }

    public function testAnnotatedSingleton() {
        $config = new Configuration(null, __DIR__ . '/_factories');
        ;
        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedSingleton');
        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedSingleton');

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedSingleton', $instanceOne);
        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedSingleton', $instanceTwo);
        $this->assertTrue($instanceOne === $instanceTwo);
    }

    public function testAnnotatedImplementedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedInterface');

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterfaceImpl', $instance);
    }

    public function testAnnotatedImplementedByDefault() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedInterfaceNamedConfig');

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterfaceNamedConfigImpl', $instance);
    }

    public function testNamedAnnotation() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestAnnotatedInterface', array(
            'named' => array(
                'implOne' => 'rg\injektor\DICTestAnnotatedInterfaceImplOne',
                'implTwo' => 'rg\injektor\DICTestAnnotatedInterfaceImplTwo'
            )
        ));
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamed');

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterfaceImplOne', $instance->one);
        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterfaceImplTwo', $instance->two);
    }

    public function testNamedAnnotationWithAnnotationConfiguration() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamedConfig');

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterfaceNamedConfigImplOne', $instance->one);
        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterfaceNamedConfigImplTwo', $instance->two);
    }

    public function testNamedAnnotationAtMethodCall() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestAnnotatedInterface', array(
            'named' => array(
                'implOne' => 'rg\injektor\DICTestAnnotatedInterfaceImplTwo',
                'implTwo' => 'rg\injektor\DICTestAnnotatedInterfaceImplOne'
            )
        ));
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamed');

        $returnValue = $dic->callMethodOnObject($instance, 'doSomething');

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterfaceImplTwo', $returnValue);
    }

    public function testNamedAnnotationAtMethodCallWithConfiguration() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamedConfig');

        $returnValue = $dic->callMethodOnObject($instance, 'doSomething');

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterfaceNamedConfigImplOne', $returnValue);
    }

    public function testMethodAspects() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = new DICTestAspects(new DICTestAnnotatedSingleton(), null);

        $returnValue = $dic->callMethodOnObject($instance, 'aspectFunction', array(
            'two' => 'some value'
        ));

        $expectedArgument = array(
            array(
                'some value',
                array(
                    'one' => '1',
                    'two' => 'bar',
                ),
                'rg\\injektor\\DICTestAspects',
                'aspectFunction',
            ),
            array(
            ),
            'rg\\injektor\\DICTestAspects',
            'aspectFunction',
        );
        $this->assertEquals($expectedArgument, $instance->two);

        $expectedReturnValue = array(
            'foo',
            array(
                'foo' => 'bar'
            ),
            'rg\injektor\DICTestAspects',
            'aspectFunction'
        );

        $this->assertEquals($expectedReturnValue, $returnValue);
    }

    public function testConstructorAspects() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\\injektor\\DICTestAspects', array(
            'two' => 'some value'
        ));

        $expectedArgument = array(
            0 =>
            array(
                0 => 'some value',
                1 =>
                array(
                    'one' => '1',
                    'two' => 'bar',
                ),
                2 => 'rg\\injektor\\DICTestAspects',
                3 => '__construct',
            ),
            1 =>
            array(
            ),
            2 => 'rg\\injektor\\DICTestAspects',
            3 => '__construct',
        );

        $this->assertInstanceOf('rg\\injektor\\DICTestAspects', $instance[0]);
        $this->assertInstanceOf('rg\\injektor\\DICTestAnnotatedSingleton', $instance[0]->cone);
        $this->assertEquals($expectedArgument, $instance[0]->ctwo);
        $this->assertEquals(array('foo' => 'bar'), $instance[1]);
        $this->assertEquals('rg\\injektor\\DICTestAspects', $instance[2]);
        $this->assertEquals('__construct', $instance[3]);
    }

    public function testInterceptAspectsConstructor() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\\injektor\\DICTestInterceptAspectClass', array(
            'two' => 'some value'
        ));

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedSingleton', $instance[0]['one']);
        $this->assertEquals('some value', $instance[0]['two']);
        $this->assertEquals(array(
            'one' => 1,
            'two' => 'bar'
        ), $instance[1]);
        $this->assertEquals('rg\injektor\DICTestInterceptAspectClass', $instance[2]);
        $this->assertEquals('__construct', $instance[3]);
        $this->assertEquals(false, $instance[4]);
    }

    public function testInterceptAspectsMethod() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);
        $instance = new DICTestInterceptAspectClass(new DICTestAnnotatedSingleton(), 'some value');
        $result = $dic->callMethodOnObject($instance, 'aspectFunction', array(
            'two' => 'some value'
        ));

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedSingleton', $result[0]['one']);
        $this->assertEquals('some value', $result[0]['two']);
        $this->assertEquals(array(
            'one' => 1,
            'two' => 'bar'
        ), $result[1]);
        $this->assertEquals('rg\injektor\DICTestInterceptAspectClass', $result[2]);
        $this->assertEquals('aspectFunction', $result[3]);
        $this->assertEquals(false, $result[4]);
    }

    public function testSimpleProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestSimpleProvidedInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestSimpleProvidedInterfaceDependency');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedInterfaceDependency', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedDecorator', $instance->dependency);

        $this->assertNull($instance->dependency->getProvidedClass());
    }

    public function testSimpleProvidedByOfDependency() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestSimpleProvidedImplDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestSimpleProvidedImplDependency');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedImplDependency', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedDecorator', $instance->providedInterface);

        $this->assertNull($instance->providedInterface->getProvidedClass());
    }

    public function testSimpleProvidedByOfDependencyOnMethod() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestSimpleProvidedImplDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestSimpleProvidedImplDependency');

        $actual = $dic->callMethodOnObject($instance, 'someMethod');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedDecorator', $actual);

        $this->assertNull($actual->getProvidedClass());
    }

    public function testNamedProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestNamedProvidedImpl1Dependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamedProvidedImpl1Dependency');

        $this->assertInstanceOf('rg\injektor\DICTestNamedProvidedImpl1Dependency', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedDecorator', $instance->providedInterface1);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedDecorator', $instance->providedInterface2);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedInterfaceImpl1', $instance->providedInterface1->getProvidedClass());
        $this->assertInstanceOf('rg\injektor\DICTestProvidedInterfaceImpl2', $instance->providedInterface2->getProvidedClass());

    }

    public function testNamedConfiguredProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestProvidedInterface', array(
            'namedProviders' => array(
                'impl1' => array(
                    'class' => 'rg\injektor\DICTestProvider',
                    'parameters' => array('name' => 'impl1'),
                ),
            ),
        ));
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestInterfaceDependencyTwo');

        $this->assertInstanceOf('rg\injektor\DICTestProvidedDecorator', $instance->dependency);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedInterfaceImpl1', $instance->dependency->getProvidedClass());
    }

    public function testNamedConfiguredProvidedByNoAnnotation() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestProvidedInterfaceNoConfig', array(
            'namedProviders' => array(
                'impl1' => array(
                    'class' => 'rg\injektor\DICTestProviderNoAnnotation',
                    'parameters' => array('name' => 'impl1Param'),
                ),
            ),
        ));
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestInterfaceDependencyTwoNoAnnotation');

        $this->assertInstanceOf('rg\injektor\DICTestInterfaceDependencyTwoNoAnnotation', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedInterfaceNoConfigImpl', $instance->dependency);
        $this->assertEquals('impl1Param', $instance->dependency->name);
    }

    public function testConfiguredProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestInterface', array(
            'provider' => array(
                'class' => 'rg\injektor\DICSimpleTestProvider',
            ),
        ));
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestInterfaceDependency');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedDecorator', $instance->dependency);

        $this->assertNull($instance->dependency->getProvidedClass());
    }

    public function testConfiguredProvidedByDirectAccess() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassTwo', array(
            'provider' => array(
                'class' => 'rg\injektor\DICSimpleTestProvider',
            ),
        ));

        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassTwo');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedDecorator', $instance);
    }

    public function testConfiguredProvidedByWithParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestInterface', array(
            'provider' => array(
                'class' => 'rg\injektor\DICSimpleTestProvider',
                'parameters' => array(
                    'name' => 'impl1'
                )
            ),
        ));
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestInterfaceDependency');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedDecorator', $instance->dependency);

        $this->assertInstanceOf('rg\injektor\DICTestProvidedInterfaceImpl1', $instance->dependency->getProvidedClass());
    }

    public function testDICTestAnnotatedInterfacePropertyInjection() {
        $config = new Configuration(null, __DIR__ . '/_factories');
        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedInterfacePropertyInjection');

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterfaceImpl', $instance->dependency);
    }

    public function testArgumentInjectionWithParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassArgumentsWithParameters');

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instance->class);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instance->injectedProperty);
        $this->assertEquals('foo', $instance->class->one);
        $this->assertEquals('bar', $instance->class->two);
        $this->assertEquals('foo', $instance->injectedProperty->one);
        $this->assertEquals('bar', $instance->injectedProperty->two);

        $dic->callMethodOnObject($instance, 'someMethod');

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instance->methodClass);
        $this->assertEquals('foo', $instance->methodClass->one);
        $this->assertEquals('bar', $instance->methodClass->two);
    }

    public function testProvidedArgumentInjectionWithParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICProvidedTestClassArgumentsWithParameters');

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassNoTypeHint', $instance->class);
        $this->assertEquals('foof', $instance->class->one);
        $this->assertEquals('barf', $instance->class->two);
        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassNoTypeHint', $instance->injectedProperty);
        $this->assertEquals('foof', $instance->injectedProperty->one);
        $this->assertEquals('barf', $instance->injectedProperty->two);

        $dic->callMethodOnObject($instance, 'someMethod');

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassNoTypeHint', $instance->methodClass);
        $this->assertEquals('foof', $instance->methodClass->one);
        $this->assertEquals('barf', $instance->methodClass->two);
    }

    public function testNamedProvidedByPropertyInjectionDefault() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICProvidedTestClassNoTypeHintNamedUserDefault');

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassNoTypeHintNamedUserDefault', $instance);

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassNoTypeHint', $instance->provided);

        $this->assertEquals('1f', $instance->provided->one);
        $this->assertEquals('2f', $instance->provided->two);
    }

    public function testNamedProvidedByPropertyInjectionNonDefault() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICProvidedTestClassNoTypeHintNamedUserSomeName');

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassNoTypeHintNamedUserSomeName', $instance);

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassNoTypeHint', $instance->provided);

        $this->assertEquals('3f', $instance->provided->one);
        $this->assertEquals('4f', $instance->provided->two);
    }
    /**
     * @param Configuration $config
     * @return DependencyInjectionContainer
     */
    public function getContainer(Configuration $config) {
        return new DependencyInjectionContainer($config);
    }
}
