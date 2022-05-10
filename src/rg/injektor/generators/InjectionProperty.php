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

use ReflectionProperty;
use rg\injektor\Configuration;
use rg\injektor\DependencyInjectionContainer;
use const PHP_VERSION_ID;

class InjectionProperty extends InjectionParameter {

    private ReflectionProperty $property;

    public function __construct(
        ReflectionProperty $property,
        array $classConfig,
        Configuration $config,
        DependencyInjectionContainer $dic
    )
    {
        $this->property = $property;
        $this->classConfig = $classConfig;
        $this->config = $config;
        $this->dic = $dic;
        $this->name = $property->name;
        $this->docComment = $this->property->getDocComment();

        $this->additionalArguments = $this->dic->getParamsFromPropertyTypeHint($this->property, 'var');
        $this->analyze();
    }

    protected function hasDefaultValue(): bool {
        return (boolean) $this->getDefaultValue();
    }

    protected function getDefaultValue() {
        if (PHP_VERSION_ID >= 80000) {
            return $this->property->getDefaultValue();
        } else {
            return $this->property->getValue();
        }
    }

    protected function hasClass(): bool {
        return (boolean) $this->getClass();
    }

    protected function getClass(): ?string {
        return $this->dic->getFullClassNameBecauseOfImports($this->property, $this->dic->getClassFromProperty($this->property));
    }

    public function getProcessingBody(): string {
        return '$this->' . $this->name . ' = ' . $this->defaultValue . ';' . PHP_EOL;
    }

}
