<?php
/*
 * This file is part of rg\injection.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injection\aspects;

class MethodTest extends \PHPUnit_Framework_TestCase {
    public function testMethodAnnotation() {
        $_SERVER['REQUEST_METHOD'] = 'post';

        $config = new \rg\injection\Configuration(null, '');
        $dic = new \rg\injection\DependencyInjectionContainer($config);

        $instance = new DICTestMethodAspects();

        $result = $dic->callMethodOnObject($instance, 'aspectFunction');

        $this->assertTrue($result);
    }

    public function testMethodAnnotationFails() {
        $this->setExpectedException('\RuntimeException', 'a');
        $_SERVER['REQUEST_METHOD'] = 'get';

        $config = new \rg\injection\Configuration(null, '');
        $dic = new \rg\injection\DependencyInjectionContainer($config);

        $instance = new DICTestMethodAspects();

        $dic->callMethodOnObject($instance, 'aspectFunction');
    }

    public function tearDown() {
        unset($_SERVER['REQUEST_METHOD']);
    }
}

class DICTestMethodAspects {

    /**
     * @inject
     * @return bool
     * @before \rg\injection\aspects\Method method=POST
     */
    public function aspectFunction() {
        return true;
    }
}