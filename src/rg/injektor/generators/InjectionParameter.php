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

use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use rg\injektor\Configuration;
use rg\injektor\DependencyInjectionContainer;
use rg\injektor\FactoryDependencyInjectionContainer;
use rg\injektor\InjectionException;
use rg\injektor\ReflectionClassHelper;

class InjectionParameter {

    const MODE_NO_ARGUMENTS = 'noArguments';
    const MODE_NUMERIC = 'numeric';
    const MODE_STRING = 'string';

    private ReflectionParameter $parameter;

    protected array $classConfig;

    protected Configuration $config;

    /**
     * @var FactoryDependencyInjectionContainer
     */
    protected $dic;

    protected ?string $factoryName = null;

    protected ?string $className = null;

    protected $defaultValue;

    protected $name;

    protected $nameForAnnotationParsing;

    protected $docComment;

    protected $additionalArguments;

    protected $mode;

    public function __construct(
        ReflectionParameter $parameter,
        array $classConfig,
        Configuration $config,
        DependencyInjectionContainer $dic,
        $mode)
    {
        $this->parameter = $parameter;
        $this->classConfig = $classConfig;
        $this->config = $config;
        $this->dic = $dic;
        $this->name = $parameter->name;
        $this->nameForAnnotationParsing = $parameter->name;
        $this->docComment = $this->parameter->getDeclaringFunction()->getDocComment();

        $this->additionalArguments = $this->dic->getParamsFromTypeHint($this->parameter);
        $this->mode = $mode;

        $this->analyze();
    }

    public function getName() {
        return $this->name;
    }

    public function getProcessingBody(): string {
        if ($this->mode === self::MODE_NO_ARGUMENTS) {
            return '$' . $this->name . ' = ' . $this->defaultValue . ';' . PHP_EOL;
        } else if ($this->mode === self::MODE_NUMERIC) {
            return '$' . $this->name . ' = array_key_exists($i, $parameters) ? $parameters[$i] : ' . $this->defaultValue . '; $i++;' . PHP_EOL;
        }
        return '$' . $this->name . ' = array_key_exists(\'' . $this->name . '\', $parameters) ? $parameters[\'' . $this->name . '\'] : ' . $this->defaultValue . ';' . PHP_EOL;
    }

    public function getDefaultProcessingBody(): string {
        if ($this->mode === self::MODE_NO_ARGUMENTS) {
            return '$' . $this->name . ' = null;' . PHP_EOL;
        } else if ($this->mode === self::MODE_NUMERIC) {
            return '$' . $this->name . ' = array_key_exists($i, $parameters) ? $parameters[$i] : null; $i++;' . PHP_EOL;
        }
        return '$' . $this->name . ' = array_key_exists(\'' . $this->name . '\', $parameters) ? $parameters[\'' . $this->name . '\'] : null;' . PHP_EOL;
    }

    /**
     * @throws ReflectionException
     */
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
                $this->defaultValue = '\\' . $argumentClass . '::getDefaultInstance()';
            } else {
                $providerClassName = $this->dic->getProviderClassName($this->config->getClassConfig($argumentClass), new ReflectionClass($argumentClass),
                    $this->dic->getImplementationName($this->docComment, $this->nameForAnnotationParsing));
                if ($providerClassName && $providerClassName->getClassName()) {
                    $argumentFactory = $this->dic->getFullFactoryClassName($providerClassName->getClassName());
                    $this->className = $providerClassName->getClassName();
                    $this->factoryName = $argumentFactory;
                    $this->defaultValue = '\\' . $argumentFactory . '::getInstance(' . var_export(array_merge($providerClassName->getParameters(), $this->additionalArguments), true) . ')->get()';
                } else {
                    $argumentClass = $this->dic->getRealConfiguredClassName($this->config->getClassConfig($argumentClass), new ReflectionClass($argumentClass));

                    $argumentClassReflection = new ReflectionClass($argumentClass);
                    if (! $argumentClassReflection->isInstantiable() && ! $argumentClassReflection->hasMethod('getInstance')) {
                        $this->setParameterToDefault();
                        return;
                    }

                    $argumentFactory = $this->dic->getFullFactoryClassName($argumentClass);

                    $this->className = $argumentClass;
                    $this->factoryName = $argumentFactory;

                    $this->defaultValue = '\\' . $argumentFactory . '::getInstance(' . var_export($this->additionalArguments, true) . ')';
                }
            }

        } else {
            $this->setParameterToDefault();
        }
    }

    private function setParameterToDefault() {
        if (!empty($this->classConfig['params'][$this->name]['value'])) {
            $this->defaultValue = var_export($this->classConfig['params'][$this->name]['value'], true);
        } else if ($this->hasDefaultValue()) {
            $this->defaultValue = var_export($this->getDefaultValue(), true);
        } else {
            $this->defaultValue = 'null';
        }
    }

    protected function hasDefaultValue() {
        return $this->parameter->isDefaultValueAvailable();
    }

    /**
     * @throws ReflectionException
     */
    protected function getDefaultValue() {
        return $this->parameter->getDefaultValue();
    }

    protected function hasClass(): bool {
        return ReflectionClassHelper::getClassNameFromReflectionParameter($this->parameter) !== null;
    }

    protected function getClass(): ?string {
        return ReflectionClassHelper::getClassNameFromReflectionParameter($this->parameter);
    }

    public function getFactoryName() {
        return $this->factoryName;
    }

    public function getClassName() {
        return $this->className;
    }
}
