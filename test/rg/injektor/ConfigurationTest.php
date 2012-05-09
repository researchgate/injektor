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

class ConfigurationTest extends \PHPUnit_Framework_TestCase {

    public function testLoadConfiguration() {
        $config = new Configuration(__DIR__ . '/test_config.php', '');

        $this->assertEquals('bar', $config->getClassConfig('foo'));
    }

    public function testGetInitialConfig() {
        $config = new Configuration(null, '');

        $this->assertEquals(array(), $config->getClassConfig('foo'));
    }

    public function testSetConfig() {
        $config = new Configuration(null, '');

        $config->setClassConfig('foo', array('bar'));
        $this->assertEquals(array('bar'), $config->getClassConfig('foo'));
    }
}