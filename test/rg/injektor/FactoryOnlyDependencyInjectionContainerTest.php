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

use rg\injektor\generators\WritingFactoryGenerator;

require_once 'DependencyInjectionContainerTest.php';

class FactoryOnlyDependencyInjectionContainerTest extends DependencyInjectionContainerTest {

    public static function setUpBeforeClass(): void {
        WritingFactoryGenerator::cleanUpGenerationDirectory(__DIR__ . '/_factories');
    }

    public function setUp(): void {
        FactoryDependencyInjectionContainer::$prefix = 'a' . uniqid();
    }

    /**
     * @param Configuration $config
     * @return FactoryDependencyInjectionContainer
     */
    public function getContainer(Configuration $config) {
        $generator = new WritingFactoryGenerator($config, __DIR__ . '/_factories');

        $fileReflection = new \Laminas\Code\Reflection\FileReflection(__DIR__ . '/test_classes.php');
        $classes = $fileReflection->getClasses();
        foreach ($classes as $class) {
            /** @var \ReflectionClass $class */
            $generator->processFileForClass($class->getName());
        }

        return new FactoryOnlyDependencyInjectionContainer($config);
    }

    public function tearDown(): void {
        FactoryDependencyInjectionContainer::$prefix = '';
    }

    public static function tearDownAfterClass(): void {
        WritingFactoryGenerator::cleanUpGenerationDirectory(__DIR__ . '/_factories');
    }
}
