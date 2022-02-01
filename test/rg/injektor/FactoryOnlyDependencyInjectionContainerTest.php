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

use Laminas\Code\Reflection\FileReflection;
use rg\injektor\generators\WritingFactoryGenerator;
use const PHP_VERSION_ID;

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

        $this->processClassesOfFile(__DIR__ . '/test_classes.php', $generator);

        if (PHP_VERSION_ID >= 70400) {
            $this->processClassesOfFile(__DIR__ . '/test_classes_php74.php', $generator);
        }

        return new FactoryOnlyDependencyInjectionContainer($config);
    }

    public function tearDown(): void {
        FactoryDependencyInjectionContainer::$prefix = '';
    }

    public static function tearDownAfterClass(): void {
        WritingFactoryGenerator::cleanUpGenerationDirectory(__DIR__ . '/_factories');
    }

    private function processClassesOfFile(string $fileName, WritingFactoryGenerator $generator): void
    {
        $fileReflection = new FileReflection($fileName);
        $classes = $fileReflection->getClasses();
        foreach ($classes as $class) {
            $generator->processFileForClass($class->getName());
        }
    }
}
