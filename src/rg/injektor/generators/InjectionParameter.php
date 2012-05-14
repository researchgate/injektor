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

class InjectionParameter {

    /**
     * @var \ReflectionParameter
     */
    private $parameter;

    /**
     * @var array
     */
    protected $classConfig;

    /**
     * @var \rg\injektor\Configuration
     */
    protected $config;

    /**
     * @var \rg\injektor\FactoryDependencyInjectionContainer
     */
    protected $dic;

    /**
     * @var string
     */
    protected $factoryName;

    /**
     * @var string
     */
    protected $className;

    protected $defaultValue;

    protected $name;

    protected $nameForAnnotationParsing;

    protected $docComment;

    protected $additionalArguments;

    public function __construct(\ReflectionParameter $parameter, array $classConfig,
                                \rg\injektor\Configuration $config,
                                \rg\injektor\DependencyInjectionContainer $dic) {
        $this->parameter = $parameter;
        $this->classConfig = $classConfig;
        $this->config = $config;
        $this->dic = $dic;
        $this->name = $parameter->name;
        $this->nameForAnnotationParsing = $parameter->name;
        $this->docComment = $this->parameter->getDeclaringFunction()->getDocComment();

        $this->additionalArguments = $this->dic->getParamsFromTypeHint($this->parameter);

        $this->analyze();
    }

    public function getPreProcessingBody() {
        return '$methodParameters[\'' . $this->name . '\'] = array_key_exists(\'' . $this->name . '\', $parameters) ? $parameters[\'' . $this->name . '\'] : ' . $this->defaultValue . ';' . PHP_EOL;
    }

    public function getPostProcessingBody() {
        return '$' . $this->name . ' = array_key_exists(\'' . $this->name . '\', $methodParameters) ? $methodParameters[\'' . $this->name . '\'] : ' . $this->defaultValue . ';' . PHP_EOL;
    }

    public function getDefaultPreProcessingBody() {
        return '$methodParameters[\'' . $this->name . '\'] = array_key_exists(\'' . $this->name . '\', $parameters) ? $parameters[\'' . $this->name . '\'] : null;' . PHP_EOL;
    }

    public function getDefaultPostProcessingBody() {
        return '$' . $this->name . ' = array_key_exists(\'' . $this->name . '\', $methodParameters) ? $methodParameters[\'' . $this->name . '\'] : null;' . PHP_EOL;
    }

    protected function analyze() {
        $argumentClass = null;

        $isInjectable = $this->dic->isInjectable($this->docComment);

        if (!empty($this->classConfig['params'][$this->name]['class'])) {
            $argumentClass = $this->classConfig['params'][$this->name]['class'];
            $isInjectable = true;

        } else if ($this->hasClass()) {
            $argumentClass = $this->getClass();
        }

        if ($argumentClass && $isInjectable) {

            try {
                $namedClass = $this->dic->getNamedClassOfArgument(
                    $argumentClass,
                    $this->docComment,
                    $this->nameForAnnotationParsing
                );
                if ($namedClass) {
                    $argumentClass = $namedClass;
                }
            } catch (InjectionException $e) {
            }
            if ($argumentClass === 'rg\injektor\DependencyInjectionContainer') {
                $this->defaultValue =  '\\' . $argumentClass . '::getDefaultInstance()';
            } else {
                $providerClassName = $this->dic->getProviderClassName($this->config->getClassConfig($argumentClass), new \ReflectionClass($argumentClass),
                    $this->dic->getImplementationName($this->docComment, $this->nameForAnnotationParsing));
                if ($providerClassName && $providerClassName->getClassName()) {
                    $argumentFactory = $this->dic->getFullFactoryClassName($providerClassName->getClassName());
                    $this->className = $providerClassName->getClassName();
                    $this->factoryName = $argumentFactory;
                    $this->defaultValue = '\\' . $argumentFactory . '::getInstance(' . var_export(array_merge($providerClassName->getParameters(), $this->additionalArguments), true) . ')->get()';
                } else {
                    $argumentClass = $this->dic->getRealConfiguredClassName($this->config->getClassConfig($argumentClass), new \ReflectionClass($argumentClass));

                    $argumentFactory = $this->dic->getFullFactoryClassName($argumentClass);

                    $this->className = $argumentClass;
                    $this->factoryName = $argumentFactory;

                    $this->defaultValue = '\\' . $argumentFactory . '::getInstance(' . var_export($this->additionalArguments, true) . ')';
                }
            }

        } else {
            if (!empty($this->classConfig['params'][$this->name]['value'])) {
                $this->defaultValue = var_export($this->classConfig['params'][$this->name]['value'], true);
            } else if ($this->hasDefaultValue()) {
                $this->defaultValue = var_export($this->getDefaultValue(), true);
            } else {
                $this->defaultValue = 'null';
            }
        }
    }

    protected function hasDefaultValue() {
        return $this->parameter->isDefaultValueAvailable();
    }

    protected function getDefaultValue() {
        return $this->parameter->getDefaultValue();
    }

    protected function hasClass() {
        return $this->parameter->getClass();
    }

    protected function getClass() {
        return $this->parameter->getClass()->name;
    }

    public function getFactoryName() {
        return $this->factoryName;
    }

    public function getClassName() {
        return $this->className;
    }
}