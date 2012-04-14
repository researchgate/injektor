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
        if (strtolower(substr(php_uname(), 0, 7)) == 'windows') {
            $this->markTestSkipped('Skipped since doesnt work on windows.');
        }

        FactoryDependencyInjectionContainer::$prefix = '';

        $config = new Configuration(null, '');
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
        $factoryGenerator = new TestingFactoryGenerator($config, '');
        $factoryGenerator->processFileForClass('rg\injection\FGTestClassOne');

        $expected = array(
'rg\\injection\\FGTestClassSimple' => <<<EOF
<?php

/** @namespace */
namespace rg\injection\generated;

class RgInjectionFGTestClassSimpleFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$instance = new \\rg\\injection\\FGTestClassSimple();
        return \$instance;
    }


}


EOF
,
'rg\\injection\\FGTestClassFour' => <<<EOF
<?php

/** @namespace */
namespace rg\\injection\\generated;

require_once '/RgInjectionFGTestClassSimpleFactory.php';

class RgInjectionFGTestClassFourFactory
{

    private static \$instance = array();

    public static function getInstance(array \$parameters = array())
    {
        \$singletonKey = json_encode(\$parameters);
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        \$methodParameters['simple'] = isset(\$parameters['simple']) ? \$parameters['simple'] : \\rg\injection\generated\\RgInjectionFGTestClassSimpleFactory::getInstance();
        \$injectedProperty = \\rg\injection\generated\\RgInjectionFGTestClassSimpleFactory::getInstance();
        \$simple = isset(\$methodParameters['simple']) ? \$methodParameters['simple'] : \\rg\injection\generated\\RgInjectionFGTestClassSimpleFactory::getInstance();

        \$instance = RgInjectionFGTestClassFourProxy::getProxyInstance(\$simple, \$injectedProperty);
        self::\$instance[\$singletonKey] = \$instance;
        return \$instance;
    }


}

class RgInjectionFGTestClassFourProxy extends \\rg\injection\FGTestClassFour
{

    public static function getProxyInstance(\$simple, \$injectedProperty)
    {
        \$instance = parent::getInstance(\$simple);
        \$instance->injectedProperty = \$injectedProperty;
        return \$instance;
    }


}


EOF
,
'rg\\injection\\FGTestClassThree' => <<<EOF
<?php

/** @namespace */
namespace rg\\injection\\generated;

require_once '/RgInjectionFGTestClassFourFactory.php';

class RgInjectionFGTestClassThreeFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$methodParameters['foo'] = isset(\$parameters['foo']) ? \$parameters['foo'] : 'foo';
        \$methodParameters['four'] = isset(\$parameters['four']) ? \$parameters['four'] : \\rg\\injection\\generated\\RgInjectionFGTestClassFourFactory::getInstance();
        \$foo = isset(\$methodParameters['foo']) ? \$methodParameters['foo'] : 'foo';
        \$four = isset(\$methodParameters['four']) ? \$methodParameters['four'] : \\rg\\injection\\generated\\RgInjectionFGTestClassFourFactory::getInstance();

        \$instance = new \\rg\\injection\\FGTestClassThree(\$foo, \$four);
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

'rg\\injection\\FGTestClassTwo' => <<<EOF
<?php

/** @namespace */
namespace rg\\injection\\generated;

require_once '/RgInjectionFGTestClassThreeFactory.php';

class RgInjectionFGTestClassTwoFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$methodParameters['three'] = isset(\$parameters['three']) ? \$parameters['three'] : \\rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();
        \$three = isset(\$methodParameters['three']) ? \$methodParameters['three'] : \\rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();

        \$instance = new \\rg\\injection\\FGTestClassTwo(\$three);
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
'rg\\injection\\FGTestBeforeAspect' => <<<EOF
<?php

/** @namespace */
namespace rg\injection\generated;

class RgInjectionFGTestBeforeAspectFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$instance = new \\rg\injection\FGTestBeforeAspect();
        return \$instance;
    }

    public static function callExecute(\$object, array \$parameters = array())
    {
        \$methodParameters = array();

        \$methodParameters['aspectArguments'] = isset(\$parameters['aspectArguments']) ? \$parameters['aspectArguments'] : null;
        \$methodParameters['className'] = isset(\$parameters['className']) ? \$parameters['className'] : null;
        \$methodParameters['functionName'] = isset(\$parameters['functionName']) ? \$parameters['functionName'] : null;
        \$methodParameters['functionArguments'] = isset(\$parameters['functionArguments']) ? \$parameters['functionArguments'] : null;


        \$aspectArguments = isset(\$methodParameters['aspectArguments']) ? \$methodParameters['aspectArguments'] : null;
        \$className = isset(\$methodParameters['className']) ? \$methodParameters['className'] : null;
        \$functionName = isset(\$methodParameters['functionName']) ? \$methodParameters['functionName'] : null;
        \$functionArguments = isset(\$methodParameters['functionArguments']) ? \$methodParameters['functionArguments'] : null;

        \$result = \$object->execute(\$aspectArguments, \$className, \$functionName, \$functionArguments);


        return \$result;
    }


}


EOF
,
'rg\\injection\\FGTestAfterAspect' => <<<EOF
<?php

/** @namespace */
namespace rg\injection\generated;

class RgInjectionFGTestAfterAspectFactory
{

    public static function getInstance(array \$parameters = array())
    {
        \$instance = new \\rg\injection\FGTestAfterAspect();
        return \$instance;
    }

    public static function callExecute(\$object, array \$parameters = array())
    {
        \$methodParameters = array();

        \$methodParameters['aspectArguments'] = isset(\$parameters['aspectArguments']) ? \$parameters['aspectArguments'] : null;
        \$methodParameters['className'] = isset(\$parameters['className']) ? \$parameters['className'] : null;
        \$methodParameters['functionName'] = isset(\$parameters['functionName']) ? \$parameters['functionName'] : null;
        \$methodParameters['result'] = isset(\$parameters['result']) ? \$parameters['result'] : null;


        \$aspectArguments = isset(\$methodParameters['aspectArguments']) ? \$methodParameters['aspectArguments'] : null;
        \$className = isset(\$methodParameters['className']) ? \$methodParameters['className'] : null;
        \$functionName = isset(\$methodParameters['functionName']) ? \$methodParameters['functionName'] : null;
        \$result = isset(\$methodParameters['result']) ? \$methodParameters['result'] : null;

        \$result = \$object->execute(\$aspectArguments, \$className, \$functionName, \$result);


        return \$result;
    }


}


EOF
,
'rg\\injection\\FGTestClassOne' => <<<EOF
<?php

/** @namespace */
namespace rg\\injection\\generated;

require_once '/RgInjectionFGTestClassTwoFactory.php';
require_once '/RgInjectionFGTestClassThreeFactory.php';
require_once '/RgInjectionFGTestBeforeAspectFactory.php';
require_once '/RgInjectionFGTestAfterAspectFactory.php';

class RgInjectionFGTestClassOneFactory
{

    private static \$instance = array();

    public static function getInstance(array \$parameters = array())
    {
        \$singletonKey = json_encode(\$parameters);
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        \$methodParameters['two'] = isset(\$parameters['two']) ? \$parameters['two'] : \\rg\\injection\\generated\\RgInjectionFGTestClassTwoFactory::getInstance();
        \$methodParameters['three'] = isset(\$parameters['three']) ? \$parameters['three'] : \\rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();
        \$four = \\rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();
        \$two = isset(\$methodParameters['two']) ? \$methodParameters['two'] : \\rg\\injection\\generated\\RgInjectionFGTestClassTwoFactory::getInstance();
        \$three = isset(\$methodParameters['three']) ? \$methodParameters['three'] : \\rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();

        \$instance = new RgInjectionFGTestClassOneProxy(\$two, \$three, \$four);
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

        \$methodParameters['two'] = isset(\$parameters['two']) ? \$parameters['two'] : \\rg\\injection\\generated\\RgInjectionFGTestClassTwoFactory::getInstance();
        \$methodParameters['three'] = isset(\$parameters['three']) ? \$parameters['three'] : \\rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();


        \$two = isset(\$methodParameters['two']) ? \$methodParameters['two'] : \\rg\\injection\\generated\\RgInjectionFGTestClassTwoFactory::getInstance();
        \$three = isset(\$methodParameters['three']) ? \$methodParameters['three'] : \\rg\\injection\\generated\\RgInjectionFGTestClassThreeFactory::getInstance();

        \$result = \$object->getSomething(\$two, \$three);


        return \$result;
    }

    public static function callGetSomethingNotInjectible(\$object, array \$parameters = array())
    {
        \$methodParameters = array();

        \$methodParameters['two'] = isset(\$parameters['two']) ? \$parameters['two'] : null;
        \$methodParameters['three'] = isset(\$parameters['three']) ? \$parameters['three'] : null;


        \$two = isset(\$methodParameters['two']) ? \$methodParameters['two'] : null;
        \$three = isset(\$methodParameters['three']) ? \$methodParameters['three'] : null;

        \$result = \$object->getSomethingNotInjectible(\$two, \$three);


        return \$result;
    }

    public static function callNoTypeHint(\$object, array \$parameters = array())
    {
        \$methodParameters = array();

        \$methodParameters['foo'] = isset(\$parameters['foo']) ? \$parameters['foo'] : null;


        \$foo = isset(\$methodParameters['foo']) ? \$methodParameters['foo'] : null;

        \$result = \$object->noTypeHint(\$foo);


        return \$result;
    }

    public static function callMethodRestriction(\$object, array \$parameters = array())
    {
        \$methodParameters = array();

        \$methodParameters['two'] = isset(\$parameters['two']) ? \$parameters['two'] : NULL;

        \$aspect = RgInjectionFGTestBeforeAspectFactory::getInstance();
        \$methodParameters = \$aspect->execute(array (
          'foo' => 'bar',
          'one' => '1',
        ), 'rg\injection\FGTestClassOne', 'methodRestriction', \$methodParameters);
        \$aspect = RgInjectionFGTestBeforeAspectFactory::getInstance();
        \$methodParameters = \$aspect->execute(array (
        ), 'rg\injection\FGTestClassOne', 'methodRestriction', \$methodParameters);

        \$two = isset(\$methodParameters['two']) ? \$methodParameters['two'] : NULL;

        \$result = \$object->methodRestriction(\$two);

        \$aspect = RgInjectionFGTestAfterAspectFactory::getInstance();
        \$result = \$aspect->execute(array (
          'foo' => 'bar',
          'one' => '1',
        ), 'rg\injection\FGTestClassOne', 'methodRestriction', \$result);
        \$aspect = RgInjectionFGTestAfterAspectFactory::getInstance();
        \$result = \$aspect->execute(array (
        ), 'rg\injection\FGTestClassOne', 'methodRestriction', \$result);

        return \$result;
    }


}

class RgInjectionFGTestClassOneProxy extends \\rg\\injection\\FGTestClassOne
{

    public function __construct(\$two, \$three, \$four)
    {
        \$this->four = \$four;
        parent::__construct(\$two, \$three);
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
     * @inject
     * @before \rg\injection\FGTestBeforeAspect foo=bar&one=1
     * @before \rg\injection\FGTestBeforeAspect
     * @after \rg\injection\FGTestAfterAspect foo=bar&one=1
     * @after \rg\injection\FGTestAfterAspect
     */
    public function methodRestriction($two = null) {

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

class FGTestBeforeAspect implements \rg\injection\aspects\Before {
    public function execute($aspectArguments, $className, $functionName, $functionArguments) {
    }
}

class FGTestAfterAspect implements \rg\injection\aspects\After {
    public function execute($aspectArguments, $className, $functionName, $result) {
    }
}