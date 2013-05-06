<?php
/**
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Johannes Brinksmeier <johannes.brinksmeier@googlemail.com>
 */
namespace rg\injektor;

include_once 'test_classes_issue.php';

class DependencyInjectionContainerTestIssue extends \PHPUnit_Framework_TestCase
{
    public function testGetInstanceOfClassWithUnderscores()
    {
        $config = new Configuration(__DIR__ . '/test_config_issue.php');
        $dic = new DependencyInjectionContainer($config);
        $class = $dic->getInstanceOfClass('issue\ClassWithDependencyToClassWithUnderscores');
        $this->assertInstanceOf('issue\Class_With_Underscores', $class->getDependency());
    }
}
