<?php
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injektor {

    use rg\injektor\attributes\Lazy;
    use rg\injektor\attributes\Param;
    use rg\injektor\attributes\Inject;
    use rg\injektor\attributes\ProvidedBy;
    use rg\injektor\attributes\Service;
    use rg\injektor\attributes\Singleton;

    require_once 'test_classes_not_injectable.php';

    class DICTestClassOne {

        /**
         * @var \rg\injektor\DICTestClassTwo
         */
        public $two;

        /**
         * @var \rg\injektor\DICTestClassThree
         */
        public $three;

        /**
         * @var \rg\injektor\DICTestClassThree
         */
        #[Inject]
        protected $four;

        /**
         * @return DICTestClassThree
         */
        public function getFour() {
            return $this->four;
        }

        /**
         * @param DICTestClassTwo $two
         * @param DICTestClassThree $three
         */
        #[Inject]
        public function __construct(DICTestClassTwo $two, DICTestClassThree $three = null) {
            $this->two = $two;
            $this->three = $three;
        }

        /**
         * @param DICTestClassTwo $two
         * @param DICTestClassThree $three
         * @return string
         */
        #[Inject]
        public function getSomething(DICTestClassTwo $two, DICTestClassThree $three) {
            return $two->getSomething() . $three->getSomething();
        }

        /**
         * @param DICTestClassTwo $two
         * @param $three
         * @return string
         */
        #[Inject]
        public function getSomethingTwo(DICTestClassTwo $two, $three) {
            return $two->getSomething() . $three->getSomething();
        }

        public function getSomethingNotInjectible(DICTestClassTwo $two, DICTestClassThree $three) {
            return $two->getSomething() . $three->getSomething();
        }

        public function noTypeHint($foo) {

        }
    }

    class DICTestClassOneConfigured extends DICTestAbstractClass implements DICTestInterface {

    }

    class DICTestClassTwo {

        /**
         * @var \rg\injektor\DICTestClassThree
         */
        public $three;

        /**
         * @param DICTestClassThree $three
         */
        #[Inject]
        public function __construct(DICTestClassThree $three) {
            $this->three = $three;
        }

        public function getSomething() {
            return 'bar';
        }
    }

    class DICTestClassThree {

        public function __construct() {

        }

        public function getSomething() {
            return 'foo';
        }
    }

    class DICTestClassNoInject {

        public function __construct(DICTestClassThree $three) {

        }
    }

    class DICProvidedTestClassNoTypeHintProvider implements Provider {

        private $one;

        private $two;

        public function __construct($one, $two) {
            $this->one = $one;
            $this->two = $two;
        }

        public function get() {
            return new DICProvidedTestClassNoTypeHint($this->one . 'f', $this->two . 'f');
        }
    }

    class DICProvidedTestClassArgumentsWithParameters {

        public $class;

        public $methodClass;

        #[Inject(overwriteParams: [
            new Param(name: 'one', value: 'foo'),
            new Param(name: 'two', value: 'bar'),
        ])]
        public \rg\injektor\DICProvidedTestClassNoTypeHint $injectedProperty;

        public function __construct(
            #[Inject(overwriteParams: [
                new Param(name: 'one', value: 'foo'),
                new Param(name: 'two', value: 'bar'),
            ])]
            DICProvidedTestClassNoTypeHint $class
        ) {
            $this->class = $class;
        }

        public function someMethod(
            #[Inject(overwriteParams: [
                new Param(name: 'one', value: 'foo'),
                new Param(name: 'two', value: 'bar'),
            ])]
            DICProvidedTestClassNoTypeHint $class
        ) {
            $this->methodClass = $class;
        }
    }

    #[ProvidedBy(className: DICProvidedTestClassNoTypeHintProvider::class)]
    class DICProvidedTestClassNoTypeHint {

        public $one;

        public $two;

        public function __construct($one, $two) {
            $this->one = $one;
            $this->two = $two;
        }
    }

    class DICProvidedTestClassNoTypeHintNamedUserDefault {
        /**
         * @var \rg\injektor\DICProvidedTestClassNoTypeHintNamed
         */
        #[Inject]
        public $provided;
    }

    class DICProvidedTestClassNoTypeHintNamedUserSomeName {

        /**
         * @var \rg\injektor\DICProvidedTestClassNoTypeHintNamed
         */
        #[Inject(named: 'someName')]
        public $provided;
    }

    #[ProvidedBy(className: DICProvidedTestClassNoTypeHintProvider::class, overwriteParams: [
        new Param(name: 'one', value: 1),
        new Param(name: 'two', value: 2),
    ])]
    #[ProvidedBy(className: DICProvidedTestClassNoTypeHintProvider::class, named: 'someName', overwriteParams: [
        new Param(name: 'one', value: 3),
        new Param(name: 'two', value: 4),
    ])]
    class DICProvidedTestClassNoTypeHintNamed {

        public $one;

        public $two;

        public function __construct($one, $two) {
            $this->one = $one;
            $this->two = $two;
        }
    }

    class DICTestClassArgumentsWithParameters {

        public $class;

        public $methodClass;

        #[Inject(overwriteParams: [
            new Param(name: 'one', value: 'foo'),
            new Param(name: 'two', value: 'bar'),
        ])]
        public DICTestClassNoTypeHint $injectedProperty;

        public function __construct(
            #[Inject(overwriteParams: [
                new Param(name: 'one', value: 'foo'),
                new Param(name: 'two', value: 'bar'),
            ])]
            DICTestClassNoTypeHint $class
        ) {
            $this->class = $class;
        }

        public function someMethod(
            #[Inject(overwriteParams: [
                new Param(name: 'one', value: 'foo'),
                new Param(name: 'two', value: 'bar'),
            ])]
            DICTestClassNoTypeHint $class
        ) {
            $this->methodClass = $class;
        }
    }

    class DICTestClassNoTypeHint {

        public $one;

        public $two;

        #[Inject]
        public function __construct($one, $two) {
            $this->one = $one;
            $this->two = $two;
        }
    }

    class DICTestClassNoTypeHintOptionalArgument {

        public $one;

        public $two;

        public $ar;

        public function __construct($one, $two = 'bar', array $ar = []) {
            $this->one = $one;
            $this->two = $two;
            $this->ar = $ar;
        }
    }

    class DICTestClassNoParamTypeHint {

        #[Inject]
        public $two;
    }

    class DICTestClassPrivateProperty {

        /**
         * @var DICTestClassNoConstructor
         */
        #[Inject]
        private $two;
    }

    class DICTestClassPropertyDoubledAnnotation {

        /**
         * @var \rg\injektor\DICTestClassNoConstructor
         * @var \rg\injektor\DICTestClassPrivateProperty
         */
        #[Inject]
        public $two;
    }

    class DICTestClassNoConstructor {

    }

    class DICTestClassThatAlsoExistsInPublicNamespace {
    }

    class DICTestAnnotatedInterfaceImpl implements DICTestAnnotatedInterface {

    }

    class DICTestAnnotatedInterfaceImplOne implements DICTestAnnotatedInterface {

    }

    class DICTestAnnotatedInterfaceImplTwo implements DICTestAnnotatedInterface {

    }

    class DICTestNamed {

        public $one;

        #[Inject(named: 'implTwo')]
        public DICTestAnnotatedInterface $two;

        public function __construct(
            #[Inject(named: 'implOne')]
            DICTestAnnotatedInterface $one
        ) {
            $this->one = $one;
        }

        public function doSomething(
            #[Inject(named: 'implOne')]
            DICTestAnnotatedInterface $one
        ) {
            return $one;
        }
    }

    class DICTestNamedAndDefault {
        #[Inject]
        public function __construct(
            #[Inject(named: 'implOne')]
            public DICTestAnnotatedInterface $one,
            public DICTestAnnotatedInterfaceImplTwo $two, // Notice this is autowired, we require #[Inject] at the constructor
        ) {
        }
    }

    class DICTestAnnotatedInterfaceNamedConfigImpl implements DICTestAnnotatedInterfaceNamedConfig {

    }

    class DICTestAnnotatedInterfaceNamedConfigImplOne implements DICTestAnnotatedInterfaceNamedConfig {

    }

    class DICTestAnnotatedInterfaceNamedConfigImplTwo implements DICTestAnnotatedInterfaceNamedConfig {

    }

    class DICTestNamedConfig {

        public $one;

        /**
         * @var \rg\injektor\DICTestAnnotatedInterfaceNamedConfig
         */
        #[Inject(named: 'implTwo')]
        public $two;

        public function __construct(
            #[Inject(named: 'implOne')]
            DICTestAnnotatedInterfaceNamedConfig $one
        ) {
            $this->one = $one;
        }

        public function doSomething(
            #[Inject(named: 'implOne')]
            DICTestAnnotatedInterfaceNamedConfig $one
        ) {
            return $one;
        }
    }

    class DICTestSingleton {

        public $foo;

        public $instance;

        /**
         * @var \rg\injektor\DICTestClassNoConstructor
         */
        #[Inject]
        public $injectedProperty;

        private function __construct($foo, $instance) {
            $this->foo = $foo;
            $this->instance = $instance;
        }

        /**
         * @static
         * @param DICTestClassNoConstructor $instance
         * @return DICTestSingleton
         */
        #[Inject]
        public static function getInstance(DICTestClassNoConstructor $instance) {
            return new static('foo', $instance);
        }
    }

    #[Singleton]
    class DICTestAnnotatedSingleton {

    }

    class DICTestService {

        public function __construct($arg) {

        }
    }

    #[Service]
    class DICTestAnnotatedService {
        public function __construct($arg) {

        }
    }

    class DICTestLazy {
        public function __construct($arg) {

        }
        public function someMethod() {
            return 'success';
        }
    }

    #[Lazy]
    class DICTestAnnotatedLazy {
        public function __construct($arg) {

        }
        public function someMethod() {
            return 'success';
        }
    }

    class DICTestLazyService {
        public function __construct($arg) {

        }
        public function someMethod() {
            return 'success';
        }
    }

    #[Service]
    #[Lazy]
    class DICTestAnnotatedLazyService {
        public function __construct($arg) {

        }
        public function someMethod() {
            return 'success';
        }
    }

    class DICTestProvidedInterfaceImpl1 implements DICTestProvidedInterface {

    }

    class DICTestProvidedInterfaceImpl2 implements DICTestProvidedInterface {

    }

    class DICTestSimpleProvidedDecorator implements DICTestSimpleProvidedInterface {

        private $providedClass;

        public function setProvidedClass($providedClass) {
            $this->providedClass = $providedClass;
        }

        public function getProvidedClass() {
            return $this->providedClass;
        }
    }

    class DICTestProvidedDecorator implements DICTestProvidedInterface {

        private $providedClass;

        public function setProvidedClass($providedClass) {
            $this->providedClass = $providedClass;
        }

        public function getProvidedClass() {
            return $this->providedClass;
        }
    }

    class DICTestNamedProvidedImpl1Dependency {

        public $providedInterface1;

        public $providedInterface2;

        public function __construct(
            #[Inject(named: 'impl1')]
            DICTestProvidedInterface $providedInterface1,
            #[Inject(named: 'impl2')]
            DICTestProvidedInterface $providedInterface2
        ) {
            $this->providedInterface1 = $providedInterface1;
            $this->providedInterface2 = $providedInterface2;
        }
    }

    class DICTestSimpleProvidedImplDependency {

        public $providedInterface;

        #[Inject]
        public function __construct(DICTestSimpleProvidedInterface $providedInterface) {
            $this->providedInterface = $providedInterface;
        }

        /**
         * @param DICTestSimpleProvidedInterface $providedInterface
         */
        #[Inject]
        public function someMethod(DICTestSimpleProvidedInterface $providedInterface) {
            return $providedInterface;
        }
    }

    class DICTestProvider implements \rg\injektor\Provider {

        private $decorator;

        private $name;

        #[Inject]
        public function __construct(DICTestProvidedDecorator $decorator, $name = null) {
            $this->decorator = $decorator;
            $this->name = $name;
        }

        public function get() {
            switch ($this->name) {
                case 'impl1':
                    $this->decorator->setProvidedClass(new DICTestProvidedInterfaceImpl1());
                    break;
                case 'impl2':
                    $this->decorator->setProvidedClass(new DICTestProvidedInterfaceImpl2());
                    break;
            }
            return $this->decorator;
        }
    }

    class DICTestProviderNoAnnotation implements \rg\injektor\Provider {

        private $decorator;

        private $name;

        #[Inject]
        public function __construct(DICTestProvidedDecorator $decorator, $name = null) {
            $this->decorator = $decorator;
            $this->name = $name;
        }

        public function get() {
            return new DICTestProvidedInterfaceNoConfigImpl($this->name);
        }
    }

    class DICTestProvidedInterfaceNoConfigImpl implements DICTestProvidedInterfaceNoConfig {

        public $name;

        public function __construct($name) {
            $this->name = $name;
        }
    }

    class DICSimpleTestProvider implements \rg\injektor\Provider {

        private $decorator;

        private $name;

        #[Inject]
        public function __construct(DICTestSimpleProvidedDecorator $decorator, $name = null) {
            $this->decorator = $decorator;
            $this->name = $name;
        }

        public function get() {
            switch ($this->name) {
                case 'impl1':
                    $this->decorator->setProvidedClass(new DICTestProvidedInterfaceImpl1());
                    break;
                case 'impl2':
                    $this->decorator->setProvidedClass(new DICTestProvidedInterfaceImpl2());
                    break;
            }
            return $this->decorator;
        }
    }

    class DICTestInterfaceDependency {

        /**
         * @var \rg\injektor\DICTestInterface
         */
        #[Inject]
        public $dependency;
    }

    class DICTestInterfaceDependencyTwo {

        public $dependency;

        public function __construct(
            #[Inject(named: 'impl1')]
            DICTestProvidedInterface $dependency
        ) {
            $this->dependency = $dependency;
        }
    }

    class DICTestInterfaceDependencyTwoNoAnnotation {

        public $dependency;

        public function __construct(
            #[Inject(named: 'impl1')]
            \rg\injektor\DICTestProvidedInterfaceNoConfig $dependency
        ) {
            $this->dependency = $dependency;
        }
    }

    class DICTestSimpleProvidedInterfaceDependency {

        /**
         * @var DICTestSimpleProvidedInterface
         */
        public $dependency;

        /**
         * @param DICTestSimpleProvidedInterface $dependency
         */
        #[Inject]
        public function __construct(DICTestSimpleProvidedInterface $dependency) {
            $this->dependency = $dependency;
        }
    }

    class DICTestAnnotatedInterfacePropertyInjection {

        /**
         * @var \rg\injektor\DICTestAnnotatedInterface
         */
        #[Inject]
        public $dependency;
    }

    class DICTestDependsOnInterface {

        public $interface = true;

        public function __construct(DICTestInterface $interface = null) {
            $this->interface = $interface;
        }
    }

    class DICTestClassWithTypedProperties {

        #[Inject]
        public DICTestClassOne $one;

        #[Inject]
        public \rg\injektor\DICTestClassTwo $two;

        #[Inject]
        public ?DICTestClassThree $three;
    }
}

namespace {
    class DICTestClassThatAlsoExistsInPublicNamespace {
    }
}

namespace some\other\name\space {

    use rg\injektor\attributes\Inject;
    use rg\injektor\DICTestClassNoConstructor;

    use rg\injektor\DICTestAnnotatedInterface as SomeInterface;
    use rg\injektor as injektorNamespace;
    use rg\injektor\DICTestClassThatAlsoExistsInPublicNamespace;

    class ClassPropertyInjectionWithUseStatementSupport {

        /**
         * @var DICTestClassNoConstructor
         */
        #[Inject]
        public $dependency;

        /**
         * @var DICTestClassThatAlsoExistsInPublicNamespace
         */
        #[Inject]
        public $dependencyWithOtherClassInPublicNamespace;

        /**
         * @var \rg\injektor\DICTestClassThatAlsoExistsInPublicNamespace
         */
        #[Inject]
        public $dependencyWithOtherClassInPublicNamespaceFq;

        /**
         * @var \DICTestClassThatAlsoExistsInPublicNamespace
         */
        #[Inject]
        public $dependencyWithOtherClassInPublicNamespaceFqPublic;

        /**
         * @var DependencySameNamespace
         */
        #[Inject]
        public $dependencySameNamespace;

        /**
         * @var SomeInterface
         */
        #[Inject]
        public $dependencyInterfaceWithAlias;

        /**
         * @var injektorNamespace\DICTestAnnotatedSingleton
         */
        #[Inject]
        public $dependencyWithAlias;
    }

    class DependencySameNamespace {

    }
}
