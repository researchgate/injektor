<?php
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injektor\generators;

use rg\injektor\FactoryDependencyInjectionContainer;
use rg\injektor\Configuration;

class FactoryGeneratorTest extends \PHPUnit_Framework_TestCase {

    /**
     * @param string $file
     * @param string $content
     * @dataProvider provider
     */
    public function testGenerateFactory($file, $content) {
        if (strtolower(substr(php_uname(), 0, 7)) == 'windows') {
            $this->markTestSkipped('Skipped since doesnt work on windows.');
        }

        FactoryDependencyInjectionContainer::$prefix = '';

        $config = new Configuration(null, '');
        $config->setClassConfig('rg\injektor\generators\FGTestClassOne', array(
            'singleton' => true
        ));
        $config->setClassConfig('rg\injektor\generators\FGTestClassFour', array(
            'singleton' => true
        ));
        $config->setClassConfig('rg\injektor\generators\FGTestClassThree', array(
            'params' => array(
                'foo' => array(
                    'value' => 'foo'
                ),
                'four' => array(
                    'class' => 'rg\injektor\generators\FGTestClassFour'
                )
            )
        ));
        $factoryGenerator = new TestingFactoryGenerator($config, '');
        $factoryGenerator->processFileForClass('rg\injektor\generators\FGTestClassOne');

        $this->assertEquals($content, $factoryGenerator->files[$file]);
    }

    public function provider() {
        $expected = array(
            'rg\\injektor\\generators\\FGTestClassSimple' => <<<EOF
<?php

namespace rg\injektor\generated;

class rg__injektor__generators__FGTestClassSimpleFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$instance = new \\rg\\injektor\\generators\\FGTestClassSimple();
        return \$instance;
    }


}


EOF
        ,
            'rg\\injektor\\generators\\FGTestClassFour' => <<<EOF
<?php

namespace rg\\injektor\\generated;

require_once '/rg__injektor__generators__FGTestClassSimpleFactory.php';

class rg__injektor__generators__FGTestClassFourProxy extends \\rg\injektor\generators\\FGTestClassFour
{

    public function propertyInjectioninjectedProperty()
    {
        \$this->injectedProperty = \\rg\injektor\\generated\\rg__injektor__generators__FGTestClassSimpleFactory::getInstance(array (
        ));
    }


}

class rg__injektor__generators__FGTestClassFourFactory
{

    private static \$instance = array();

    public static function getInstance(array \$parameters = array())
    {
        \$singletonKey = json_encode(\$parameters) . "#" . getmypid();
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        \$methodParameters['simple'] = array_key_exists('simple', \$parameters) ? \$parameters['simple'] : \\rg\injektor\generated\\rg__injektor__generators__FGTestClassSimpleFactory::getInstance(array (
        ));
        \$simple = array_key_exists('simple', \$methodParameters) ? \$methodParameters['simple'] : \\rg\injektor\generated\\rg__injektor__generators__FGTestClassSimpleFactory::getInstance(array (
        ));

        \$instance = rg__injektor__generators__FGTestClassFourProxy::getInstance(\$simple);
        self::\$instance[\$singletonKey] = \$instance;
        \$instance->propertyInjectioninjectedProperty();
        return \$instance;
    }

    public static function callGetInstance(\$object, array \$parameters = array())
    {
        \$methodParameters = array();
        \$methodParameters['simple'] = array_key_exists('simple', \$parameters) ? \$parameters['simple'] : \\rg\injektor\\generated\\rg__injektor__generators__FGTestClassSimpleFactory::getInstance(array (
        ));
        \$simple = array_key_exists('simple', \$methodParameters) ? \$methodParameters['simple'] : \\rg\injektor\\generated\\rg__injektor__generators__FGTestClassSimpleFactory::getInstance(array (
        ));
        \$result = \$object->getInstance(\$simple);


        return \$result;
    }


}


EOF
        ,
            'rg\\injektor\\generators\\FGTestClassThree' => <<<EOF
<?php

namespace rg\\injektor\\generated;

require_once '/rg__injektor__generators__FGTestClassFourFactory.php';

class rg__injektor__generators__FGTestClassThreeFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$methodParameters['foo'] = array_key_exists('foo', \$parameters) ? \$parameters['foo'] : 'foo';
        \$methodParameters['four'] = array_key_exists('four', \$parameters) ? \$parameters['four'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassFourFactory::getInstance(array (
        ));
        \$foo = array_key_exists('foo', \$methodParameters) ? \$methodParameters['foo'] : 'foo';
        \$four = array_key_exists('four', \$methodParameters) ? \$methodParameters['four'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassFourFactory::getInstance(array (
        ));

        \$instance = new \\rg\\injektor\\generators\\FGTestClassThree(\$foo, \$four);
        return \$instance;
    }

    public static function callGetSomething(\$object)
    {
        \$methodParameters = array();
        \$result = \$object->getSomething();


        return \$result;
    }


}


EOF
        ,

            'rg\\injektor\\generators\\FGTestClassTwo' => <<<EOF
<?php

namespace rg\\injektor\\generated;

require_once '/rg__injektor__generators__FGTestClassThreeFactory.php';

class rg__injektor__generators__FGTestClassTwoFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassThreeFactory::getInstance(array (
        ));
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassThreeFactory::getInstance(array (
        ));

        \$instance = new \\rg\\injektor\\generators\\FGTestClassTwo(\$three);
        return \$instance;
    }

    public static function callGetSomething(\$object)
    {
        \$methodParameters = array();
        \$result = \$object->getSomething();


        return \$result;
    }


}


EOF
        ,
            'rg\\injektor\\generators\\FGTestClassOne' => <<<EOF
<?php

namespace rg\\injektor\\generated;

require_once '/rg__injektor__generators__FGTestClassTwoFactory.php';
require_once '/rg__injektor__generators__FGTestClassThreeFactory.php';

class rg__injektor__generators__FGTestClassOneProxy extends \\rg\\injektor\\generators\\FGTestClassOne
{

    public function propertyInjectionfour()
    {
        \$this->four = \\rg\injektor\\generated\\rg__injektor__generators__FGTestClassThreeFactory::getInstance(array (
        ));
    }


}

class rg__injektor__generators__FGTestClassOneFactory
{

    private static \$instance = array();

    public static function getInstance(array \$parameters = array())
    {
        \$singletonKey = json_encode(\$parameters) . "#" . getmypid();
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        \$methodParameters['two'] = array_key_exists('two', \$parameters) ? \$parameters['two'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassTwoFactory::getInstance(array (
        ));
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassThreeFactory::getInstance(array (
        ));
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassTwoFactory::getInstance(array (
        ));
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassThreeFactory::getInstance(array (
        ));

        \$instance = new rg__injektor__generators__FGTestClassOneProxy(\$two, \$three);
        self::\$instance[\$singletonKey] = \$instance;
        \$instance->propertyInjectionfour();
        return \$instance;
    }

    public static function callGetFour(\$object)
    {
        \$methodParameters = array();
        \$result = \$object->getFour();


        return \$result;
    }

    public static function callGetSomething(\$object, array \$parameters = array())
    {
        \$methodParameters = array();
        \$methodParameters['two'] = array_key_exists('two', \$parameters) ? \$parameters['two'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassTwoFactory::getInstance(array (
        ));
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassThreeFactory::getInstance(array (
        ));
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassTwoFactory::getInstance(array (
        ));
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : \\rg\\injektor\\generated\\rg__injektor__generators__FGTestClassThreeFactory::getInstance(array (
        ));
        \$result = \$object->getSomething(\$two, \$three);


        return \$result;
    }

    public static function callGetSomethingNotInjectible(\$object, array \$parameters = array())
    {
        \$methodParameters = array();
        \$methodParameters['two'] = array_key_exists('two', \$parameters) ? \$parameters['two'] : null;
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : null;
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : null;
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : null;
        \$result = \$object->getSomethingNotInjectible(\$two, \$three);


        return \$result;
    }

    public static function callNoTypeHint(\$object, array \$parameters = array())
    {
        \$methodParameters = array();
        \$methodParameters['foo'] = array_key_exists('foo', \$parameters) ? \$parameters['foo'] : null;
        \$foo = array_key_exists('foo', \$methodParameters) ? \$methodParameters['foo'] : null;
        \$result = \$object->noTypeHint(\$foo);


        return \$result;
    }

    public static function callMethodRestriction(\$object, array \$parameters = array())
    {
        \$methodParameters = array();
        \$methodParameters['two'] = array_key_exists('two', \$parameters) ? \$parameters['two'] : NULL;
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : NULL;
        \$result = \$object->methodRestriction(\$two);


        return \$result;
    }


}


EOF
        );

        $data = array();
        foreach ($expected as $file => $content) {
            $data[] = array(
                $file, $content,
            );
        }

        return $data;
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
     * @var \rg\injektor\generators\FGTestClassTwo
     */
    public $two;
    /**
     * @var \rg\injektor\generators\FGTestClassThree
     */
    public $three;

    /**
     * @inject
     * @var \rg\injektor\generators\FGTestClassThree
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
     * @inject
     * @param mixed $two
     */
    public function methodRestriction($two = null) {

    }
}


class FGTestClassTwo {
    /**
     * @var \rg\injektor\generators\FGTestClassThree
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
     * @var \rg\injektor\generators\FGTestClassSimple
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
