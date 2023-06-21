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

include_once 'test_classes_attributes.php';

class DependencyInjectionContainerAttributesTest extends TestCase {

    public function testGetInstance() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassWithAttributes');

        $this->assertInstanceOf('rg\injektor\DICTestClassWithAttributes', $instance);

        $this->assertInstanceOf('rg\injektor\DICTestClassTwo', $instance->two);
        $this->assertInstanceOf('rg\injektor\DICTestClassThree', $instance->three);
        $this->assertInstanceOf('rg\injektor\DICTestClassThree', $instance->two->three);
        $this->assertInstanceOf('rg\injektor\DICTestClassThree', $instance->getFour());
    }

    public function testGetInstanceOfAnnotatedService() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestAttributedService', []);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedService', ['arg' => 123]);

        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedService', ['arg' => 123]);

        $this->assertSame($instance, $instanceTwo);
    }

    public function testGetInstanceOfAnnotatedLazy() {
        $config = new Configuration(null, __DIR__ . '/_factories', true);

        $config->setClassConfig('rg\injektor\DICTestAttributedLazy', []);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedLazy', ['arg' => 123]);

        $this->assertInstanceOf('rg\injektor\DICTestAttributedLazy', $instance);
        $this->assertInstanceOf('ProxyManager\Proxy\LazyLoadingInterface', $instance);

        $this->assertEquals('success', $instance->someMethod(), 'Must be able to call someMethod() on proxy object');
    }

    public function testGetInstanceOfAnnotatedLazyService() {
        $config = new Configuration(null, __DIR__ . '/_factories', true);

        $config->setClassConfig('rg\injektor\DICTestAttributedLazyService', []);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedLazyService', ['arg' => 123]);
        $instance2 = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedLazyService', ['arg' => 123]);

        $this->assertSame($instance, $instance2);

        $this->assertInstanceOf('rg\injektor\DICTestAttributedLazyService', $instance);
        $this->assertInstanceOf('ProxyManager\Proxy\LazyLoadingInterface', $instance);

        $this->assertEquals('success', $instance->someMethod(), 'Must be able to call someMethod() on proxy object');
    }

    public function testCallMethodOnObject() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassWithAttributes');

        $actual = $dic->callMethodOnObject($instance, 'getSomething');

        $this->assertEquals('barfoo', $actual);
    }

    public function testAnnotatedSingleton() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedSingleton');
        $instanceTwo = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedSingleton');

        $this->assertInstanceOf('rg\injektor\DICTestAttributedSingleton', $instanceOne);
        $this->assertInstanceOf('rg\injektor\DICTestAttributedSingleton', $instanceTwo);
        $this->assertTrue($instanceOne === $instanceTwo);
    }

    public function testAnnotatedImplementedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedInterface');

        $this->assertInstanceOf('rg\injektor\DICTestAttributedInterfaceImpl', $instance);
    }

    public function testAnnotatedImplementedByDefault() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedInterfaceNamedConfig');

        $this->assertInstanceOf('rg\injektor\DICTestAttributedInterfaceNamedConfigImpl', $instance);
    }

    public function testNamedAnnotation() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestAttributedInterface', [
            'named' => [
                'implOne' => 'rg\injektor\DICTestAttributedInterfaceImplOne',
                'implTwo' => 'rg\injektor\DICTestAttributedInterfaceImplTwo'
            ]
        ]);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamedWithAttributes');

        $this->assertInstanceOf('rg\injektor\DICTestAttributedInterfaceImplOne', $instance->one);
        $this->assertInstanceOf('rg\injektor\DICTestAttributedInterfaceImplTwo', $instance->two);
    }

    public function testNamedAnnotationWithAnnotationConfiguration() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamedConfigWithAttributes');

        $this->assertInstanceOf('rg\injektor\DICTestAttributedInterfaceNamedConfigImplOne', $instance->one);
        $this->assertInstanceOf('rg\injektor\DICTestAttributedInterfaceNamedConfigImplTwo', $instance->two);
    }

    public function testNamedAnnotationAtMethodCall() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestAttributedInterface', [
            'named' => [
                'implOne' => 'rg\injektor\DICTestAttributedInterfaceImplTwo',
                'implTwo' => 'rg\injektor\DICTestAttributedInterfaceImplOne'
            ]
        ]);
        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamedWithAttributes');

        $returnValue = $dic->callMethodOnObject($instance, 'doSomething');

        $this->assertInstanceOf('rg\injektor\DICTestAttributedInterfaceImplTwo', $returnValue);
    }

    public function testNamedAnnotationAtMethodCallWithConfiguration() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamedConfigWithAttributes');

        $returnValue = $dic->callMethodOnObject($instance, 'doSomething');

        $this->assertInstanceOf('rg\injektor\DICTestAttributedInterfaceNamedConfigImplOne', $returnValue);
    }

    public function testSimpleProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestSimpleProvidedAnnotatedInterfaceDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestSimpleProvidedAnnotatedInterfaceDependency');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedAnnotatedInterfaceDependency', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedAnnotatedDecorator', $instance->dependency);

        $this->assertNull($instance->dependency->getProvidedClass());
    }

    public function testSimpleProvidedByOfDependency() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestSimpleProvidedAnnotatedImplDependency $instance */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestSimpleProvidedAnnotatedImplDependency');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedAnnotatedImplDependency', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedAnnotatedDecorator', $instance->providedInterface);

        $this->assertNull($instance->providedInterface->getProvidedClass());
    }

    public function testSimpleProvidedByOfDependencyOnMethod() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestSimpleProvidedAnnotatedImplDependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestSimpleProvidedAnnotatedImplDependency');

        $actual = $dic->callMethodOnObject($instance, 'someMethod');

        $this->assertInstanceOf('rg\injektor\DICTestSimpleProvidedAnnotatedDecorator', $actual);

        $this->assertNull($actual->getProvidedClass());
    }

    public function testNamedProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        /** @var DICTestNamedProvidedImpl1Dependency $instance  */
        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestNamedProvidedAnnotatedImpl1Dependency');

        $this->assertInstanceOf('rg\injektor\DICTestNamedProvidedAnnotatedImpl1Dependency', $instance);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedAnnotatedDecorator', $instance->providedInterface1);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedAnnotatedDecorator', $instance->providedInterface2);
        $this->assertInstanceOf('rg\injektor\DICTestProvidedAnnotatedInterfaceImpl1', $instance->providedInterface1->getProvidedClass());
        $this->assertInstanceOf('rg\injektor\DICTestProvidedAnnotatedInterfaceImpl2', $instance->providedInterface2->getProvidedClass());
    }

    public function testNamedConfiguredProvidedBy() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $config->setClassConfig('rg\injektor\DICTestProvidedAnnotatedInterface', [
            'namedProviders' => [
                'impl1' => [
                    'class' => 'rg\injektor\DICTestProviderForAnnotatedInterface',
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

    public function testDICTestAnnotatedInterfacePropertyInjection() {
        $config = new Configuration(null, __DIR__ . '/_factories');
        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestAttributedInterfacePropertyInjection');

        $this->assertInstanceOf('rg\injektor\DICTestAttributedInterfaceImpl', $instance->dependency);
    }

    public function testArgumentInjectionWithParameters() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICTestClassAttributedArgumentsWithParameters');

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

        $instance = $dic->getInstanceOfClass('rg\injektor\DICProvidedTestClassWithAttributesArgumentsWithParameters');

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

        $instance = $dic->getInstanceOfClass('rg\injektor\DICProvidedTestClassWithAttributesNoTypeHintNamedUserDefault');

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassWithAttributesNoTypeHintNamedUserDefault', $instance);

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassNoTypeHintNamed', $instance->provided);

        $this->assertEquals('1f', $instance->provided->one);
        $this->assertEquals('2f', $instance->provided->two);
    }

    public function testNamedProvidedByPropertyInjectionNonDefault() {
        $config = new Configuration(null, __DIR__ . '/_factories');

        $dic = $this->getContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\DICProvidedTestClassWithAttributesNoTypeHintNamedUserSomeName');

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassWithAttributesNoTypeHintNamedUserSomeName', $instance);

        $this->assertInstanceOf('rg\injektor\DICProvidedTestClassNoTypeHintNamed', $instance->provided);

        $this->assertEquals('3f', $instance->provided->one);
        $this->assertEquals('4f', $instance->provided->two);
    }

    public function getContainer(Configuration $config): DependencyInjectionContainer {
        return new DependencyInjectionContainer($config);
    }
}
