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
         * @inject
         * @var \rg\injektor\DICTestClassThree
         */
        protected $four;

        /**
         * @return DICTestClassThree
         */
        public function getFour() {
            return $this->four;
        }

        /**
         * @inject
         * @param DICTestClassTwo $two
         * @param DICTestClassThree $three
         */
        public function __construct(DICTestClassTwo $two, DICTestClassThree $three = null) {
            $this->two = $two;
            $this->three = $three;
        }

        /**
         * @inject
         * @param DICTestClassTwo $two
         * @param DICTestClassThree $three
         * @return string
         */
        public function getSomething(DICTestClassTwo $two, DICTestClassThree $three) {
            return $two->getSomething() . $three->getSomething();
        }

        /**
         * @inject
         * @param DICTestClassTwo $two
         * @param $three
         * @return string
         */
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
         * @inject
         * @param DICTestClassThree $three
         */
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

        /**
         * @inject
         * @var \rg\injektor\DICProvidedTestClassNoTypeHint {"one":"foo","two":"bar"}
         */
        public $injectedProperty;

        /**
         * @inject
         * @param DICProvidedTestClassNoTypeHint $class {"one":"foo","two":"bar"}
         */
        public function __construct(DICProvidedTestClassNoTypeHint $class) {
            $this->class = $class;
        }

        /**
         * @inject
         * @param DICProvidedTestClassNoTypeHint $class {"one":"foo","two":"bar"}
         */
        public function someMethod(DICProvidedTestClassNoTypeHint $class) {
            $this->methodClass = $class;
        }
    }

    /**
     * @providedBy \rg\injektor\DICProvidedTestClassNoTypeHintProvider
     */
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
         * @inject
         * @var \rg\injektor\DICProvidedTestClassNoTypeHintNamed
         */
        public $provided;
    }

    class DICProvidedTestClassNoTypeHintNamedUserSomeName {

        /**
         * @inject
         * @var \rg\injektor\DICProvidedTestClassNoTypeHintNamed
         * @named someName
         */
        public $provided;
    }

    /**
     * @providedBy default \rg\injektor\DICProvidedTestClassNoTypeHintProvider {"one":1,"two":2}
     * @providedBy someName \rg\injektor\DICProvidedTestClassNoTypeHintProvider {"one":3,"two":4}
     */
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

        /**
         * @inject
         * @var \rg\injektor\DICTestClassNoTypeHint {"one":"foo","two":"bar"}
         */
        public $injectedProperty;

        /**
         * @inject
         * @param DICTestClassNoTypeHint $class {"one":"foo","two":"bar"}
         */
        public function __construct(DICTestClassNoTypeHint $class) {
            $this->class = $class;
        }

        /**
         * @inject
         * @param DICTestClassNoTypeHint $class {"one":"foo","two":"bar"}
         */
        public function someMethod(DICTestClassNoTypeHint $class) {
            $this->methodClass = $class;
        }
    }

    class DICTestClassNoTypeHint {

        public $one;

        public $two;

        /**
         * @inject
         */
        public function __construct($one, $two) {
            $this->one = $one;
            $this->two = $two;
        }
    }

    class DICTestClassNoTypeHintOptionalArgument {

        public $one;

        public $two;

        public $ar;

        public function __construct($one, $two = 'bar', array $ar = array()) {
            $this->one = $one;
            $this->two = $two;
            $this->ar = $ar;
        }
    }

    class DICTestClassNoParamTypeHint {

        /**
         * @inject
         */
        public $two;
    }

    class DICTestClassPrivateProperty {

        /**
         * @inject
         * @var DICTestClassNoConstructor
         */
        private $two;
    }

    class DICTestClassPropertyDoubledAnnotation {

        /**
         * @inject
         * @var \rg\injektor\DICTestClassNoConstructor
         * @var \rg\injektor\DICTestClassPrivateProperty
         */
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

        /**
         * @inject
         * @var \rg\injektor\DICTestAnnotatedInterface
         * @named implTwo
         */
        public $two;

        /**
         * @inject
         * @param DICTestAnnotatedInterface $one
         * @named implOne $one
         */
        public function __construct(DICTestAnnotatedInterface $one) {
            $this->one = $one;
        }

        /**
         * @inject
         * @param DICTestAnnotatedInterface $one
         * @named implOne $one
         * @return \rg\injektor\DICTestAnnotatedInterface
         */
        public function doSomething(DICTestAnnotatedInterface $one) {
            return $one;
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
         * @inject
         * @var \rg\injektor\DICTestAnnotatedInterfaceNamedConfig
         * @named implTwo
         */
        public $two;

        /**
         * @inject
         * @param DICTestAnnotatedInterfaceNamedConfig $one
         * @named implOne $one
         */
        public function __construct(DICTestAnnotatedInterfaceNamedConfig $one) {
            $this->one = $one;
        }

        /**
         * @inject
         * @param DICTestAnnotatedInterfaceNamedConfig $one
         * @named implOne $one
         * @return \rg\injektor\DICTestAnnotatedInterfaceNamedConfig
         */
        public function doSomething(DICTestAnnotatedInterfaceNamedConfig $one) {
            return $one;
        }
    }

    class DICTestSingleton {

        public $foo;

        public $instance;

        /**
         * @inject
         * @var \rg\injektor\DICTestClassNoConstructor
         */
        public $injectedProperty;

        private function __construct($foo, $instance) {
            $this->foo = $foo;
            $this->instance = $instance;
        }

        /**
         * @inject
         * @static
         * @param DICTestClassNoConstructor $instance
         * @return Singleton
         */
        public static function getInstance(DICTestClassNoConstructor $instance) {
            return new static('foo', $instance);
        }
    }

    /**
     * @singleton
     */
    class DICTestAnnotatedSingleton {

    }

    class DICTestService {

        public function __construct($arg) {

        }
    }

    /**
     * @service
     */
    class DICTestAnnotatedService {
        public function __construct($arg) {

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

        /**
         * @inject
         * @named impl1 $providedInterface1
         * @named impl2 $providedInterface2
         */
        public function __construct(DICTestProvidedInterface $providedInterface1, DICTestProvidedInterface $providedInterface2) {
            $this->providedInterface1 = $providedInterface1;
            $this->providedInterface2 = $providedInterface2;
        }
    }

    class DICTestSimpleProvidedImplDependency {

        public $providedInterface;

        /**
         * @inject
         */
        public function __construct(DICTestSimpleProvidedInterface $providedInterface) {
            $this->providedInterface = $providedInterface;
        }

        /**
         * @inject
         * @param DICTestSimpleProvidedInterface $providedInterface
         */
        public function someMethod(DICTestSimpleProvidedInterface $providedInterface) {
            return $providedInterface;
        }
    }

    class DICTestProvider implements \rg\injektor\Provider {

        private $decorator;

        private $name;

        /**
         * @inject
         */
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

        /**
         * @inject
         */
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

        /**
         * @inject
         */
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
         * @inject
         * @var \rg\injektor\DICTestInterface
         */
        public $dependency;
    }

    class DICTestInterfaceDependencyTwo {

        public $dependency;

        /**
         * @inject
         * @named impl1 $dependency
         */
        public function __construct(DICTestProvidedInterface $dependency) {
            $this->dependency = $dependency;
        }
    }

    class DICTestInterfaceDependencyTwoNoAnnotation {

        public $dependency;

        /**
         * @inject
         * @named impl1 $dependency
         */
        public function __construct(\rg\injektor\DICTestProvidedInterfaceNoConfig $dependency) {
            $this->dependency = $dependency;
        }
    }

    class DICTestSimpleProvidedInterfaceDependency {

        /**
         * @var DICTestSimpleProvidedInterface
         */
        public $dependency;

        /**
         * @inject
         * @param DICTestSimpleProvidedInterface $dependency
         */
        public function __construct(DICTestSimpleProvidedInterface $dependency) {
            $this->dependency = $dependency;
        }
    }

    class DICTestAnnotatedInterfacePropertyInjection {

        /**
         * @inject
         * @var \rg\injektor\DICTestAnnotatedInterface
         */
        public $dependency;
    }

    class DICTestDependsOnInterface {

        public $interface = true;

        public function __construct(DICTestInterface $interface = null) {
            $this->interface = $interface;
        }
    }
}

namespace {
    class DICTestClassThatAlsoExistsInPublicNamespace {
    }
}

namespace some\other\name\space {

    use rg\injektor\DICTestClassNoConstructor;

    use rg\injektor\DICTestAnnotatedInterface as SomeInterface;
    use rg\injektor as injektorNamespace;
    use rg\injektor\DICTestClassThatAlsoExistsInPublicNamespace;

    class ClassPropertyInjectionWithUseStatementSupport {

        /**
         * @inject
         * @var DICTestClassNoConstructor
         */
        public $dependency;

        /**
         * @inject
         * @var DICTestClassThatAlsoExistsInPublicNamespace
         */
        public $dependencyWithOtherClassInPublicNamespace;

        /**
         * @inject
         * @var \rg\injektor\DICTestClassThatAlsoExistsInPublicNamespace
         */
        public $dependencyWithOtherClassInPublicNamespaceFq;

        /**
         * @inject
         * @var \DICTestClassThatAlsoExistsInPublicNamespace
         */
        public $dependencyWithOtherClassInPublicNamespaceFqPublic;

        /**
         * @inject
         * @var DependencySameNamespace
         */
        public $dependencySameNamespace;

        /**
         * @inject
         * @var SomeInterface
         */
        public $dependencyInterfaceWithAlias;

        /**
         * @inject
         * @var injektorNamespace\DICTestAnnotatedSingleton
         */
        public $dependencyWithAlias;
    }

    class DependencySameNamespace {

    }
}
