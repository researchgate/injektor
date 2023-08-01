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

use PHPUnit\Framework\TestCase;

include_once 'test_classes.php';

class DependencyInjectionContainerTest extends TestCase {

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

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne', [
            'two' => $instanceTwo
        ]);

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance);

        $this->assertEquals($instanceTwo, $instance->two);
    }

    public function testGetInstanceOverWriteValuesWithNull() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne', [
            'three' => null
        ]);

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

        $config->setClassConfig('rg\injektor\DICTestAbstractClass', [
            'class' => 'rg\injektor\DICTestClassOneConfigured',
        ]);

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

        $config->setClassConfig('rg\injektor\DICTestClassOne', [
            'singleton' => false
        ]);

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceTwo);

        $this->assertTrue($instanceOne !== $instanceTwo);
    }

    public function testGetSingletonInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassOne', [
            'singleton' => true
        ]);

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instanceTwo);

        $this->assertTrue($instanceOne === $instanceTwo);
    }

    public function testGetDifferentSingletonInstancesBecauseOfDifferentParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHint', [
            'singleton' => true
        ]);

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint', ['one' => 'one', 'two' => 'two']);
        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint', ['one' => 'three', 'two' => 'four']);
        $instanceThree = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint', ['one' => 'one', 'two' => 'two']);

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instanceOne);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instanceTwo);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instanceThree);

        $this->assertFalse($instanceOne === $instanceTwo);
        $this->assertTrue($instanceOne === $instanceThree);
    }

    public function testGetInstanceOfRealSingleton() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestSingleton', [
            'singleton' => true
        ]);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestSingleton');

        $this->assertInstanceOf('rg\injektor\DICTestSingleton', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance->instance);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance->injectedProperty);
        $this->assertEquals('foo', $instance->foo);
    }

    public function testGetInstanceOfRealSingletonMarkedAsService() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestSingleton', [
            'service' => true
        ]);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestSingleton');

        $this->assertInstanceOf('rg\injektor\DICTestSingleton', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance->instance);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance->injectedProperty);
        $this->assertEquals('foo', $instance->foo);
    }

    public function testGetInstanceOfService() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestService', [
            'service' => true
        ]);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestService', ['arg' => 123]);

        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestService', ['arg' => 123]);

        $this->assertSame($instance, $instanceTwo);
    }

    public function testGetInstanceOfAnnotatedService() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestAnnotatedService', []);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedService', ['arg' => 123]);

        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedService', ['arg' => 123]);

        $this->assertSame($instance, $instanceTwo);
    }

    public function testGetInstanceOfLazy() {
        $config = new Configuration(null, __DIR__ . '/_factories', true);

        $config->setClassConfig('rg\injektor\DICTestLazy', [
            'lazy' => true
        ]);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestLazy', ['arg' => 123]);

        $this->assertInstanceOf('rg\injektor\DICTestLazy', $instance);
        $this->assertInstanceOf('ProxyManager\Proxy\LazyLoadingInterface', $instance);

        $this->assertEquals('success', $instance->someMethod(), 'Must be able to call someMethod() on proxy object');
    }

    public function testGetInstanceOfAnnotatedLazy() {
        $config = new Configuration(null, __DIR__ . '/_factories', true);

        $config->setClassConfig('rg\injektor\DICTestAnnotatedLazy', []);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedLazy', ['arg' => 123]);

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedLazy', $instance);
        $this->assertInstanceOf('ProxyManager\Proxy\LazyLoadingInterface', $instance);

        $this->assertEquals('success', $instance->someMethod(), 'Must be able to call someMethod() on proxy object');
    }

    public function testGetInstanceOfLazyService() {
        $config = new Configuration(null, __DIR__ . '/_factories', true);

        $config->setClassConfig('rg\injektor\DICTestLazyService', [
            'lazy' => true,
            'service' => true,
        ]);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestLazyService', ['arg' => 123]);
        $instance2 = $dic->getInstanceOfClass('rg\injektor\DICTestLazyService', ['arg' => 123]);

        $this->assertSame($instance, $instance2);

        $this->assertInstanceOf('rg\injektor\DICTestLazyService', $instance);
        $this->assertInstanceOf('ProxyManager\Proxy\LazyLoadingInterface', $instance);

        $this->assertEquals('success', $instance->someMethod(), 'Must be able to call someMethod() on proxy object');
    }

    public function testGetInstanceOfAnnotatedLazyService() {
        $config = new Configuration(null, __DIR__ . '/_factories', true);

        $config->setClassConfig('rg\injektor\DICTestAnnotatedLazyService', []);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedLazyService', ['arg' => 123]);
        $instance2 = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedLazyService', ['arg' => 123]);

        $this->assertSame($instance, $instance2);

        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedLazyService', $instance);
        $this->assertInstanceOf('ProxyManager\Proxy\LazyLoadingInterface', $instance);

        $this->assertEquals('success', $instance->someMethod(), 'Must be able to call someMethod() on proxy object');
    }

    public function testGetInstanceOfServiceWithDifferentArgumentsStillReturnSameInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestService', [
            'service' => true
        ]);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestService', ['arg' => 123]);

        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestService', ['arg' => 456]);

        $this->assertSame($instance, $instanceTwo);
    }

    public function testGetInstanceOfAnnotatedServiceWithDifferentArgumentsStillReturnSameInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestAnnotatedService', [
            'service' => true
        ]);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedService', ['arg' => 123]);

        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestAnnotatedService', ['arg' => 456]);

        $this->assertSame($instance, $instanceTwo);
    }

    public function testGetInstanceWithoutConstructor() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoConstructor');

        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance);
    }

    public function testGetInstanceWithConfiguredParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHint', [
            'params' => [
                'one' => [
                    'value' => 'foo',
                ],
                'two' => [
                    'value' => 123
                ]
            ],
        ]);

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint');

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals(123, $instance->two);
    }

    public function testGetInstanceWithConfiguredClassParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHint', [
            'params' => [
                'one' => [
                    'value' => 'foo',
                ],
                'two' => [
                    'class' => 'rg\injektor\DICTestClassOne'
                ]
            ],
        ]);

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint');

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance->two);
    }

    public function testGetInstanceWithConfiguredAndOptionalClassParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHintOptionalArgument', [
            'params' => [
                'one' => [
                    'value' => 'foo',
                ],
            ],
        ]);

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHintOptionalArgument', [
        ]);

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHintOptionalArgument', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals('bar', $instance->two);
        $this->assertEquals([], $instance->ar);
    }

    public function testGetInstanceWithConfiguredAndOptionalClassParametersSomeGiven() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHintOptionalArgument', [
            'params' => [
                'one' => [
                    'value' => 'foo',
                ],
            ],
        ]);

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHintOptionalArgument', [
            'ar' => ['abc']
        ]);

        $this->assertInstanceOf('rg\injektor\DICTestClassNoTypeHintOptionalArgument', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals('bar', $instance->two);
        $this->assertEquals(['abc'], $instance->ar);
    }

    public function testGetInstanceWithConfiguredAndDefaultClassParameter() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassNoTypeHint', [
            'params' => [
                'two' => [
                    'class' => 'rg\injektor\DICTestClassOne'
                ],
            ],
        ]);

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassNoTypeHint', [
            'one' => 'foo'
        ]);

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

        $actual = $dic->callMethodOnObject($instance, 'getSomethingTwo', [
            'three' => new DICTestClassThree()
        ]);

        $this->assertEquals('barfoo', $actual);
    }

    public function testCallMethodWithUnnamedParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassOne');

        $actual = $dic->callMethodOnObject($instance, 'getSomethingTwo', [
            new DICTestClassTwo(new DICTestClassThree()), new DICTestClassThree()
        ]);

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

        $config->setClassConfig('rg\injektor\DICTestAnnotatedInterface', [
            'named' => [
                'implOne' => 'rg\injektor\DICTestAnnotatedInterfaceImplOne',
                'implTwo' => 'rg\injektor\DICTestAnnotatedInterfaceImplTwo'
            ]
        ]);
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

        $config->setClassConfig('rg\injektor\DICTestAnnotatedInterface', [
            'named' => [
                'implOne' => 'rg\injektor\DICTestAnnotatedInterfaceImplTwo',
                'implTwo' => 'rg\injektor\DICTestAnnotatedInterfaceImplOne'
            ]
        ]);
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

        $config->setClassConfig('rg\injektor\DICTestProvidedInterface', [
            'namedProviders' => [
                'impl1' => [
                    'class' => 'rg\injektor\DICTestProvider',
                    'parameters' => ['name' => 'impl1'],
                ],
            ],
        ]);
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestInterfaceDependencyTwo');

        $this->assertInstanceOf('rg\injektor\DICTestProvidedDecorator', $instance->dependency);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedInterfaceImpl1', $instance->dependency->getProvidedClass());
    }

    public function testNamedConfiguredProvidedByNoAnnotation() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestProvidedInterfaceNoConfig', [
            'namedProviders' => [
                'impl1' => [
                    'class' => 'rg\injektor\DICTestProviderNoAnnotation',
                    'parameters' => ['name' => 'impl1Param'],
                ],
            ],
        ]);
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestInterfaceDependencyTwoNoAnnotation');

        $this->assertInstanceOf('rg\injektor\DICTestInterfaceDependencyTwoNoAnnotation', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedInterfaceNoConfigImpl', $instance->dependency);
        $this->assertEquals('impl1Param', $instance->dependency->name);
    }

    public function testConfiguredProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestInterface', [
            'provider' => [
                'class' => 'rg\injektor\DICSimpleTestProvider',
            ],
        ]);
        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestInterfaceDependency');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedDecorator', $instance->dependency);

        $this->assertNull($instance->dependency->getProvidedClass());
    }

    public function testDependsOnInterface() {
        $config = new Configuration(null, __DIR__ . '/_factories');
        $dic = $this->getContainer($config);
        /** @var DICTestDependsOnInterface $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestDependsOnInterface');

        $this->assertNull($instance->interface);
    }

    public function testConfiguredProvidedByDirectAccess() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestClassTwo', [
            'provider' => [
                'class' => 'rg\injektor\DICSimpleTestProvider',
            ],
        ]);

        $dic = $this->getContainer($config);

        /** @var DICTestInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassTwo');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedDecorator', $instance);
    }

    public function testConfiguredProvidedByWithParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestInterface', [
            'provider' => [
                'class' => 'rg\injektor\DICSimpleTestProvider',
                'parameters' => [
                    'name' => 'impl1'
                ]
            ],
        ]);
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

    public function testPropertyInjectionWithUseStatement() {
        $config = new Configuration(null, __DIR__ . '/_factories');
        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('some\other\name\space\ClassPropertyInjectionWithUseStatementSupport');

        $this->assertInstanceOf('some\other\name\space\ClassPropertyInjectionWithUseStatementSupport', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestClassNoConstructor', $instance->dependency);
        $this->assertInstanceOf('some\other\name\space\DependencySameNamespace', $instance->dependencySameNamespace);
        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedInterface', $instance->dependencyInterfaceWithAlias);
        $this->assertInstanceOf('rg\injektor\DICTestAnnotatedSingleton', $instance->dependencyWithAlias);
        $this->assertInstanceOf('rg\injektor\DICTestClassThatAlsoExistsInPublicNamespace', $instance->dependencyWithOtherClassInPublicNamespaceFq);
        $this->assertInstanceOf('DICTestClassThatAlsoExistsInPublicNamespace', $instance->dependencyWithOtherClassInPublicNamespaceFqPublic);
        $this->assertInstanceOf('rg\injektor\DICTestClassThatAlsoExistsInPublicNamespace', $instance->dependencyWithOtherClassInPublicNamespace);
    }

    public function test_getInstanceOfClass_givenClassWithTypedProperties_injectsCorrectClassesIntoProperties() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassWithTypedProperties');

        $this->assertInstanceOf('rg\injektor\DICTestClassWithTypedProperties', $instance);

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance->one);
        $this->assertInstanceOf('rg\injektor\DICTestClassTwo', $instance->two);
        $this->assertInstanceOf('rg\injektor\DICTestClassThree', $instance->three);
    }

    public function test_getInstanceOfClass_givenClassWithUnionTypedProperties_injectsCorrectClassesIntoProperties() {
        include_once 'test_classes_php80.php';

        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassWithUnionTypedProperties');

        $this->assertInstanceOf('rg\injektor\DICTestClassWithUnionTypedProperties', $instance);

        $this->assertInstanceOf('rg\injektor\DICTestClassOne', $instance->one);
        $this->assertInstanceOf('rg\injektor\DICTestClassTwo', $instance->two);
        $this->assertInstanceOf('rg\injektor\DICTestClassThree', $instance->three);
    }

    public function testClearDoesNotThrow() {
        $config = new Configuration(null, __DIR__ . '/_factories');
        $dic = $this->getContainer($config);

        $dic->clear();
        $this->assertTrue(true);
    }

    public function getContainer(Configuration $config): DependencyInjectionContainer {
        return new DependencyInjectionContainer($config);
    }
}
