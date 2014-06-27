<?php
namespace rg\injektor\generators;
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Zend\Code\Generator;
use rg\injektor\Configuration;
use rg\injektor\FactoryDependencyInjectionContainer;

class FileGenerator {

    /**
     * @var FactoryGenerator
     */
    private $factoryGenerator;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var string
     */
    private $factoryPath;

    /**
     * @var string
     */
    private $fullClassName;

    /**
     * @var array
     */
    private $constructorArgumentStringParts = array();

    /**
     * @var array
     */
    private $usedFactories = array();

    /**
     * @var array
     */
    private $realConstructorArgumentStringParts = array();

    /**
     * @var array
     */
    private $constructorArguments = array();

    /**
     * @var InjectionProperty[]
     */
    private $injectableArguments = array();

    /**
     * @var array
     */
    private $injectableProperties = array();

    /**
     * @var FactoryDependencyInjectionContainer
     */
    private $dic;

    /**
     * @param FactoryGenerator $factoryGenerator
     * @param Configuration $config
     * @param string $factoryPath
     * @param string $fullClassName
     */
    public function __construct(FactoryGenerator $factoryGenerator, Configuration $config, $factoryPath, $fullClassName) {
        $this->factoryGenerator = $factoryGenerator;
        $this->config = $config;
        $this->factoryPath = $factoryPath;
        $this->fullClassName = $fullClassName;
        $this->dic = new FactoryDependencyInjectionContainer($this->config);
    }

    /**
     * @return \Zend\Code\Generator\FileGenerator|null
     */
    public function getGeneratedFile() {
        $classConfig = $this->config->getClassConfig($this->fullClassName);
        $factoryName = $this->dic->getFactoryClassName($this->fullClassName);

        $classReflection = $this->dic->getClassReflection($this->fullClassName);

        if (strpos($classReflection->getDocComment(), '@generator ignore') !== false) {
            return null;
        }

        $file = new Generator\FileGenerator();

        $factoryClass = new \rg\injektor\generators\FactoryClass($factoryName);
        $instanceMethod = new \rg\injektor\generators\InstanceMethod($this->factoryGenerator);

        $arguments = array();

        $constructorMethodReflection = null;
        if ($this->dic->isSingleton($classReflection)) {
            $constructorMethodReflection = $classReflection->getMethod('getInstance');
            $arguments = $constructorMethodReflection->getParameters();
        } else if ($classReflection->hasMethod('__construct')) {
            $constructorMethodReflection = $classReflection->getMethod('__construct');
            $arguments = $constructorMethodReflection->getParameters();
        }

        $isSingleton = $this->dic->isConfiguredAsSingleton($classConfig, $classReflection);

        $body = '$i = 0;' . PHP_EOL;

        if ($isSingleton) {
            $property = new Generator\PropertyGenerator('instance', array(), Generator\PropertyGenerator::FLAG_PRIVATE);
            $property->setStatic(true);
            $factoryClass->addPropertyFromGenerator($property);

            $body .= '$singletonKey = serialize($parameters) . "#" . getmypid();' . PHP_EOL;
            $body .= 'if (isset(self::$instance[$singletonKey])) {' . PHP_EOL;
            $body .= '    return self::$instance[$singletonKey];' . PHP_EOL;
            $body .= '}' . PHP_EOL . PHP_EOL;
        }

        $isService = $this->dic->isConfiguredAsService($classConfig, $classReflection);

        if ($isService) {
            $property = new Generator\PropertyGenerator('instance', null, Generator\PropertyGenerator::FLAG_PRIVATE);
            $property->setStatic(true);
            $factoryClass->addPropertyFromGenerator($property);

            $body .= 'if (self::$instance) {' . PHP_EOL;
            $body .= '    return self::$instance;' . PHP_EOL;
            $body .= '}' . PHP_EOL . PHP_EOL;
        }

        $providerClassName = $this->dic->getProviderClassName($classConfig, new \ReflectionClass($this->fullClassName), null);
        if ($providerClassName && $providerClassName->getClassName()) {
            $argumentFactory = $this->dic->getFullFactoryClassName($providerClassName->getClassName());
            $this->factoryGenerator->processFileForClass($providerClassName->getClassName());
            $body .= '$instance = \\' . $argumentFactory . '::getInstance(array())->get();' . PHP_EOL;
            $this->usedFactories[] = $argumentFactory;
        } else {
            // constructor method arguments

            if (count($arguments) > 0) {
                foreach ($arguments as $argument) {
                    /** @var \ReflectionParameter $argument */
                    $argumentName = $argument->name;
                    $this->constructorArguments[] = $argumentName;
                    $this->constructorArgumentStringParts[] = '$' . $argumentName;
                    $this->realConstructorArgumentStringParts[] = '$' . $argumentName;

                }

                $body .= 'if (!$parameters) {' . PHP_EOL;

                foreach ($arguments as $argument) {
                    /** @var \ReflectionParameter $argument */

                    $injectionParameter = new \rg\injektor\generators\InjectionParameter(
                        $argument,
                        $classConfig,
                        $this->config,
                        $this->dic,
                        InjectionParameter::MODE_NO_ARGUMENTS
                    );

                    try {
                        if ($injectionParameter->getClassName()) {
                            $this->factoryGenerator->processFileForClass($injectionParameter->getClassName());
                        }
                        if ($injectionParameter->getFactoryName()) {
                            $this->usedFactories[] = $injectionParameter->getFactoryName();
                        }
                        $body .= '    ' . $injectionParameter->getProcessingBody();
                    } catch (\Exception $e) {
                        $body .= '    ' . $injectionParameter->getDefaultProcessingBody();
                    }
                }

                $body .= '}' . PHP_EOL;
                $body .= 'else if (array_key_exists(0, $parameters)) {' . PHP_EOL;

                foreach ($arguments as $argument) {
                    /** @var \ReflectionParameter $argument */

                    $injectionParameter = new \rg\injektor\generators\InjectionParameter(
                        $argument,
                        $classConfig,
                        $this->config,
                        $this->dic,
                        InjectionParameter::MODE_NUMERIC
                    );

                    try {
                        if ($injectionParameter->getClassName()) {
                            $this->factoryGenerator->processFileForClass($injectionParameter->getClassName());
                        }
                        if ($injectionParameter->getFactoryName()) {
                            $this->usedFactories[] = $injectionParameter->getFactoryName();
                        }
                        $body .= '    ' . $injectionParameter->getProcessingBody();
                    } catch (\Exception $e) {
                        $body .= '    ' . $injectionParameter->getDefaultProcessingBody();
                    }
                }

                $body .= '}' . PHP_EOL;
                $body .= 'else {' . PHP_EOL;

                foreach ($arguments as $argument) {
                    /** @var \ReflectionParameter $argument */

                    $injectionParameter = new \rg\injektor\generators\InjectionParameter(
                        $argument,
                        $classConfig,
                        $this->config,
                        $this->dic,
                        InjectionParameter::MODE_STRING
                    );

                    try {
                        if ($injectionParameter->getClassName()) {
                            $this->factoryGenerator->processFileForClass($injectionParameter->getClassName());
                        }
                        if ($injectionParameter->getFactoryName()) {
                            $this->usedFactories[] = $injectionParameter->getFactoryName();
                        }
                        $body .= '    ' . $injectionParameter->getProcessingBody();
                    } catch (\Exception $e) {
                        $body .= '    ' . $injectionParameter->getDefaultProcessingBody();
                    }
                }

                $body .= '}' . PHP_EOL;
            }

            // Property injection
            $this->injectProperties($classConfig, $classReflection);

            if (count($this->injectableProperties) > 0) {
                $proxyName = $this->dic->getProxyClassName($this->fullClassName);
                if ($this->dic->isSingleton($classReflection)) {
                    $file->setClass($this->createProxyClass($proxyName));
                    $body .= PHP_EOL . '$instance = ' . $proxyName . '::getInstance(' . implode(', ', $this->constructorArgumentStringParts) . ');' . PHP_EOL;
                } else {
                    $file->setClass($this->createProxyClass($proxyName));
                    $body .= PHP_EOL . '$instance = new ' . $proxyName . '(' . implode(', ', $this->constructorArgumentStringParts) . ');' . PHP_EOL;
                }
            } else {
                if ($this->dic->isSingleton($classReflection)) {
                    $body .= PHP_EOL . '$instance = \\' . $this->fullClassName . '::getInstance(' . implode(', ', $this->constructorArgumentStringParts) . ');' . PHP_EOL;
                } else {
                    $body .= PHP_EOL . '$instance = new \\' . $this->fullClassName . '(' . implode(', ', $this->constructorArgumentStringParts) . ');' . PHP_EOL;
                }
            }
        }

        if ($isSingleton) {
            $body .= 'self::$instance[$singletonKey] = $instance;' . PHP_EOL;
        }
        if ($isService) {
            $body .= 'self::$instance = $instance;' . PHP_EOL;
        }

        foreach ($this->injectableArguments as $injectableArgument) {
            $body .= '$instance->propertyInjection' . $injectableArgument->getName() . '();' . PHP_EOL;
        }

        $body .= 'return $instance;' . PHP_EOL;

        $instanceMethod->setBody($body);
        $instanceMethod->setStatic(true);
        $factoryClass->addMethodFromGenerator($instanceMethod);

        // Add Factory Method
        $methods = $classReflection->getMethods();
        foreach ($methods as $method) {
            /** @var \ReflectionMethod $method */
            if ($method->isPublic() &&
                substr($method->name, 0, 2) !== '__'
            ) {
                $factoryMethod = $this->getFactoryMethod($method, $classConfig);
                $factoryClass->addMethodFromGenerator($factoryMethod);
            }
        }

        // Generate File

        $file->setNamespace('rg\injektor\generated');
        $this->usedFactories = array_unique($this->usedFactories);
        foreach ($this->usedFactories as &$usedFactory) {
            $usedFactory = str_replace('rg\injektor\generated\\', '', $usedFactory);
            $usedFactory = $usedFactory . '.php';
        }
        $file->setRequiredFiles($this->usedFactories);
        $file->setClass($factoryClass);
        $file->setFilename($this->factoryPath . DIRECTORY_SEPARATOR . $factoryName . '.php');

        return $file;
    }

    /**
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     */
    private function injectProperties(array $classConfig, \ReflectionClass $classReflection) {
        try {
            $this->injectableProperties = $this->dic->getInjectableProperties($classReflection);
            foreach ($this->injectableProperties as $key => $injectableProperty) {
                /** @var \ReflectionProperty $injectableProperty */
                $propertyClass = $this->dic->getClassFromVarTypeHint($injectableProperty->getDocComment());
                if (!$propertyClass) {
                    unset($this->injectableProperties[$key]);
                    continue;
                }

                $injectionProperty = new \rg\injektor\generators\InjectionProperty(
                    $injectableProperty, $classConfig, $this->config, $this->dic
                );

                try {
                    if ($injectionProperty->getClassName()) {
                        $this->factoryGenerator->processFileForClass($injectionProperty->getClassName());
                    }
                    if ($injectionProperty->getFactoryName()) {
                        $this->usedFactories[] = $injectionProperty->getFactoryName();
                    }
                    $this->injectableArguments[] = $injectionProperty;
                } catch (\Exception $e) {
                    unset($this->injectableProperties[$key]);
                }
            }
        } catch (\Exception $e) {
        }
    }

    protected function getFactoryMethod(\ReflectionMethod $method, $classConfig) {
        $factoryMethod = new Generator\MethodGenerator($this->dic->getFactoryMethodName($method->name));
        $factoryMethod->setParameter(new Generator\ParameterGenerator('object'));
        $factoryMethod->setStatic(true);

        $arguments = $method->getParameters();

        $body = '$i = 0;' . PHP_EOL;
        $methodArgumentStringParts = array();

        if (count($arguments) > 0) {
            $factoryMethod->setParameter(new \Zend\Code\Generator\ParameterGenerator('parameters', 'array', array()));

            foreach ($arguments as $argument) {
                /** @var \ReflectionParameter $argument */
                $argumentName = $argument->name;
                $methodArgumentStringParts[] = '$' . $argumentName;
            }

            $body .= 'if (!$parameters) {' . PHP_EOL;

            foreach ($arguments as $argument) {
                /** @var \ReflectionParameter $argument */
                $injectionParameter = new \rg\injektor\generators\InjectionParameter(
                    $argument,
                    $classConfig,
                    $this->config,
                    $this->dic,
                    InjectionParameter::MODE_NO_ARGUMENTS
                );

                try {
                    if ($injectionParameter->getClassName()) {
                        $this->factoryGenerator->processFileForClass($injectionParameter->getClassName());
                    }
                    if ($injectionParameter->getFactoryName()) {
                        $this->usedFactories[] = $injectionParameter->getFactoryName();
                    }
                    $body .= '    ' . $injectionParameter->getProcessingBody();
                } catch (\Exception $e) {
                    $body .= '    ' . $injectionParameter->getDefaultProcessingBody();
                }
            }

            $body .= '}' . PHP_EOL;
            $body .= 'else if (array_key_exists(0, $parameters)) {' . PHP_EOL;

            foreach ($arguments as $argument) {
                /** @var \ReflectionParameter $argument */
                $injectionParameter = new \rg\injektor\generators\InjectionParameter(
                    $argument,
                    $classConfig,
                    $this->config,
                    $this->dic,
                    InjectionParameter::MODE_NUMERIC
                );

                try {
                    if ($injectionParameter->getClassName()) {
                        $this->factoryGenerator->processFileForClass($injectionParameter->getClassName());
                    }
                    if ($injectionParameter->getFactoryName()) {
                        $this->usedFactories[] = $injectionParameter->getFactoryName();
                    }
                    $body .= '    ' . $injectionParameter->getProcessingBody();
                } catch (\Exception $e) {
                    $body .= '    ' . $injectionParameter->getDefaultProcessingBody();
                }
            }

            $body .= '}' . PHP_EOL;
            $body .= 'else {' . PHP_EOL;

            foreach ($arguments as $argument) {
                /** @var \ReflectionParameter $argument */
                $injectionParameter = new \rg\injektor\generators\InjectionParameter(
                    $argument,
                    $classConfig,
                    $this->config,
                    $this->dic,
                    InjectionParameter::MODE_STRING
                );

                try {
                    if ($injectionParameter->getClassName()) {
                        $this->factoryGenerator->processFileForClass($injectionParameter->getClassName());
                    }
                    if ($injectionParameter->getFactoryName()) {
                        $this->usedFactories[] = $injectionParameter->getFactoryName();
                    }
                    $body .= '    ' . $injectionParameter->getProcessingBody();
                } catch (\Exception $e) {
                    $body .= '    ' . $injectionParameter->getDefaultProcessingBody();
                }
            }

            $body .= '}' . PHP_EOL;
        }

        $body .= '$result = $object->' . $method->name . '(' . implode(', ', $methodArgumentStringParts) . ');' . PHP_EOL . PHP_EOL;

        $body .= PHP_EOL . 'return $result;';
        $factoryMethod->setBody($body);
        return $factoryMethod;
    }

    /**
     * @param string $proxyName
     * @return \Zend\Code\Generator\ClassGenerator
     */
    private function createProxyClass($proxyName) {
        $proxyClass = new Generator\ClassGenerator($proxyName);
        $proxyClass->setExtendedClass('\\' . $this->fullClassName);
        foreach ($this->injectableArguments as $injectableArgument) {
            $injectorMethod = new \Zend\Code\Generator\MethodGenerator('propertyInjection' . $injectableArgument->getName());
            $injectorMethod->setBody($injectableArgument->getProcessingBody());
            $proxyClass->addMethodFromGenerator($injectorMethod);
        }
        return $proxyClass;
    }
}
