<?php
/*
 * This file is part of rg\injection.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injection\generators;

use rg\injection\InjectionException;

class InjectionParameter {

    /**
     * @var \ReflectionParameter
     */
    private $parameter;

    /**
     * @var array
     */
    private $classConfig;

    /**
     * @var \rg\injection\Configuration
     */
    private $config;

    /**
     * @var \rg\injection\FactoryDependencyInjectionContainer
     */
    private $dic;

    /**
     * @var string
     */
    private $parameterFactoryName;

    /**
     * @var string
     */
    private $parameterClassName;

    private $parameterDefaultValue;

    public function __construct(\ReflectionParameter $parameter, array $classConfig,
                                \rg\injection\Configuration $config,
                                \rg\injection\DependencyInjectionContainer $dic) {
        $this->parameter = $parameter;
        $this->classConfig = $classConfig;
        $this->config = $config;
        $this->dic = $dic;

        $this->analyzeParameter();
    }

    public function getPreProcessingBody() {
        return '$methodParameters[\'' . $this->parameter->name . '\'] = isset($parameters[\'' . $this->parameter->name . '\']) ? $parameters[\'' . $this->parameter->name . '\'] : ' . $this->parameterDefaultValue . ';' . PHP_EOL;
    }

    public function getPostProcessingBody() {
        return '$' . $this->parameter->name . ' = isset($methodParameters[\'' . $this->parameter->name . '\']) ? $methodParameters[\'' . $this->parameter->name . '\'] : ' . $this->parameterDefaultValue . ';' . PHP_EOL;
    }

    private function analyzeParameter() {
        $argumentClass = null;

        $isInjectable = $this->dic->isInjectable($this->parameter->getDeclaringFunction()->getDocComment());

        if (!empty($this->classConfig['params'][$this->parameter->name]['class'])) {
            $argumentClass = $this->classConfig['params'][$this->parameter->name]['class'];
            $isInjectable = true;

        } else if ($this->parameter->getClass()) {
            $argumentClass = $this->parameter->getClass()->name;

        }

        if ($argumentClass && $isInjectable) {

            try {
                $namedClass = $this->dic->getNamedClassOfArgument(
                    $argumentClass,
                    $this->config->getClassConfig($argumentClass),
                    $this->parameter->getDeclaringFunction()->getDocComment(),
                    $this->parameter->name
                );
                if ($namedClass) {
                    $argumentClass = $namedClass;
                }
            } catch (InjectionException $e) {
            }
            if ($argumentClass === 'rg\injection\DependencyInjectionContainer') {
                $this->parameterDefaultValue =  '\\' . $argumentClass . '::getInstance()';
            } else {

                $providerClassName = $this->dic->getProviderClassName($this->config->getClassConfig($argumentClass), new \ReflectionClass($argumentClass),
                    $this->dic->getImplementationName($this->parameter->getDeclaringFunction()->getDocComment(), $this->parameter->name));
                if ($providerClassName && $providerClassName->getClassName()) {
                    $argumentFactory = $this->dic->getFullFactoryClassName($providerClassName->getClassName());
                    $this->parameterClassName = $providerClassName->getClassName();
                    $this->parameterFactoryName = $argumentFactory;
                    $this->parameterDefaultValue = '\\' . $argumentFactory . '::getInstance(' . var_export($providerClassName->getParameters(), true) . ')->get()';
                } else {
                    $argumentClass = $this->dic->getRealConfiguredClassName($this->config->getClassConfig($argumentClass), new \ReflectionClass($argumentClass));

                    $argumentFactory = $this->dic->getFullFactoryClassName($argumentClass);

                    $this->parameterClassName = $argumentClass;
                    $this->parameterFactoryName = $argumentFactory;

                    $this->parameterDefaultValue = '\\' . $argumentFactory . '::getInstance()';
                }
            }

        } else {
            if (!empty($this->classConfig['params'][$this->parameter->name]['value'])) {
                $this->parameterDefaultValue = var_export($this->classConfig['params'][$this->parameter->name]['value'], true);
            } else if ($this->parameter->isDefaultValueAvailable()) {
                $this->parameterDefaultValue = var_export($this->parameter->getDefaultValue(), true);
            } else {
                $this->parameterDefaultValue = 'null';
            }
        }
    }

    public function getParameterFactoryName() {
        return $this->parameterFactoryName;
    }

    public function getParameterClassName() {
        return $this->parameterClassName;
    }
}