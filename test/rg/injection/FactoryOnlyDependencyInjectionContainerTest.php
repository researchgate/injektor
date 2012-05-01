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

use rg\injection\generators\WritingFactoryGenerator;

require_once 'DependencyInjectionContainerTest.php';

class FactoryOnlyDependencyInjectionContainerTest extends DependencyInjectionContainerTest {

    public static function setUpBeforeClass() {
        WritingFactoryGenerator::cleanUpGenerationDirectory(__DIR__ . '/_factories');
    }

    public function setUp() {
        FactoryDependencyInjectionContainer::$prefix = 'a' . uniqid();
    }

    /**
     * @param Configuration $config
     * @return FactoryDependencyInjectionContainer
     */
    public function getContainer(Configuration $config) {
        $generator = new WritingFactoryGenerator($config, __DIR__ . '/_factories');

        $fileReflection = new \Zend\Code\Reflection\FileReflection(__DIR__ . '/test_classes.php');
        $classes = $fileReflection->getClasses();
        foreach ($classes as $class) {
            /** @var \ReflectionClass $class */
            $generator->processFileForClass($class->getName());
        }

        return new FactoryOnlyDependencyInjectionContainer($config);
    }

    public function tearDown() {
        FactoryDependencyInjectionContainer::$prefix = '';
    }

    public static function tearDownAfterClass() {
        WritingFactoryGenerator::cleanUpGenerationDirectory(__DIR__ . '/_factories');
    }
}
