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

class FactoryGeneratorTest extends \PHPUnit\Framework\TestCase {

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

class rg_injektor_generators_FGTestClassSimpleFactory
{

    public static function getInstance(array \$parameters = [])
    {
        \$i = 0;

        \$instance = new \\rg\\injektor\\generators\\FGTestClassSimple();
        return \$instance;
    }


}


EOF
        ,
            'rg\\injektor\\generators\\FGTestClassFour' => <<<EOF
<?php

namespace rg\\injektor\\generated;

require_once 'rg_injektor_generators_FGTestClassSimpleFactory.php';

class rg_injektor_generators_FGTestClassFourProxy extends \\rg\injektor\generators\\FGTestClassFour
{

    public function propertyInjectioninjectedProperty()
    {
        \$this->injectedProperty = \\rg\injektor\\generated\\rg_injektor_generators_FGTestClassSimpleFactory::getInstance(array (
        ));
    }


}

class rg_injektor_generators_FGTestClassFourFactory
{

    private static \$instance = [];

    public static function getInstance(array \$parameters = [])
    {
        \$i = 0;
        \$singletonKey = serialize(\$parameters) . "#" . getmypid();
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        if (!\$parameters) {
            \$simple = \\rg\injektor\generated\\rg_injektor_generators_FGTestClassSimpleFactory::getInstance(array (
        ));
        }
        else if (array_key_exists(0, \$parameters)) {
            \$simple = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassSimpleFactory::getInstance(array (
        )); \$i++;
        }
        else {
            \$simple = array_key_exists('simple', \$parameters) ? \$parameters['simple'] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassSimpleFactory::getInstance(array (
        ));
        }

        \$instance = rg_injektor_generators_FGTestClassFourProxy::getInstance(\$simple);
        self::\$instance[\$singletonKey] = \$instance;
        \$instance->propertyInjectioninjectedProperty();
        return \$instance;
    }

    public static function callGetInstance(\$object, array \$parameters = [])
    {
        \$i = 0;
        if (!\$parameters) {
            \$simple = \\rg\injektor\generated\\rg_injektor_generators_FGTestClassSimpleFactory::getInstance(array (
        ));
        }
        else if (array_key_exists(0, \$parameters)) {
            \$simple = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassSimpleFactory::getInstance(array (
        )); \$i++;
        }
        else {
            \$simple = array_key_exists('simple', \$parameters) ? \$parameters['simple'] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassSimpleFactory::getInstance(array (
        ));
        }
        \$result = \$object->getInstance(\$simple);


        return \$result;
    }


}


EOF
        ,
            'rg\\injektor\\generators\\FGTestClassThree' => <<<EOF
<?php

namespace rg\\injektor\\generated;

require_once 'rg_injektor_generators_FGTestClassFourFactory.php';

class rg_injektor_generators_FGTestClassThreeFactory
{

    public static function getInstance(array \$parameters = [])
    {
        \$i = 0;
        if (!\$parameters) {
            \$foo = 'foo';
            \$four = \\rg\injektor\generated\\rg_injektor_generators_FGTestClassFourFactory::getInstance(array (
        ));
        }
        else if (array_key_exists(0, \$parameters)) {
            \$foo = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : 'foo'; \$i++;
            \$four = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassFourFactory::getInstance(array (
        )); \$i++;
        }
        else {
            \$foo = array_key_exists('foo', \$parameters) ? \$parameters['foo'] : 'foo';
            \$four = array_key_exists('four', \$parameters) ? \$parameters['four'] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassFourFactory::getInstance(array (
        ));
        }

        \$instance = new \\rg\\injektor\\generators\\FGTestClassThree(\$foo, \$four);
        return \$instance;
    }

    public static function callGetSomething(\$object)
    {
        \$i = 0;
        \$result = \$object->getSomething();


        return \$result;
    }


}


EOF
        ,

            'rg\\injektor\\generators\\FGTestClassTwo' => <<<EOF
<?php

namespace rg\\injektor\\generated;

require_once 'rg_injektor_generators_FGTestClassThreeFactory.php';

class rg_injektor_generators_FGTestClassTwoFactory
{

    public static function getInstance(array \$parameters = [])
    {
        \$i = 0;
        if (!\$parameters) {
            \$three = \\rg\injektor\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        ));
        }
        else if (array_key_exists(0, \$parameters)) {
            \$three = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        )); \$i++;
        }
        else {
            \$three = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        ));
        }

        \$instance = new \\rg\\injektor\\generators\\FGTestClassTwo(\$three);
        return \$instance;
    }

    public static function callGetSomething(\$object)
    {
        \$i = 0;
        \$result = \$object->getSomething();


        return \$result;
    }


}


EOF
        ,
            'rg\\injektor\\generators\\FGTestClassOne' => <<<EOF
<?php

namespace rg\\injektor\\generated;

require_once 'rg_injektor_generators_FGTestClassTwoFactory.php';
require_once 'rg_injektor_generators_FGTestClassThreeFactory.php';

class rg_injektor_generators_FGTestClassOneProxy extends \\rg\\injektor\\generators\\FGTestClassOne
{

    public function propertyInjectionfour()
    {
        \$this->four = \\rg\injektor\\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        ));
    }


}

class rg_injektor_generators_FGTestClassOneFactory
{

    private static \$instance = [];

    public static function getInstance(array \$parameters = [])
    {
        \$i = 0;
        \$singletonKey = serialize(\$parameters) . "#" . getmypid();
        if (isset(self::\$instance[\$singletonKey])) {
            return self::\$instance[\$singletonKey];
        }

        if (!\$parameters) {
            \$two = \\rg\injektor\generated\\rg_injektor_generators_FGTestClassTwoFactory::getInstance(array (
        ));
            \$three = \\rg\injektor\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        ));
        }
        else if (array_key_exists(0, \$parameters)) {
            \$two = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassTwoFactory::getInstance(array (
        )); \$i++;
            \$three = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        )); \$i++;
        }
        else {
            \$two = array_key_exists('two', \$parameters) ? \$parameters['two'] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassTwoFactory::getInstance(array (
        ));
            \$three = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        ));
        }

        \$instance = new rg_injektor_generators_FGTestClassOneProxy(\$two, \$three);
        self::\$instance[\$singletonKey] = \$instance;
        \$instance->propertyInjectionfour();
        return \$instance;
    }

    public static function callGetFour(\$object)
    {
        \$i = 0;
        \$result = \$object->getFour();


        return \$result;
    }

    public static function callGetSomething(\$object, array \$parameters = [])
    {
        \$i = 0;
        if (!\$parameters) {
            \$two = \\rg\injektor\generated\\rg_injektor_generators_FGTestClassTwoFactory::getInstance(array (
        ));
            \$three = \\rg\injektor\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        ));
        }
        else if (array_key_exists(0, \$parameters)) {
            \$two = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassTwoFactory::getInstance(array (
        )); \$i++;
            \$three = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        )); \$i++;
        }
        else {
            \$two = array_key_exists('two', \$parameters) ? \$parameters['two'] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassTwoFactory::getInstance(array (
        ));
            \$three = array_key_exists('three', \$parameters) ? \$parameters['three'] : \\rg\injektor\generated\\rg_injektor_generators_FGTestClassThreeFactory::getInstance(array (
        ));
        }
        \$result = \$object->getSomething(\$two, \$three);


        return \$result;
    }

    public static function callGetSomethingNotInjectible(\$object, array \$parameters = [])
    {
        \$i = 0;
        if (!\$parameters) {
            \$two = null;
            \$three = null;
        }
        else if (array_key_exists(0, \$parameters)) {
            \$two = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : null; \$i++;
            \$three = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : null; \$i++;
        }
        else {
            \$two = array_key_exists('two', \$parameters) ? \$parameters['two'] : null;
            \$three = array_key_exists('three', \$parameters) ? \$parameters['three'] : null;
        }
        \$result = \$object->getSomethingNotInjectible(\$two, \$three);


        return \$result;
    }

    public static function callNoTypeHint(\$object, array \$parameters = [])
    {
        \$i = 0;
        if (!\$parameters) {
            \$foo = null;
        }
        else if (array_key_exists(0, \$parameters)) {
            \$foo = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : null; \$i++;
        }
        else {
            \$foo = array_key_exists('foo', \$parameters) ? \$parameters['foo'] : null;
        }
        \$result = \$object->noTypeHint(\$foo);


        return \$result;
    }

    public static function callMethodRestriction(\$object, array \$parameters = [])
    {
        \$i = 0;
        if (!\$parameters) {
            \$two = NULL;
        }
        else if (array_key_exists(0, \$parameters)) {
            \$two = array_key_exists(\$i, \$parameters) ? \$parameters[\$i] : NULL; \$i++;
        }
        else {
            \$two = array_key_exists('two', \$parameters) ? \$parameters['two'] : NULL;
        }
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
        $file = $this->generateFileForClass($fullClassName);
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
