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
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use function class_exists;

require_once 'DependencyInjectionContainerAttributesTest.php';

class FactoryOnlyDependencyInjectionContainerAttributesTest extends DependencyInjectionContainerAttributesTest {

    public static function setUpBeforeClass(): void {
        WritingFactoryGenerator::cleanUpGenerationDirectory(__DIR__ . '/_factories');
    }

    public function setUp(): void {
        FactoryDependencyInjectionContainer::$prefix = 'a' . uniqid();
    }

    public function getContainer(Configuration $config): FactoryOnlyDependencyInjectionContainer {
        $generator = new WritingFactoryGenerator($config, __DIR__ . '/_factories');

        $this->processClassesOfFile(__DIR__ . '/test_classes_attributes.php', $generator);

        if (PHP_VERSION_ID >= 80000) {
            $this->processClassesOfFile(__DIR__ . '/test_classes_php80.php', $generator);
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
        require_once $fileName;

        $astLocator = (new BetterReflection())->astLocator();
        // bc break between v4 and v5. Can be switched to DefaultReflector fully when min version is PHP 8.0
        if (class_exists(\Roave\BetterReflection\Reflector\ClassReflector::class)) {
            $reflector  = new \Roave\BetterReflection\Reflector\ClassReflector(new SingleFileSourceLocator($fileName, $astLocator));
            $classes = $reflector->getAllClasses();
        } else {
            $reflector  = new \Roave\BetterReflection\Reflector\DefaultReflector(new SingleFileSourceLocator($fileName, $astLocator));
            $classes = $reflector->reflectAllClasses();
        }
        foreach ($classes as $class) {
            $generator->processFileForClass($class->getName());
        }
    }
}
