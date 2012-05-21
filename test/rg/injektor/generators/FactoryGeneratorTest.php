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

    public function testGenerateFactory() {
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

        $expected = array(
'rg\\injektor\\generators\\FGTestClassSimple' => <<<EOF
<?php

namespace rg\injektor\generated;

class RgInjektorGeneratorsFGTestClassSimpleFactory
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

require_once '/RgInjektorGeneratorsFGTestClassSimpleFactory.php';

class RgInjektorGeneratorsFGTestClassFourProxy extends \\rg\injektor\generators\\FGTestClassFour
{

    public function propertyInjectioninjectedProperty()
    {
        \$this->injectedProperty = \\rg\injektor\generated\RgInjektorGeneratorsFGTestClassSimpleFactory::getInstance(array (
        ));
    }


}

class RgInjektorGeneratorsFGTestClassFourFactory
{

    private static \$instance = array();

    public static function getInstance(array \$parameters = array())
    {
        \$singletonKey = json_encode(\$parameters) . "#" . getmypid();
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        \$methodParameters['simple'] = array_key_exists('simple', \$parameters) ? \$parameters['simple'] : \\rg\injektor\generated\\RgInjektorGeneratorsFGTestClassSimpleFactory::getInstance(array (
        ));
        \$simple = array_key_exists('simple', \$methodParameters) ? \$methodParameters['simple'] : \\rg\injektor\generated\\RgInjektorGeneratorsFGTestClassSimpleFactory::getInstance(array (
        ));

        \$instance = RgInjektorGeneratorsFGTestClassFourProxy::getInstance(\$simple);
        self::\$instance[\$singletonKey] = \$instance;
        \$instance->propertyInjectioninjectedProperty();
        return \$instance;
    }

    public static function callGetInstance(\$object, array \$parameters = array())
    {
        \$methodParameters = array();
        \$methodParameters['simple'] = array_key_exists('simple', \$parameters) ? \$parameters['simple'] : \\rg\injektor\generated\RgInjektorGeneratorsFGTestClassSimpleFactory::getInstance(array (
        ));
        \$simple = array_key_exists('simple', \$methodParameters) ? \$methodParameters['simple'] : \\rg\injektor\generated\RgInjektorGeneratorsFGTestClassSimpleFactory::getInstance(array (
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

require_once '/RgInjektorGeneratorsFGTestClassFourFactory.php';

class RgInjektorGeneratorsFGTestClassThreeFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$methodParameters['foo'] = array_key_exists('foo', \$parameters) ? \$parameters['foo'] : 'foo';
        \$methodParameters['four'] = array_key_exists('four', \$parameters) ? \$parameters['four'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassFourFactory::getInstance(array (
        ));
        \$foo = array_key_exists('foo', \$methodParameters) ? \$methodParameters['foo'] : 'foo';
        \$four = array_key_exists('four', \$methodParameters) ? \$methodParameters['four'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassFourFactory::getInstance(array (
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

require_once '/RgInjektorGeneratorsFGTestClassThreeFactory.php';

class RgInjektorGeneratorsFGTestClassTwoFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassThreeFactory::getInstance(array (
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
'rg\\injektor\\generators\\FGTestBeforeAspect' => <<<EOF
<?php

namespace rg\injektor\generated;

class RgInjektorGeneratorsFGTestBeforeAspectFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$instance = new \\rg\injektor\generators\\FGTestBeforeAspect();
        return \$instance;
    }

    public static function callExecute(\$object, array \$parameters = array())
    {
        \$methodParameters = array();
        \$methodParameters['aspectArguments'] = array_key_exists('aspectArguments', \$parameters) ? \$parameters['aspectArguments'] : null;
        \$methodParameters['className'] = array_key_exists('className', \$parameters) ? \$parameters['className'] : null;
        \$methodParameters['functionName'] = array_key_exists('functionName', \$parameters) ? \$parameters['functionName'] : null;
        \$methodParameters['functionArguments'] = array_key_exists('functionArguments', \$parameters) ? \$parameters['functionArguments'] : null;
        \$aspectArguments = array_key_exists('aspectArguments', \$methodParameters) ? \$methodParameters['aspectArguments'] : null;
        \$className = array_key_exists('className', \$methodParameters) ? \$methodParameters['className'] : null;
        \$functionName = array_key_exists('functionName', \$methodParameters) ? \$methodParameters['functionName'] : null;
        \$functionArguments = array_key_exists('functionArguments', \$methodParameters) ? \$methodParameters['functionArguments'] : null;
        \$result = \$object->execute(\$aspectArguments, \$className, \$functionName, \$functionArguments);


        return \$result;
    }


}


EOF
,
'rg\\injektor\\generators\\FGTestAfterAspect' => <<<EOF
<?php

namespace rg\injektor\generated;

class RgInjektorGeneratorsFGTestAfterAspectFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$instance = new \\rg\injektor\generators\\FGTestAfterAspect();
        return \$instance;
    }

    public static function callExecute(\$object, array \$parameters = array())
    {
        \$methodParameters = array();
        \$methodParameters['aspectArguments'] = array_key_exists('aspectArguments', \$parameters) ? \$parameters['aspectArguments'] : null;
        \$methodParameters['className'] = array_key_exists('className', \$parameters) ? \$parameters['className'] : null;
        \$methodParameters['functionName'] = array_key_exists('functionName', \$parameters) ? \$parameters['functionName'] : null;
        \$methodParameters['result'] = array_key_exists('result', \$parameters) ? \$parameters['result'] : null;
        \$aspectArguments = array_key_exists('aspectArguments', \$methodParameters) ? \$methodParameters['aspectArguments'] : null;
        \$className = array_key_exists('className', \$methodParameters) ? \$methodParameters['className'] : null;
        \$functionName = array_key_exists('functionName', \$methodParameters) ? \$methodParameters['functionName'] : null;
        \$result = array_key_exists('result', \$methodParameters) ? \$methodParameters['result'] : null;
        \$result = \$object->execute(\$aspectArguments, \$className, \$functionName, \$result);


        return \$result;
    }


}


EOF
,
'rg\\injektor\\generators\\FGTestClassOne' => <<<EOF
<?php

namespace rg\\injektor\\generated;

require_once '/RgInjektorGeneratorsFGTestClassTwoFactory.php';
require_once '/RgInjektorGeneratorsFGTestClassThreeFactory.php';
require_once '/RgInjektorGeneratorsFGTestBeforeAspectFactory.php';
require_once '/RgInjektorGeneratorsFGTestAfterAspectFactory.php';

class RgInjektorGeneratorsFGTestClassOneProxy extends \\rg\\injektor\\generators\\FGTestClassOne
{

    public function propertyInjectionfour()
    {
        \$this->four = \\rg\injektor\generated\RgInjektorGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));
    }


}

class RgInjektorGeneratorsFGTestClassOneFactory
{

    private static \$instance = array();

    public static function getInstance(array \$parameters = array())
    {
        \$singletonKey = json_encode(\$parameters) . "#" . getmypid();
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        \$methodParameters['two'] = array_key_exists('two', \$parameters) ? \$parameters['two'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassTwoFactory::getInstance(array (
        ));
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassTwoFactory::getInstance(array (
        ));
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));

        \$instance = new RgInjektorGeneratorsFGTestClassOneProxy(\$two, \$three);
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
        \$methodParameters['two'] = array_key_exists('two', \$parameters) ? \$parameters['two'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassTwoFactory::getInstance(array (
        ));
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassTwoFactory::getInstance(array (
        ));
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : \\rg\\injektor\\generated\\RgInjektorGeneratorsFGTestClassThreeFactory::getInstance(array (
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
        \$aspect = RgInjektorGeneratorsFGTestBeforeAspectFactory::getInstance();
        \$methodParameters = \$aspect->execute(array (
          'foo' => 'bar',
          'one' => '1',
        ), 'rg\injektor\generators\\FGTestClassOne', 'methodRestriction', \$methodParameters);
        \$aspect = RgInjektorGeneratorsFGTestBeforeAspectFactory::getInstance();
        \$methodParameters = \$aspect->execute(array (
        ), 'rg\injektor\generators\\FGTestClassOne', 'methodRestriction', \$methodParameters);
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : NULL;
        \$result = \$object->methodRestriction(\$two);

        \$aspect = RgInjektorGeneratorsFGTestAfterAspectFactory::getInstance();
        \$result = \$aspect->execute(array (
          'foo' => 'bar',
          'one' => '1',
        ), 'rg\injektor\generators\\FGTestClassOne', 'methodRestriction', \$result);
        \$aspect = RgInjektorGeneratorsFGTestAfterAspectFactory::getInstance();
        \$result = \$aspect->execute(array (
        ), 'rg\injektor\generators\\FGTestClassOne', 'methodRestriction', \$result);

        return \$result;
    }


}


EOF
);
            foreach ($expected as $file => $content) {
                $this->assertEquals($content, $factoryGenerator->files[$file]);
            }

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
     * @before \rg\injektor\generators\FGTestBeforeAspect foo=bar&one=1
     * @before \rg\injektor\generators\FGTestBeforeAspect
     * @after \rg\injektor\generators\FGTestAfterAspect foo=bar&one=1
     * @after \rg\injektor\generators\FGTestAfterAspect
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

class FGTestBeforeAspect implements \rg\injektor\aspects\Before {
    public function execute($aspectArguments, $className, $functionName, $functionArguments) {
    }
}

class FGTestAfterAspect implements \rg\injektor\aspects\After {
    public function execute($aspectArguments, $className, $functionName, $result) {
    }
}