<?php
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injektor {

    use rg\injektor\attributes\ImplementedBy;
    use rg\injektor\attributes\Inject;
    use rg\injektor\attributes\Lazy;
    use rg\injektor\attributes\Named;
    use rg\injektor\attributes\Params;
    use rg\injektor\attributes\ProvidedBy;
    use rg\injektor\attributes\Service;
    use rg\injektor\attributes\Singleton;

    require_once 'test_classes.php';

    class DICTestClassWithAttributes {

        public DICTestClassTwo $two;

        public DICTestClassThree $three;

        #[Inject]
        protected DICTestClassThree $four;

        public function getFour(): DICTestClassThree {
            return $this->four;
        }

        #[Inject]
        public function __construct(DICTestClassTwo $two, DICTestClassThree $three = null) {
            $this->two = $two;
            $this->three = $three;
        }

        #[Inject]
        public function getSomething(DICTestClassTwo $two, DICTestClassThree $three): string {
            return $two->getSomething() . $three->getSomething();
        }
    }

    class DICProvidedTestClassWithAttributesArgumentsWithParameters {

        public $class;

        public $methodClass;

        #[Inject]
        #[Params(['one' => 'foo', 'two' => 'bar'])]
        public DICProvidedTestClassNoTypeHint $injectedProperty;

        #[Inject]
        public function __construct(#[Params(['one' => 'foo', 'two' => 'bar'])] DICProvidedTestClassNoTypeHint $class) {
            $this->class = $class;
        }

        #[Inject]
        public function someMethod(#[Params(['one' => 'foo', 'two' => 'bar'])] DICProvidedTestClassNoTypeHint $class) {
            $this->methodClass = $class;
        }
    }

    class DICProvidedTestClassWithAttributesNoTypeHintNamedUserDefault {
        #[Inject]
        public DICProvidedTestClassNoTypeHintNamed $provided;
    }

    class DICProvidedTestClassWithAttributesNoTypeHintNamedUserSomeName {
        #[Inject]
        #[Named('someName')]
        public DICProvidedTestClassNoTypeHintNamed $provided;
    }

    class DICTestClassAttributedArgumentsWithParameters {

        public $class;

        public $methodClass;

        #[Inject]
        #[Params(['one' => 'foo', 'two' => 'bar'])]
        public DICTestClassNoTypeHint $injectedProperty;

        #[Inject]
        public function __construct(#[Params(['one' => 'foo', 'two' => 'bar'])] DICTestClassNoTypeHint $class) {
            $this->class = $class;
        }

        #[Inject]
        public function someMethod(#[Params(['one' => 'foo', 'two' => 'bar'])] DICTestClassNoTypeHint $class) {
            $this->methodClass = $class;
        }
    }

    #[ImplementedBy(DICTestAttributedInterfaceImpl::class)]
    interface DICTestAttributedInterface {

    }

    class DICTestAttributedInterfaceImpl implements DICTestAttributedInterface {

    }

    class DICTestAttributedInterfaceImplOne implements DICTestAttributedInterface {

    }

    class DICTestAttributedInterfaceImplTwo implements DICTestAttributedInterface {

    }

    class DICTestNamedWithAttributes {
        public $one;

        #[Inject, Named('implTwo')]
        public DICTestAttributedInterface $two;

        #[Inject]
        public function __construct(#[Named('implOne')] DICTestAttributedInterface $one) {
            $this->one = $one;
        }

        #[Inject]
        public function doSomething(#[Named('implOne')] DICTestAttributedInterface $one) {
            return $one;
        }
    }

    #[ImplementedBy(DICTestAttributedInterfaceNamedConfigImpl::class, name: 'default')]
    #[ImplementedBy(DICTestAttributedInterfaceNamedConfigImplOne::class, name: 'implOne')]
    #[ImplementedBy(DICTestAttributedInterfaceNamedConfigImplTwo::class, name: 'implTwo')]
    interface DICTestAttributedInterfaceNamedConfig {

    }

    class DICTestAttributedInterfaceNamedConfigImpl implements DICTestAttributedInterfaceNamedConfig {

    }

    class DICTestAttributedInterfaceNamedConfigImplOne implements DICTestAttributedInterfaceNamedConfig {

    }

    class DICTestAttributedInterfaceNamedConfigImplTwo implements DICTestAttributedInterfaceNamedConfig {

    }

    class DICTestNamedConfigWithAttributes {

        public $one;

        #[Inject]
        #[Named('implTwo')]
        public DICTestAttributedInterfaceNamedConfig $two;

        #[Inject]
        public function __construct(#[Named('implOne')] DICTestAttributedInterfaceNamedConfig $one) {
            $this->one = $one;
        }

        #[Inject]
        public function doSomething(#[Named('implOne')] DICTestAttributedInterfaceNamedConfig $one) {
            return $one;
        }
    }

    #[Singleton]
    class DICTestAttributedSingleton {

    }

    #[Service]
    class DICTestAttributedService {
        public function __construct($arg) {

        }
    }

    #[Lazy]
    class DICTestAttributedLazy {
        public function __construct($arg) {

        }
        public function someMethod() {
            return 'success';
        }
    }

    #[Lazy, Service]
    class DICTestAttributedLazyService {
        public function __construct($arg) {

        }
        public function someMethod() {
            return 'success';
        }
    }

    #[ProvidedBy(DICTestProviderForAnnotatedInterface::class, name: 'impl1', params: ['name' => 'impl1'])]
    #[ProvidedBy(DICTestProviderForAnnotatedInterface::class, name: 'impl2', params: ['name' => 'impl2'])]
    interface DICTestProvidedAnnotatedInterface {

    }

    class DICTestProvidedAnnotatedInterfaceImpl1 implements DICTestProvidedAnnotatedInterface {

    }

    class DICTestProvidedAnnotatedInterfaceImpl2 implements DICTestProvidedAnnotatedInterface {

    }

    #[ProvidedBy(DICSimpleTestProviderForAnnotatedInterface::class)]
    interface DICTestSimpleProvidedAnnotatedInterface {

    }

    class DICTestSimpleProvidedAnnotatedDecorator implements DICTestSimpleProvidedAnnotatedInterface {

        private $providedClass;

        public function setProvidedClass($providedClass) {
            $this->providedClass = $providedClass;
        }

        public function getProvidedClass() {
            return $this->providedClass;
        }
    }

    class DICTestProvidedAnnotatedDecorator implements DICTestProvidedAnnotatedInterface {

        private $providedClass;

        public function setProvidedClass($providedClass) {
            $this->providedClass = $providedClass;
        }

        public function getProvidedClass() {
            return $this->providedClass;
        }
    }

    class DICTestNamedProvidedAnnotatedImpl1Dependency {

        public $providedInterface1;

        public $providedInterface2;

        #[Inject]
        public function __construct(#[Named('impl1')] DICTestProvidedAnnotatedInterface $providedInterface1, #[Named('impl2')] DICTestProvidedAnnotatedInterface $providedInterface2) {
            $this->providedInterface1 = $providedInterface1;
            $this->providedInterface2 = $providedInterface2;
        }
    }

    class DICTestSimpleProvidedAnnotatedImplDependency {

        public $providedInterface;

        #[Inject]
        public function __construct(DICTestSimpleProvidedAnnotatedInterface $providedInterface) {
            $this->providedInterface = $providedInterface;
        }

        #[Inject]
        public function someMethod(DICTestSimpleProvidedAnnotatedInterface $providedInterface) {
            return $providedInterface;
        }
    }

    class DICTestProviderForAnnotatedInterface implements \rg\injektor\Provider {

        private $decorator;

        private $name;

        /**
         * @inject
         */
        public function __construct(DICTestProvidedAnnotatedDecorator $decorator, $name = null) {
            $this->decorator = $decorator;
            $this->name = $name;
        }

        public function get() {
            switch ($this->name) {
                case 'impl1':
                    $this->decorator->setProvidedClass(new DICTestProvidedAnnotatedInterfaceImpl1());
                    break;
                case 'impl2':
                    $this->decorator->setProvidedClass(new DICTestProvidedAnnotatedInterfaceImpl2());
                    break;
            }
            return $this->decorator;
        }
    }

    class DICSimpleTestProviderForAnnotatedInterface implements \rg\injektor\Provider {

        private $decorator;

        private $name;

        /**
         * @inject
         */
        public function __construct(DICTestSimpleProvidedAnnotatedDecorator $decorator, $name = null) {
            $this->decorator = $decorator;
            $this->name = $name;
        }

        public function get() {
            switch ($this->name) {
                case 'impl1':
                    $this->decorator->setProvidedClass(new DICTestProvidedAnnotatedInterfaceImpl1());
                    break;
                case 'impl2':
                    $this->decorator->setProvidedClass(new DICTestProvidedAnnotatedInterfaceImpl2());
                    break;
            }
            return $this->decorator;
        }
    }

    class DICTestSimpleProvidedAnnotatedInterfaceDependency {
        public DICTestSimpleProvidedAnnotatedInterface $dependency;

        #[Inject]
        public function __construct(DICTestSimpleProvidedAnnotatedInterface $dependency) {
            $this->dependency = $dependency;
        }
    }

    class DICTestAttributedInterfacePropertyInjection {

        #[Inject]
        public DICTestAttributedInterface $dependency;
    }
}
