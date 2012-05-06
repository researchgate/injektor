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

class DependencyInjectionContainerTest extends \PHPUnit_Framework_TestCase {

    public function testGetInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instance);

        $this->assertInstanceOf('rg\injection\DICTestClassTwo', $instance->two);
        $this->assertInstanceOf('rg\injection\DICTestClassThree', $instance->three);
        $this->assertInstanceOf('rg\injection\DICTestClassThree', $instance->two->three);
        $this->assertInstanceOf('rg\injection\DICTestClassThree', $instance->getFour());
    }

    public function testGetInstanceOverWriteValues() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instanceTwo = new DICTestClassTwo(new DICTestClassThree());
        $instanceTwo->three = 'foo';

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne', array(
            'two' => $instanceTwo
        ));

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instance);

        $this->assertEquals($instanceTwo, $instance->two);
    }

    public function testGetInstanceOverWriteValuesWithNull() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne', array(
            'three' => null
        ));

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instance);

        $this->assertNull($instance->three);
    }

    public function testGetInstanceWithParameterInjectionAndDoubledAnnotationTakesFirstOne() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassPropertyDoubledAnnotation');

        $this->assertInstanceOf('rg\injection\DICTestClassPropertyDoubledAnnotation', $instance);
        $this->assertInstanceOf('rg\injection\DICTestClassNoConstructor', $instance->two);
    }

    public function testGetConfiguredInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestAbstractClass', array(
            'class' => 'rg\injection\DICTestClassOneConfigured',
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestAbstractClass');

        $this->assertInstanceOf('rg\injection\DICTestClassOneConfigured', $instance);
    }

    public function testGetDifferentInstancesWithTwoCalls() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceTwo);

        $this->assertFalse($instanceOne === $instanceTwo);
    }

    public function testDontGetSingletonInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestClassOne', array(
            'singleton' => false
        ));

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceTwo);

        $this->assertTrue($instanceOne !== $instanceTwo);
    }

    public function testGetSingletonInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestClassOne', array(
            'singleton' => true
        ));

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceTwo);

        $this->assertTrue($instanceOne === $instanceTwo);
    }

    public function testGetDifferentSingletonInstancesBecauseOfDifferentParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestClassNoTypeHint', array(
            'singleton' => true
        ));

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint', array('one' => 'one', 'two' => 'two'));
        $instanceTwo = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint', array('one' => 'three', 'two' => 'four'));
        $instanceThree = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint', array('one' => 'one', 'two' => 'two'));

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instanceOne);
        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instanceTwo);
        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instanceThree);

        $this->assertFalse($instanceOne === $instanceTwo);
        $this->assertTrue($instanceOne === $instanceThree);
    }

    public function testGetInstanceOfRealSingleton() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestSingleton', array(
            'singleton' => true
        ));
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestSingleton');

        $this->assertInstanceOf('rg\injection\DICTestSingleton', $instance);
        $this->assertInstanceOf('rg\injection\DICTestClassNoConstructor', $instance->instance);
        $this->assertInstanceOf('rg\injection\DICTestClassNoConstructor', $instance->injectedProperty);
        $this->assertEquals('foo', $instance->foo);
    }

    public function testGetInstanceWithoutConstructor() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoConstructor');

        $this->assertInstanceOf('rg\injection\DICTestClassNoConstructor', $instance);
    }

    public function testGetInstanceWithConfiguredParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestClassNoTypeHint', array(
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

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint');

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals(123, $instance->two);
    }

    public function testGetInstanceWithConfiguredClassParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestClassNoTypeHint', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
                'two' => array(
                    'class' => 'rg\injection\DICTestClassOne'
                )
            ),
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint');

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instance->two);
    }

    public function testGetInstanceWithConfiguredAndOptionalClassParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestClassNoTypeHintOptionalArgument', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
            ),
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHintOptionalArgument', array(
        ));

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHintOptionalArgument', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals('bar', $instance->two);
        $this->assertEquals(array(), $instance->ar);
    }

    public function testGetInstanceWithConfiguredAndOptionalClassParametersSomeGiven() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestClassNoTypeHintOptionalArgument', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
            ),
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHintOptionalArgument', array(
            'ar' => array('abc')
        ));

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHintOptionalArgument', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals('bar', $instance->two);
        $this->assertEquals(array('abc'), $instance->ar);
    }

    public function testGetInstanceWithConfiguredAndDefaultClassParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestClassNoTypeHint', array(
            'params' => array(
                'two' => array(
                    'class' => 'rg\injection\DICTestClassOne'
                ),
            ),
        ));

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint', array(
            'one' => 'foo'
        ));

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instance->two);
    }

    public function testCallMethodOnObject() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $actual = $dic->callMethodOnObject($instance, 'getSomething');

        $this->assertEquals('barfoo', $actual);
    }

    public function testCallMethodWithMixedParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $actual = $dic->callMethodOnObject($instance, 'getSomethingTwo', array(
            'three' => new DICTestClassThree()
        ));

        $this->assertEquals('barfoo', $actual);
    }

    public function testCallMethodWithoutParametersOnObject() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassTwo');

        $actual = $dic->callMethodOnObject($instance, 'getSomething');

        $this->assertEquals('bar', $actual);
    }

    public function testAnnotatedSingleton() {
        $config = new Configuration(null, __DIR__ . '/_factories');
        ;
        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injection\DICTestAnnotatedSingleton');
        $instanceTwo = $dic->getInstanceOfClass('rg\injection\DICTestAnnotatedSingleton');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedSingleton', $instanceOne);
        $this->assertInstanceOf('rg\injection\DICTestAnnotatedSingleton', $instanceTwo);
        $this->assertTrue($instanceOne === $instanceTwo);
    }

    public function testAnnotatedImplementedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestAnnotatedInterface');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceImpl', $instance);
    }

    public function testAnnotatedImplementedByDefault() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestAnnotatedInterfaceNamedConfig');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceNamedConfigImpl', $instance);
    }

    public function testNamedAnnotation() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestAnnotatedInterface', array(
            'named' => array(
                'implOne' => 'rg\injection\DICTestAnnotatedInterfaceImplOne',
                'implTwo' => 'rg\injection\DICTestAnnotatedInterfaceImplTwo'
            )
        ));
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestNamed');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceImplOne', $instance->one);
        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceImplTwo', $instance->two);
    }

    public function testNamedAnnotationWithAnnotationConfiguration() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestNamedConfig');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceNamedConfigImplOne', $instance->one);
        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceNamedConfigImplTwo', $instance->two);
    }

    public function testNamedAnnotationAtMethodCall() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestAnnotatedInterface', array(
            'named' => array(
                'implOne' => 'rg\injection\DICTestAnnotatedInterfaceImplTwo',
                'implTwo' => 'rg\injection\DICTestAnnotatedInterfaceImplOne'
            )
        ));
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestNamed');

        $returnValue = $dic->callMethodOnObject($instance, 'doSomething');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceImplTwo', $returnValue);
    }

    public function testNamedAnnotationAtMethodCallWithConfiguration() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestNamedConfig');

        $returnValue = $dic->callMethodOnObject($instance, 'doSomething');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceNamedConfigImplOne', $returnValue);
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
                'rg\\injection\\DICTestAspects',
                'aspectFunction',
            ),
            array(
            ),
            'rg\\injection\\DICTestAspects',
            'aspectFunction',
        );
        $this->assertEquals($expectedArgument, $instance->two);

        $expectedReturnValue = array(
            'foo',
            array(
                'foo' => 'bar'
            ),
            'rg\injection\DICTestAspects',
            'aspectFunction'
        );

        $this->assertEquals($expectedReturnValue, $returnValue);
    }

    public function testConstructorAspects() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\\injection\\DICTestAspects', array(
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
                2 => 'rg\\injection\\DICTestAspects',
                3 => '__construct',
            ),
            1 =>
            array(
            ),
            2 => 'rg\\injection\\DICTestAspects',
            3 => '__construct',
        );

        $this->assertInstanceOf('rg\\injection\\DICTestAspects', $instance[0]);
        $this->assertInstanceOf('rg\\injection\\DICTestAnnotatedSingleton', $instance[0]->cone);
        $this->assertEquals($expectedArgument, $instance[0]->ctwo);
        $this->assertEquals(array('foo' => 'bar'), $instance[1]);
        $this->assertEquals('rg\\injection\\DICTestAspects', $instance[2]);
        $this->assertEquals('__construct', $instance[3]);
    }

    public function testInterceptAspectsConstructor() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\\injection\\DICTestInterceptAspectClass', array(
            'two' => 'some value'
        ));

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedSingleton', $instance[0]['one']);
        $this->assertEquals('some value', $instance[0]['two']);
        $this->assertEquals(array(
            'one' => 1,
            'two' => 'bar'
        ), $instance[1]);
        $this->assertEquals('rg\injection\DICTestInterceptAspectClass', $instance[2]);
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

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedSingleton', $result[0]['one']);
        $this->assertEquals('some value', $result[0]['two']);
        $this->assertEquals(array(
            'one' => 1,
            'two' => 'bar'
        ), $result[1]);
        $this->assertEquals('rg\injection\DICTestInterceptAspectClass', $result[2]);
        $this->assertEquals('aspectFunction', $result[3]);
        $this->assertEquals(false, $result[4]);
    }

    public function testSimpleProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestSimpleProvidedInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestSimpleProvidedInterfaceDependency');

        $this->assertInstanceOf('rg\injection\DICTestSimpleProvidedInterfaceDependency', $instance);
        $this->assertInstanceOf('rg\injection\DICTestSimpleProvidedDecorator', $instance->dependency);

        $this->assertNull($instance->dependency->getProvidedClass());
    }

    public function testSimpleProvidedByOfDependency() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestSimpleProvidedImplDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestSimpleProvidedImplDependency');

        $this->assertInstanceOf('rg\injection\DICTestSimpleProvidedImplDependency', $instance);
        $this->assertInstanceOf('rg\injection\DICTestSimpleProvidedDecorator', $instance->providedInterface);

        $this->assertNull($instance->providedInterface->getProvidedClass());
    }

    public function testSimpleProvidedByOfDependencyOnMethod() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestSimpleProvidedImplDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestSimpleProvidedImplDependency');

        $actual = $dic->callMethodOnObject($instance, 'someMethod');

        $this->assertInstanceOf('rg\injection\DICTestSimpleProvidedDecorator', $actual);

        $this->assertNull($actual->getProvidedClass());
    }

    public function testNamedProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestNamedProvidedImpl1Dependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestNamedProvidedImpl1Dependency');

        $this->assertInstanceOf('rg\injection\DICTestNamedProvidedImpl1Dependency', $instance);
        $this->assertInstanceOf('rg\injection\DICTestProvidedDecorator', $instance->providedInterface1);
        $this->assertInstanceOf('rg\injection\DICTestProvidedDecorator', $instance->providedInterface2);
        $this->assertInstanceOf('rg\injection\DICTestProvidedInterfaceImpl1', $instance->providedInterface1->getProvidedClass());
        $this->assertInstanceOf('rg\injection\DICTestProvidedInterfaceImpl2', $instance->providedInterface2->getProvidedClass());

    }

    public function testNamedConfiguredProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestProvidedInterface', array(
            'namedProviders' => array(
                'impl1' => array(
                    'class' => 'rg\injection\DICTestProvider',
                    'parameters' => array('name' => 'impl1'),
                ),
            ),
        ));
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestInterfaceDependencyTwo');

        $this->assertInstanceOf('rg\injection\DICTestProvidedDecorator', $instance->dependency);
        $this->assertInstanceOf('rg\injection\DICTestProvidedInterfaceImpl1', $instance->dependency->getProvidedClass());
    }

    public function testNamedConfiguredProvidedByNoAnnotation() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestProvidedInterfaceNoConfig', array(
            'namedProviders' => array(
                'impl1' => array(
                    'class' => 'rg\injection\DICTestProviderNoAnnotation',
                    'parameters' => array('name' => 'impl1Param'),
                ),
            ),
        ));
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestInterfaceDependencyTwoNoAnnotation');

        $this->assertInstanceOf('rg\injection\DICTestInterfaceDependencyTwoNoAnnotation', $instance);
        $this->assertInstanceOf('rg\injection\DICTestProvidedInterfaceNoConfigImpl', $instance->dependency);
        $this->assertEquals('impl1Param', $instance->dependency->name);
    }

    public function testConfiguredProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestInterface', array(
            'provider' => array(
                'class' => 'rg\injection\DICSimpleTestProvider',
            ),
        ));
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestInterfaceDependency');

        $this->assertInstanceOf('rg\injection\DICTestSimpleProvidedDecorator', $instance->dependency);

        $this->assertNull($instance->dependency->getProvidedClass());
    }

    public function testConfiguredProvidedByDirectAccess() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestClassTwo', array(
            'provider' => array(
                'class' => 'rg\injection\DICSimpleTestProvider',
            ),
        ));

        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassTwo');

        $this->assertInstanceOf('rg\injection\DICTestSimpleProvidedDecorator', $instance);
    }

    public function testConfiguredProvidedByWithParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injection\DICTestInterface', array(
            'provider' => array(
                'class' => 'rg\injection\DICSimpleTestProvider',
                'parameters' => array(
                    'name' => 'impl1'
                )
            ),
        ));
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestInterfaceDependency');

        $this->assertInstanceOf('rg\injection\DICTestSimpleProvidedDecorator', $instance->dependency);

        $this->assertInstanceOf('rg\injection\DICTestProvidedInterfaceImpl1', $instance->dependency->getProvidedClass());
    }

    public function testDICTestAnnotatedInterfacePropertyInjection() {
        $config = new Configuration(null, __DIR__ . '/_factories');
        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestAnnotatedInterfacePropertyInjection');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceImpl', $instance->dependency);
    }

    public function testArgumentInjectionWithParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassArgumentsWithParameters');

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instance->class);
        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instance->injectedProperty);
        $this->assertEquals('foo', $instance->class->one);
        $this->assertEquals('bar', $instance->class->two);
        $this->assertEquals('foo', $instance->injectedProperty->one);
        $this->assertEquals('bar', $instance->injectedProperty->two);

        $dic->callMethodOnObject($instance, 'someMethod');

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instance->methodClass);
        $this->assertEquals('foo', $instance->methodClass->one);
        $this->assertEquals('bar', $instance->methodClass->two);
    }

    public function testProvidedArgumentInjectionWithParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICProvidedTestClassArgumentsWithParameters');

        $this->assertInstanceOf('rg\injection\DICProvidedTestClassNoTypeHint', $instance->class);
        $this->assertEquals('foof', $instance->class->one);
        $this->assertEquals('barf', $instance->class->two);
        $this->assertInstanceOf('rg\injection\DICProvidedTestClassNoTypeHint', $instance->injectedProperty);
        $this->assertEquals('foof', $instance->injectedProperty->one);
        $this->assertEquals('barf', $instance->injectedProperty->two);

        $dic->callMethodOnObject($instance, 'someMethod');

        $this->assertInstanceOf('rg\injection\DICProvidedTestClassNoTypeHint', $instance->methodClass);
        $this->assertEquals('foof', $instance->methodClass->one);
        $this->assertEquals('barf', $instance->methodClass->two);
    }

    /**
     * @param Configuration $config
     * @return DependencyInjectionContainer
     */
    public function getContainer(Configuration $config) {
        return new DependencyInjectionContainer($config);
    }
}
