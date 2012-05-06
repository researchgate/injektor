<?php
/*
 * This file is part of rg\injection.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injection\generators;

use rg\injection\FactoryDependencyInjectionContainer;
use rg\injection\Configuration;

class FactoryGeneratorTest extends \PHPUnit_Framework_TestCase {

    public function testGenerateFactory() {
        if (strtolower(substr(php_uname(), 0, 7)) == 'windows') {
            $this->markTestSkipped('Skipped since doesnt work on windows.');
        }

        FactoryDependencyInjectionContainer::$prefix = '';

        $config = new Configuration(null, '');
        $config->setClassConfig('rg\injection\generators\FGTestClassOne', array(
            'singleton' => true
        ));
        $config->setClassConfig('rg\injection\generators\FGTestClassFour', array(
            'singleton' => true
        ));
        $config->setClassConfig('rg\injection\generators\FGTestClassThree', array(
            'params' => array(
                'foo' => array(
                    'value' => 'foo'
                ),
                'four' => array(
                    'class' => 'rg\injection\generators\FGTestClassFour'
                )
            )
        ));
        $factoryGenerator = new TestingFactoryGenerator($config, '');
        $factoryGenerator->processFileForClass('rg\injection\generators\FGTestClassOne');

        $expected = array(
'rg\\injection\\generators\\FGTestClassSimple' => <<<EOF
<?php

/** @namespace */
namespace rg\injection\generated;

class RgInjectionGeneratorsFGTestClassSimpleFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$instance = new \\rg\\injection\\generators\\FGTestClassSimple();
        return \$instance;
    }


}


EOF
,
'rg\\injection\\generators\\FGTestClassFour' => <<<EOF
<?php

/** @namespace */
namespace rg\\injection\\generated;

require_once '/RgInjectionGeneratorsFGTestClassSimpleFactory.php';

class RgInjectionGeneratorsFGTestClassFourProxy extends \\rg\injection\generators\\FGTestClassFour
{

    public static function getProxyInstance(\$simple, \$injectedProperty)
    {
        \$instance = parent::getInstance(\$simple);
        \$instance->injectedProperty = \$injectedProperty;
        return \$instance;
    }


}

class RgInjectionGeneratorsFGTestClassFourFactory
{

    private static \$instance = array();

    public static function getInstance(array \$parameters = array())
    {
        \$singletonKey = json_encode(\$parameters) . "#" . getmypid();
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        \$methodParameters['simple'] = array_key_exists('simple', \$parameters) ? \$parameters['simple'] : \\rg\injection\generated\\RgInjectionGeneratorsFGTestClassSimpleFactory::getInstance(array (
        ));
        \$injectedProperty = \\rg\injection\generated\\RgInjectionGeneratorsFGTestClassSimpleFactory::getInstance(array (
        ));
        \$simple = array_key_exists('simple', \$methodParameters) ? \$methodParameters['simple'] : \\rg\injection\generated\\RgInjectionGeneratorsFGTestClassSimpleFactory::getInstance(array (
        ));

        \$instance = RgInjectionGeneratorsFGTestClassFourProxy::getProxyInstance(\$simple, \$injectedProperty);
        self::\$instance[\$singletonKey] = \$instance;
        return \$instance;
    }


}


EOF
,
'rg\\injection\\generators\\FGTestClassThree' => <<<EOF
<?php

/** @namespace */
namespace rg\\injection\\generated;

require_once '/RgInjectionGeneratorsFGTestClassFourFactory.php';

class RgInjectionGeneratorsFGTestClassThreeFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$methodParameters['foo'] = array_key_exists('foo', \$parameters) ? \$parameters['foo'] : 'foo';
        \$methodParameters['four'] = array_key_exists('four', \$parameters) ? \$parameters['four'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassFourFactory::getInstance(array (
        ));
        \$foo = array_key_exists('foo', \$methodParameters) ? \$methodParameters['foo'] : 'foo';
        \$four = array_key_exists('four', \$methodParameters) ? \$methodParameters['four'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassFourFactory::getInstance(array (
        ));

        \$instance = new \\rg\\injection\\generators\\FGTestClassThree(\$foo, \$four);
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

'rg\\injection\\generators\\FGTestClassTwo' => <<<EOF
<?php

/** @namespace */
namespace rg\\injection\\generated;

require_once '/RgInjectionGeneratorsFGTestClassThreeFactory.php';

class RgInjectionGeneratorsFGTestClassTwoFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));

        \$instance = new \\rg\\injection\\generators\\FGTestClassTwo(\$three);
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
'rg\\injection\\generators\\FGTestBeforeAspect' => <<<EOF
<?php

/** @namespace */
namespace rg\injection\generated;

class RgInjectionGeneratorsFGTestBeforeAspectFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$instance = new \\rg\injection\generators\\FGTestBeforeAspect();
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
'rg\\injection\\generators\\FGTestAfterAspect' => <<<EOF
<?php

/** @namespace */
namespace rg\injection\generated;

class RgInjectionGeneratorsFGTestAfterAspectFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$instance = new \\rg\injection\generators\\FGTestAfterAspect();
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
'rg\\injection\\generators\\FGTestClassOne' => <<<EOF
<?php

/** @namespace */
namespace rg\\injection\\generated;

require_once '/RgInjectionGeneratorsFGTestClassTwoFactory.php';
require_once '/RgInjectionGeneratorsFGTestClassThreeFactory.php';
require_once '/RgInjectionGeneratorsFGTestBeforeAspectFactory.php';
require_once '/RgInjectionGeneratorsFGTestAfterAspectFactory.php';

class RgInjectionGeneratorsFGTestClassOneProxy extends \\rg\\injection\\generators\\FGTestClassOne
{

    public function __construct(\$two, \$three, \$four)
    {
        \$this->four = \$four;
        parent::__construct(\$two, \$three);
    }


}

class RgInjectionGeneratorsFGTestClassOneFactory
{

    private static \$instance = array();

    public static function getInstance(array \$parameters = array())
    {
        \$singletonKey = json_encode(\$parameters) . "#" . getmypid();
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        \$methodParameters['two'] = array_key_exists('two', \$parameters) ? \$parameters['two'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassTwoFactory::getInstance(array (
        ));
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));
        \$four = \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassTwoFactory::getInstance(array (
        ));
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));

        \$instance = new RgInjectionGeneratorsFGTestClassOneProxy(\$two, \$three, \$four);
        self::\$instance[\$singletonKey] = \$instance;
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
        \$methodParameters['two'] = array_key_exists('two', \$parameters) ? \$parameters['two'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassTwoFactory::getInstance(array (
        ));
        \$methodParameters['three'] = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassThreeFactory::getInstance(array (
        ));
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassTwoFactory::getInstance(array (
        ));
        \$three = array_key_exists('three', \$methodParameters) ? \$methodParameters['three'] : \\rg\\injection\\generated\\RgInjectionGeneratorsFGTestClassThreeFactory::getInstance(array (
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
        \$aspect = RgInjectionGeneratorsFGTestBeforeAspectFactory::getInstance();
        \$methodParameters = \$aspect->execute(array (
          'foo' => 'bar',
          'one' => '1',
        ), 'rg\injection\generators\\FGTestClassOne', 'methodRestriction', \$methodParameters);
        \$aspect = RgInjectionGeneratorsFGTestBeforeAspectFactory::getInstance();
        \$methodParameters = \$aspect->execute(array (
        ), 'rg\injection\generators\\FGTestClassOne', 'methodRestriction', \$methodParameters);
        \$two = array_key_exists('two', \$methodParameters) ? \$methodParameters['two'] : NULL;
        \$result = \$object->methodRestriction(\$two);

        \$aspect = RgInjectionGeneratorsFGTestAfterAspectFactory::getInstance();
        \$result = \$aspect->execute(array (
          'foo' => 'bar',
          'one' => '1',
        ), 'rg\injection\generators\\FGTestClassOne', 'methodRestriction', \$result);
        \$aspect = RgInjectionGeneratorsFGTestAfterAspectFactory::getInstance();
        \$result = \$aspect->execute(array (
        ), 'rg\injection\generators\\FGTestClassOne', 'methodRestriction', \$result);

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
     * @var \rg\injection\generators\FGTestClassTwo
     */
    public $two;
    /**
     * @var \rg\injection\generators\FGTestClassThree
     */
    public $three;

    /**
     * @inject
     * @var \rg\injection\generators\FGTestClassThree
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
     * @before \rg\injection\generators\FGTestBeforeAspect foo=bar&one=1
     * @before \rg\injection\generators\FGTestBeforeAspect
     * @after \rg\injection\generators\FGTestAfterAspect foo=bar&one=1
     * @after \rg\injection\generators\FGTestAfterAspect
     * @param mixed $two
     */
    public function methodRestriction($two = null) {

    }
}


class FGTestClassTwo {
    /**
     * @var \rg\injection\generators\FGTestClassThree
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
     * @var \rg\injection\generators\FGTestClassSimple
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

class FGTestBeforeAspect implements \rg\injection\aspects\Before {
    public function execute($aspectArguments, $className, $functionName, $functionArguments) {
    }
}

class FGTestAfterAspect implements \rg\injection\aspects\After {
    public function execute($aspectArguments, $className, $functionName, $result) {
    }
}