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

class DependencyInjectionContainerTest extends \PHPUnit_Framework_TestCase {

    public function testGetInstance() {
        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instance);

        $this->assertInstanceOf('rg\injection\DICTestClassTwo', $instance->two);
        $this->assertInstanceOf('rg\injection\DICTestClassThree', $instance->three);
        $this->assertInstanceOf('rg\injection\DICTestClassThree', $instance->two->three);
        $this->assertInstanceOf('rg\injection\DICTestClassThree', $instance->getFour());
    }

    public function testGetInstanceWithInvalidParameterInjectionThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Expected tag @var not found in doc comment.');

        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassNoParamTypeHint');
    }

    public function testGetInstanceWithPrivateParameterInjectionThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Property two must not be private for property injection.');

        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassPrivateProperty');
    }

    public function testGetInstanceWithParameterInjectionAndDoubledAnnotationTakesFirstOne() {
        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassPropertyDoubledAnnotation');

        $this->assertInstanceOf('rg\injection\DICTestClassPropertyDoubledAnnotation', $instance);
        $this->assertInstanceOf('rg\injection\DICTestClassNoConstructor', $instance->two);
    }

    public function testGetConfiguredInstance() {
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\DICTestAbstractClass', array(
            'class' => 'rg\injection\DICTestClassOneConfigured',
        ));

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestAbstractClass');

        $this->assertInstanceOf('rg\injection\DICTestClassOneConfigured', $instance);
    }

    public function testAbstractInstanceThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Can not instanciate abstract class rg\injection\DICTestAbstractClass');

        $config = new Configuration(null);
        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestAbstractClass');
    }

    public function testGetDifferentInstancesWithTwoCalls() {
        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceTwo);

        $this->assertFalse($instanceOne === $instanceTwo);
    }

    public function testDontGetSingletonInstance() {
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\DICTestClassOne', array(
            'singleton' => false
        ));

        $dic = new DependencyInjectionContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceTwo);

        $this->assertTrue($instanceOne !== $instanceTwo);
    }

    public function testGetSingletonInstance() {
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\DICTestClassOne', array(
            'singleton' => true
        ));

        $dic = new DependencyInjectionContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');
        $instanceTwo = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceOne);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instanceTwo);

        $this->assertTrue($instanceOne === $instanceTwo);
    }

    public function testGetInstanceOfRealSingleton() {
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\DICTestSingleton', array(
            'singleton' => true
        ));
        $dic = new DependencyInjectionContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestSingleton');

        $this->assertInstanceOf('rg\injection\DICTestSingleton', $instance);
        $this->assertInstanceOf('rg\injection\DICTestClassNoConstructor', $instance->instance);
        $this->assertInstanceOf('rg\injection\DICTestClassNoConstructor', $instance->injectedProperty);
        $this->assertEquals('foo', $instance->foo);
    }

    public function testGetInstanceWithoutConstructor() {
        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoConstructor');

        $this->assertInstanceOf('rg\injection\DICTestClassNoConstructor', $instance);
    }

    public function testGetInstanceOfNotInjectableClassThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'not injectable');

        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassNoInject');
    }

    public function testGetInstanceOfClassWithoNoTypeHintThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Invalid argument without class typehint one');

        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint');
    }

    public function testGetInstanceWithConfiguredParameter() {
        $config = new Configuration(null);
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


        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint');

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals(123, $instance->two);
    }

    public function testGetInstanceWithConfiguredClassParameter() {
        $config = new Configuration(null);
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

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint');

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instance->two);
    }

    public function testGetInstanceWithConfiguredAndOptionalClassParameter() {
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\DICTestClassNoTypeHintOptionalArgument', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
            ),
        ));

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHintOptionalArgument', array(
        ));

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHintOptionalArgument', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertEquals('bar', $instance->two);
    }

    public function testGetInstanceWithConfiguredAndDefaultClassParameter() {
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\DICTestClassNoTypeHint', array(
            'params' => array(
                'two' => array(
                    'class' => 'rg\injection\DICTestClassOne'
                ),
            ),
        ));

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint', array(
            'one' => 'foo'
        ));

        $this->assertInstanceOf('rg\injection\DICTestClassNoTypeHint', $instance);
        $this->assertEquals('foo', $instance->one);
        $this->assertInstanceOf('rg\injection\DICTestClassOne', $instance->two);
    }

    public function testGetInstanceWithWrongConfiguredParameterThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Invalid argument without class typehint two');
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\DICTestClassNoTypeHint', array(
            'params' => array(
                'one' => array(
                    'value' => 'foo',
                ),
            ),
        ));

        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass('rg\injection\DICTestClassNoTypeHint');
    }

    public function testCallMethodOnObject() {
        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $actual = $dic->callMethodOnObject($instance, 'getSomething');

        $this->assertEquals('barfoo', $actual);
    }

    public function testCallMethodWithMixedParameters() {
        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $actual = $dic->callMethodOnObject($instance, 'getSomethingTwo', array(
            'three' => new DICTestClassThree()
        ));

        $this->assertEquals('barfoo', $actual);
    }

    public function testCallMethodWithoutParametersOnObject() {
        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassTwo');

        $actual = $dic->callMethodOnObject($instance, 'getSomething');

        $this->assertEquals('bar', $actual);
    }

    public function testCallNotInjectableMethodThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'not injectable');

        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $dic->callMethodOnObject($instance, 'getSomethingNotInjectible');
    }

    public function testCallMethodWithoutTypehintOnObjectThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'not injectable');

        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $dic->callMethodOnObject($instance, 'noTypeHint');
    }

    public function testCallUndefinedMethodThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'Method undefined not found in rg\injection\DICTestClassOne');

        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $dic->callMethodOnObject($instance, 'undefined');
    }

    public function testCallMagicMethodThrowsException() {
        $this->setExpectedException('rg\injection\InjectionException', 'not allowed to call magic method');

        $config = new Configuration(null);

        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestClassOne');

        $dic->callMethodOnObject($instance, '__get');
    }

    public function testAnnotatedSingleton() {
        $config = new Configuration(null);
        $dic = new DependencyInjectionContainer($config);

        $instanceOne = $dic->getInstanceOfClass('rg\injection\DICTestAnnotatedSingleton');
        $instanceTwo = $dic->getInstanceOfClass('rg\injection\DICTestAnnotatedSingleton');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedSingleton', $instanceOne);
        $this->assertInstanceOf('rg\injection\DICTestAnnotatedSingleton', $instanceTwo);
        $this->assertTrue($instanceOne === $instanceTwo);
    }

    public function testAnnotatedImplementedBy() {
        $config = new Configuration(null);
        $dic = new DependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\DICTestAnnotatedInterface');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceImpl', $instance);
    }

    public function testNamedAnnotation() {
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\DICTestAnnotatedInterface', array(
            'named' => array(
                'implOne' => 'rg\injection\DICTestAnnotatedInterfaceImplOne',
                'implTwo' => 'rg\injection\DICTestAnnotatedInterfaceImplTwo'
            )
        ));
        $dic = new DependencyInjectionContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestNamed');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceImplOne', $instance->one);
        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceImplTwo', $instance->two);
    }

    public function testNamedAnnotationAtMethodCall() {
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\DICTestAnnotatedInterface', array(
            'named' => array(
                'implOne' => 'rg\injection\DICTestAnnotatedInterfaceImplTwo',
                'implTwo' => 'rg\injection\DICTestAnnotatedInterfaceImplOne'
            )
        ));
        $dic = new DependencyInjectionContainer($config);
        $instance = $dic->getInstanceOfClass('rg\injection\DICTestNamed');

        $returnValue = $dic->callMethodOnObject($instance, 'doSomething');

        $this->assertInstanceOf('rg\injection\DICTestAnnotatedInterfaceImplTwo', $returnValue);
    }

    public function testAspects() {
        $config = new Configuration(null);
        $dic = new DependencyInjectionContainer($config);

        $instance = new DICTestAspects();

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
}

class DICTestClassOne {
    /**
     * @var \rg\injection\DICTestClassTwo
     */
    public $two;
    /**
     * @var \rg\injection\DICTestClassThree
     */
    public $three;

    /**
     * @inject
     * @var \rg\injection\DICTestClassThree
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
    public function __construct(DICTestClassTwo $two, DICTestClassThree $three) {
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
     * @var \rg\injection\DICTestClassThree
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

class DICTestClassNoTypeHint {

    public $one;
    public $two;

    /**
     * @inject
     */
    public function __construct($one, $two) {
        $this->one = $one;
        $this->two  = $two;
    }
}

class DICTestClassNoTypeHintOptionalArgument {

    public $one;
    public $two;

    public function __construct($one, $two = 'bar') {
        $this->one = $one;
        $this->two = $two;
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
     * @var \rg\injection\DICTestClassNoConstructor
     * @var \rg\injection\DICTestClassPrivateProperty
     */
    public $two;
}

class DICTestClassNoConstructor {
}

abstract class DICTestAbstractClass {
}

interface DICTestInterface {
}

/**
 * @implementedBy rg\injection\DICTestAnnotatedInterfaceImpl
 */
interface DICTestAnnotatedInterface {
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
     * @var \rg\injection\DICTestAnnotatedInterface
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
     * @return \rg\injection\DICTestAnnotatedInterface
     */
    public function doSomething(DICTestAnnotatedInterface $one) {
        return $one;
    }
}

class DICTestSingleton {
    public $foo;
    public $instance;

    /**
     * @inject
     * @var rg\injection\DICTestClassNoConstructor
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
        return new DICTestSingleton('foo', $instance);
    }
}

/**
 * @singleton
 */
class DICTestAnnotatedSingleton {
}

class DICTestAspects {
    public $one;
    public $two;

    /**
     * @inject
     * @param \rg\injection\DICTestAnnotatedSingleton $one
     * @param $two
     * @before \rg\injection\BeforeAspect one=1&two=bar
     * @before \rg\injection\BeforeAspect
     * @after \rg\injection\AfterAspect foo=bar
     */
    public function aspectFunction(DICTestAnnotatedSingleton $one, $two) {
        $this->one = $one;
        $this->two = $two;

        return 'foo';
    }
}

class BeforeAspect implements \rg\injection\aspects\Before {
   public function execute($aspectArguments, $className, $functionName, $functionArguments) {
       $functionArguments['two'] = array(
           $functionArguments['two'],
           $aspectArguments,
           $className,
           $functionName
       );
       return $functionArguments;
   }
}

class AfterAspect implements \rg\injection\aspects\After {
    public function execute($aspectArguments, $className, $functionName, $result) {
        $result = array(
            $result,
            $aspectArguments,
            $className,
            $functionName
        );
        return $result;
    }
}