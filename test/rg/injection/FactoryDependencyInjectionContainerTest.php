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

class FactoryDependencyInjectionContainerTest extends \PHPUnit_Framework_TestCase {

    public function testInjectionWithoutFactory() {
        $config = new Configuration(null);

        $dic = new FactoryDependencyInjectionContainer($config);

        $instance = $dic->getInstanceOfClass('rg\injection\FDICTestClassOne');

        $this->assertInstanceOf('rg\injection\FDICTestClassOne', $instance);

        $this->assertInstanceOf('rg\injection\FDICTestClassTwo', $instance->two);
        $this->assertInstanceOf('rg\injection\FDICTestClassThree', $instance->three);
        $this->assertInstanceOf('rg\injection\FDICTestClassThree', $instance->two->three);
        $this->assertInstanceOf('rg\injection\FDICTestClassThree', $instance->getFour());
    }

}

class FDICTestClassOne {
    /**
     * @var \rg\injection\FDICTestClassTwo
     */
    public $two;
    /**
     * @var \rg\injection\FDICTestClassThree
     */
    public $three;

    /**
     * @inject
     * @var \rg\injection\FDICTestClassThree
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
     * @var \rg\injection\FDICTestClassThree
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