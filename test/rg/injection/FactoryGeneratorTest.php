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

class FactoryGeneratorTest extends \PHPUnit_Framework_TestCase {

    public function testGenerateFactory() {
        $config = new Configuration(null);
        $config->setClassConfig('rg\injection\FGTestClassOne', array(
            'singleton' => true
        ));
        $config->setClassConfig('rg\injection\FGTestClassFour', array(
            'singleton' => true
        ));
        $config->setClassConfig('rg\injection\FGTestClassThree', array(
            'params' => array(
                'foo' => array(
                    'value' => 'foo'
                ),
                'four' => array(
                    'class' => 'rg\injection\FGTestClassFour'
                )
            )
        ));
        $factoryGenerator = new TestingFactoryGenerator($config);
        $factoryGenerator->processFileForClass('rg\injection\FGTestClassOne');

        $expected = array (
'rg\\injection\\FGTestClassSimple' => '<?php

/** @namespace */
namespace rg\injection\generated;

class RgInjectionFGTestClassSimpleFactory
{

    public static function getInstance(array $parameters = array())
    {
        $instance = new \rg\injection\FGTestClassSimple();
        return $instance;
    }


}

',
'rg\\injection\\FGTestClassFour' => '<?php

/** @namespace */
namespace rg\\injection\\generated;

class RgInjectionFGTestClassFourFactory
{

    private static $instance = null;

    public static function getInstance(array $parameters = array())
    {
        if (self::$instance) {
            return self::$instance;
        }

        $simple = isset($parameters[\'simple\']) ? $parameters[\'simple\'] : rg\injection\generated\RgInjectionFGTestClassSimpleFactory::getInstance();
        $injectedProperty = rg\injection\generated\RgInjectionFGTestClassSimpleFactory::getInstance();

        $instance = RgInjectionFGTestClassFourProxy::getInstance($simple, $injectedProperty);
        self::$instance = $instance;
        return $instance;
    }


}

class RgInjectionFGTestClassFourProxy extends \rg\injection\FGTestClassFour
{

    public static function getInstance($simple, $injectedProperty)
    {
        $instance = parent::getInstance($simple);
        $this->injectedProperty = $injectedProperty;
        return $instance;
    }


}

',
'rg\\injection\\FGTestClassThree' => '<?php

/** @namespace */
namespace rg\\injection\\generated;

class RgInjectionFGTestClassThreeFactory
{

    public static function getInstance(array $parameters = array())
    {
        $foo = isset($parameters[\'foo\']) ? $parameters[\'foo\'] : \'foo\';
        $four = isset($parameters[\'four\']) ? $parameters[\'four\'] : rg\injection\generated\RgInjectionFGTestClassFourFactory::getInstance();

        $instance = new \\rg\\injection\\FGTestClassThree($foo, $four);
        return $instance;
    }

    public static function callGetSomething($object)
    {
        return $object->getSomething();
    }


}

',

'rg\\injection\\FGTestClassTwo' => '<?php

/** @namespace */
namespace rg\\injection\\generated;

class RgInjectionFGTestClassTwoFactory
{

    public static function getInstance(array $parameters = array())
    {
        $three = isset($parameters[\'three\']) ? $parameters[\'three\'] : rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();

        $instance = new \\rg\\injection\\FGTestClassTwo($three);
        return $instance;
    }

    public static function callGetSomething($object)
    {
        return $object->getSomething();
    }


}

',
'rg\\injection\\FGTestClassOne' => '<?php

/** @namespace */
namespace rg\\injection\\generated;

class RgInjectionFGTestClassOneFactory
{

    private static $instance = null;

    public static function getInstance(array $parameters = array())
    {
        if (self::$instance) {
            return self::$instance;
        }

        $two = isset($parameters[\'two\']) ? $parameters[\'two\'] : rg\\injection\\generated\\RgInjectionFGTestClassTwoFactory::getInstance();
        $three = isset($parameters[\'three\']) ? $parameters[\'three\'] : rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();
        $four = rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();

        $instance = new RgInjectionFGTestClassOneProxy($two, $three, $four);
        self::$instance = $instance;
        return $instance;
    }

    public static function callGetFour($object)
    {
        return $object->getFour();
    }

    public static function callGetSomething($object, array $parameters = array())
    {
        $two = isset($parameters[\'two\']) ? $parameters[\'two\'] : rg\\injection\\generated\\RgInjectionFGTestClassTwoFactory::getInstance();
        $three = isset($parameters[\'three\']) ? $parameters[\'three\'] : rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();

        return $object->getSomething($two, $three);
    }

    public static function callMethodRestriction($object)
    {
        if (isset($_SERVER["request_method"]) && strtolower($_SERVER["request_method"]) !== "post") {
            throw new \RuntimeException("invalid http method " . $_SERVER["REQUEST_METHOD"] . " for rg\injection\FGTestClassOne::methodRestriction(), POST expected");
        }


        return $object->methodRestriction();
    }


}

class RgInjectionFGTestClassOneProxy extends \\rg\\injection\\FGTestClassOne
{

    public function __construct($two, $three, $four)
    {
        $this->four = $four;
        parent::__construct($two, $three);
    }


}

');
        $this->assertEquals($expected, $factoryGenerator->files);
    }

}

class TestingFactoryGenerator extends FactoryGenerator {
    public $files = array();

    public function processFileForClass($fullClassName) {
        $file = $this->generateFileForClass($fullClassName, false);
        if ($file) {
            $this->files[$fullClassName] = $file->generate();
        }
    }
}

class FGTestClassOne {
    /**
     * @var \rg\injection\FGTestClassTwo
     */
    public $two;
    /**
     * @var \rg\injection\FGTestClassThree
     */
    public $three;

    /**
     * @inject
     * @var \rg\injection\FGTestClassThree
     */
    protected $four;

    /**
     * @return FGTestClassThree
     */
    public function getFour() {
        return $this->four;
    }

    /**
     * @inject
     * @param FGTestClassTwo $two
     * @param FGTestClassThree $three
     */
    public function __construct(FGTestClassTwo $two, FGTestClassThree $three) {
        $this->two = $two;
        $this->three = $three;
    }

    /**
     * @inject
     * @param FGTestClassTwo $two
     * @param FGTestClassThree $three
     * @return string
     */
    public function getSomething(FGTestClassTwo $two, FGTestClassThree $three) {
        return $two->getSomething() . $three->getSomething();
    }

    public function getSomethingNotInjectible(FGTestClassTwo $two, FGTestClassThree $three) {
        return $two->getSomething() . $three->getSomething();
    }

    public function noTypeHint($foo) {

    }

    /**
     * @method POST
     */
    public function methodRestriction() {

    }
}


class FGTestClassTwo {
    /**
     * @var \rg\injection\FGTestClassThree
     */
    public $three;
    /**
     * @inject
     * @param FGTestClassThree $three
     */
    public function __construct(FGTestClassThree $three) {
        $this->three = $three;
    }

    public function getSomething() {
        return 'bar';
    }
}

class FGTestClassThree {

    public function __construct($foo, $four) {

    }

    public function getSomething() {
        return 'foo';
    }
}

class FGTestClassFour {

    /**
     * @inject
     * @var rg\injection\FGTestClassSimple
     */
    protected $injectedProperty;

    private function __construct() {

    }

    /**
     * @inject
     * @static
     * @param FGTestClassSimple $simple
     * @return FGTestClassFour
     */
    public static function getInstance(FGTestClassSimple $simple) {
        return new FGTestClassFour();
    }
}

class FGTestClassSimple {

}