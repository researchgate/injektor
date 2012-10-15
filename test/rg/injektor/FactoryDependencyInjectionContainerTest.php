<?php
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injektor;

class FactoryDependencyInjectionContainerTest extends \PHPUnit_Framework_TestCase {

    public function testInjectionWithoutFactory() {
        $config = new Configuration(null, '');

        $dic = new FactoryDependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injektor\FDICTestClassOne');

        $this->assertInstanceOf('rg\injektor\FDICTestClassOne', $instance);

        $this->assertInstanceOf('rg\injektor\FDICTestClassTwo', $instance->two);
        $this->assertInstanceOf('rg\injektor\FDICTestClassThree', $instance->three);
        $this->assertInstanceOf('rg\injektor\FDICTestClassThree', $instance->two->three);
        $this->assertInstanceOf('rg\injektor\FDICTestClassThree', $instance->getFour());
    }

    public function testGetFactoryClassName() {
        $config = new Configuration(null, '');

        $dic = new FactoryDependencyInjectionContainer($config);
        $dic::$prefix = 'prefix';

        $this->assertEquals('prefixFooFactory', $dic->getFactoryClassName('Foo'));
        $this->assertEquals('prefix__FooFactory', $dic->getFactoryClassName('\\Foo'));
        $this->assertEquals('prefixFoo__Bar__BazFactory', $dic->getFactoryClassName('Foo\\Bar\\Baz'));
        $this->assertEquals('prefixfoo__bar__BazFactory', $dic->getFactoryClassName('foo\\bar\\Baz'));
    }
}

class FDICTestClassOne {

    /**
     * @var \rg\injektor\FDICTestClassTwo
     */
    public $two;

    /**
     * @var \rg\injektor\FDICTestClassThree
     */
    public $three;

    /**
     * @inject
     * @var \rg\injektor\FDICTestClassThree
     */
    protected $four;

    /**
     * @return FDICTestClassThree
     */
    public function getFour() {
        return $this->four;
    }

    /**
     * @inject
     * @param FDICTestClassTwo $two
     * @param FDICTestClassThree $three
     */
    public function __construct(FDICTestClassTwo $two, FDICTestClassThree $three) {
        $this->two = $two;
        $this->three = $three;
    }

    /**
     * @inject
     * @param FDICTestClassTwo $two
     * @param FDICTestClassThree $three
     * @return string
     */
    public function getSomething(FDICTestClassTwo $two, FDICTestClassThree $three) {
        return $two->getSomething() . $three->getSomething();
    }

    public function getSomethingNotInjectible(FDICTestClassTwo $two, FDICTestClassThree $three) {
        return $two->getSomething() . $three->getSomething();
    }

    public function noTypeHint($foo) {

    }
}

class FDICTestClassTwo {

    /**
     * @var \rg\injektor\FDICTestClassThree
     */
    public $three;

    /**
     * @inject
     * @param FDICTestClassThree $three
     */
    public function __construct(FDICTestClassThree $three) {
        $this->three = $three;
    }

    public function getSomething() {
        return 'bar';
    }
}

class FDICTestClassThree {

    public function __construct() {

    }

    public function getSomething() {
        return 'foo';
    }
}