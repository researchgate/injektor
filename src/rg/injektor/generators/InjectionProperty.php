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

use rg\injektor\InjectionException;

class InjectionProperty extends InjectionParameter {

    /**
     * @var \ReflectionProperty
     */
    private $property;


    public function __construct(\ReflectionProperty $property, array $classConfig,
                                \rg\injektor\Configuration $config,
                                \rg\injektor\DependencyInjectionContainer $dic) {
        $this->property = $property;
        $this->classConfig = $classConfig;
        $this->config = $config;
        $this->dic = $dic;
        $this->name = $property->name;
        $this->docComment = $this->property->getDocComment();

        $this->additionalArguments = $this->dic->getParamsFromPropertyTypeHint($this->property, 'var');
        $this->analyze();
    }

    protected function hasDefaultValue() {
        return (boolean) $this->getDefaultValue();
    }

    protected function getDefaultValue() {
        return $this->property->getValue();
    }

    protected function hasClass() {
        return (boolean) $this->getClass();
    }

    protected function getClass() {
        return $this->dic->getFullClassNameBecauseOfImports($this->property, $this->dic->getClassFromVarTypeHint($this->docComment));
    }

    public function getProcessingBody() {
        return '$this->' . $this->name . ' = ' . $this->defaultValue . ';' . PHP_EOL;
    }

}