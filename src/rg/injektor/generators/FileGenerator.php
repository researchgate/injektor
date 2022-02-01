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
use ProxyManager\Generator\ClassGenerator;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolderGenerator;
use Laminas\Code\Generator;
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
     * @return \Laminas\Code\Generator\FileGenerator|null
     */
    public function getGeneratedFile() {
        $classConfig = $this->config->getClassConfig($this->fullClassName);
        $factoryName = $this->dic->getFactoryClassName($this->fullClassName);
        $lazyProxyClassName = null;

        $classReflection = $this->dic->getClassReflection($this->fullClassName);

        if (strpos($classReflection->getDocComment(), '@generator ignore') !== false) {
            return null;
        }

        $factoryClass = new \rg\injektor\generators\FactoryClass($factoryName);
        $getInstanceMethod = new \rg\injektor\generators\GetInstanceMethod();
        $createInstanceMethod = new \rg\injektor\generators\CreateInstanceMethod();
        $loadDependenciesMethod = new \rg\injektor\generators\LoadDependenciesMethod();

        $file = new Generator\FileGenerator();
        $file->setNamespace('rg\injektor\generated');
        $file->setFilename($this->factoryPath . DIRECTORY_SEPARATOR . $factoryName . '.php');

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
        $isService = $this->dic->isConfiguredAsService($classConfig, $classReflection);
        $isLazy = $this->dic->supportsLazyLoading() && $this->config->isLazyLoading() && $this->dic->isConfiguredAsLazy($classConfig, $classReflection);

        $createInstanceBody = '';

        if ($isLazy) {
            $createInstanceBody .= 'self::loadDependencies();' . PHP_EOL;
        }

        $createInstanceBody .= '$i = 0;' . PHP_EOL;
        if ($isSingleton || $isService) {
            $defaultValue = new Generator\PropertyValueGenerator(array(), Generator\ValueGenerator::TYPE_ARRAY, Generator\ValueGenerator::OUTPUT_SINGLE_LINE);
            $property = new Generator\PropertyGenerator('instance', $defaultValue, Generator\PropertyGenerator::FLAG_PRIVATE);
            $property->setStatic(true);
            $factoryClass->addPropertyFromGenerator($property);
        }

        $fullClassNameRefection = new \ReflectionClass($this->fullClassName);
        $providerClassName = $this->dic->getProviderClassName($classConfig, $fullClassNameRefection, null);
        if ($providerClassName && $providerClassName->getClassName()) {
            $argumentFactory = $this->dic->getFullFactoryClassName($providerClassName->getClassName());
            $this->factoryGenerator->processFileForClass($providerClassName->getClassName());
            $createInstanceBody .= '$instance = \\' . $argumentFactory . '::getInstance(array())->get();' . PHP_EOL;
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

                $createInstanceBody .= 'if (!$parameters) {' . PHP_EOL;

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
                        $createInstanceBody .= '    ' . $injectionParameter->getProcessingBody();
                    } catch (\Exception $e) {
                        $createInstanceBody .= '    ' . $injectionParameter->getDefaultProcessingBody();
                    }
                }

                $createInstanceBody .= '}' . PHP_EOL;
                $createInstanceBody .= 'else if (array_key_exists(0, $parameters)) {' . PHP_EOL;

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
                        $createInstanceBody .= '    ' . $injectionParameter->getProcessingBody();
                    } catch (\Exception $e) {
                        $createInstanceBody .= '    ' . $injectionParameter->getDefaultProcessingBody();
                    }
                }

                $createInstanceBody .= '}' . PHP_EOL;
                $createInstanceBody .= 'else {' . PHP_EOL;

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
                        $createInstanceBody .= '    ' . $injectionParameter->getProcessingBody();
                    } catch (\Exception $e) {
                        $createInstanceBody .= '    ' . $injectionParameter->getDefaultProcessingBody();
                    }
                }

                $createInstanceBody .= '}' . PHP_EOL;
            }

            // Property injection
            $this->injectProperties($classConfig, $classReflection);

            if (count($this->injectableProperties) > 0) {
                $proxyName = $this->dic->getProxyClassName($this->fullClassName);
                if ($this->dic->isSingleton($classReflection)) {
                    $file->setClass($this->createProxyClass($proxyName));
                    $createInstanceBody .= PHP_EOL . '$instance = ' . $proxyName . '::getInstance(' . implode(', ', $this->constructorArgumentStringParts) . ');' . PHP_EOL;
                } else {
                    $file->setClass($this->createProxyClass($proxyName));
                    $createInstanceBody .= PHP_EOL . '$instance = new ' . $proxyName . '(' . implode(', ', $this->constructorArgumentStringParts) . ');' . PHP_EOL;
                }
            } else {
                if ($this->dic->isSingleton($classReflection)) {
                    $createInstanceBody .= PHP_EOL . '$instance = \\' . $this->fullClassName . '::getInstance(' . implode(', ', $this->constructorArgumentStringParts) . ');' . PHP_EOL;
                } else {
                    $createInstanceBody .= PHP_EOL . '$instance = new \\' . $this->fullClassName . '(' . implode(', ', $this->constructorArgumentStringParts) . ');' . PHP_EOL;
                }
            }
        }

        // Make the newly created instance immediately available for property injection
        if ($isSingleton) {
            $createInstanceBody .= '$singletonKey = serialize($parameters) . "#" . getmypid();' . PHP_EOL;
            $createInstanceBody .= 'self::$instance[$singletonKey] = $instance;' . PHP_EOL;
        }
        if ($isService) {
            $createInstanceBody .= 'self::$instance = $instance;' . PHP_EOL;
        }

        foreach ($this->injectableArguments as $injectableArgument) {
            $createInstanceBody .= '$instance->propertyInjection' . $injectableArgument->getName() . '();' . PHP_EOL;
        }

        $getInstanceBody = '';
        if ($isSingleton) {
            $getInstanceBody .= '$singletonKey = serialize($parameters) . "#" . getmypid();' . PHP_EOL;
            $getInstanceBody .= 'if (isset(self::$instance[$singletonKey])) {' . PHP_EOL;
            $getInstanceBody .= '    return self::$instance[$singletonKey];' . PHP_EOL;
            $getInstanceBody .= '}' . PHP_EOL . PHP_EOL;
        }

        if ($isService) {
            $getInstanceBody .= 'if (self::$instance) {' . PHP_EOL;
            $getInstanceBody .= '    return self::$instance;' . PHP_EOL;
            $getInstanceBody .= '}' . PHP_EOL . PHP_EOL;
        }

        if ($isLazy) {
            // Create
            $lazyProxyClassName = $this->dic->getLazyProxyClassName($this->fullClassName);
            $getInstanceBody .= '$instance = ' . $lazyProxyClassName . '::staticProxyConstructor(' . PHP_EOL;
            $getInstanceBody .= '    function (&$wrappedObject, $proxy) use ($parameters) {' . PHP_EOL;
            $getInstanceBody .= '        $proxy->setProxyInitializer(null);' . PHP_EOL;
            $getInstanceBody .= '        $wrappedObject = self::createInstance($parameters);' . PHP_EOL;
            $getInstanceBody .= '        return true;' . PHP_EOL;
            $getInstanceBody .= '    }' . PHP_EOL;
            $getInstanceBody .= ');' . PHP_EOL;
            $getInstanceBody .= '';

            // Store the service/singleton instance
            if ($isSingleton) {
                $getInstanceBody .= '$singletonKey = serialize($parameters) . "#" . getmypid();' . PHP_EOL;
                $getInstanceBody .= 'self::$instance[$singletonKey] = $instance;' . PHP_EOL;
            }
            if ($isService) {
                $getInstanceBody .= 'self::$instance = $instance;' . PHP_EOL;
            }

            // Create instance method
            $createInstanceBody .= 'return $instance;' . PHP_EOL;
            $createInstanceMethod->setBody($createInstanceBody);
            $factoryClass->addMethodFromGenerator($createInstanceMethod);
        } else {
            $getInstanceBody .= $createInstanceBody;
        }

        $getInstanceBody .= 'return $instance;' . PHP_EOL;

        $getInstanceMethod->setBody($getInstanceBody);
        $factoryClass->addMethodFromGenerator($getInstanceMethod);

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

        // Dependency require statements
        $this->usedFactories = array_unique($this->usedFactories);
        $requiredFactoryFiles = array();
        foreach ($this->usedFactories as $usedFactory) {
            $usedFactory = str_replace('rg\injektor\generated\\', '', $usedFactory);
            $requiredFactoryFiles[] = $usedFactory . '.php';
        }
        if ($isLazy) {
            // When the class is lazy, only load the dependencies when the real instance is created
            $loadDependenciesBody = '';
            foreach ($requiredFactoryFiles as $usedFactory) {
                $loadDependenciesBody .= 'require_once \'' . $usedFactory . '\';' . PHP_EOL;
            }
            $loadDependenciesMethod->setBody($loadDependenciesBody);
            $factoryClass->addMethodFromGenerator($loadDependenciesMethod);
        } else {
            // When the class is not lazy loaded, we can get all the dependencies right away, because we'll need them for instance creation
            $file->setRequiredFiles($requiredFactoryFiles);
        }

        $file->setClass($factoryClass);

        // Add lazy proxy class
        if ($isLazy) {
            $proxyGenerator = new LazyLoadingValueHolderGenerator();
            $generatedClass = new ClassGenerator($lazyProxyClassName);
            $proxyGenerator->generate($fullClassNameRefection, $generatedClass);
            $file->setClass($generatedClass);
        }

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
                $propertyClass = $this->dic->getClassFromProperty($injectableProperty);
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
            $factoryMethod->setParameter(new \Laminas\Code\Generator\ParameterGenerator('parameters', 'array', array()));

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
     * @return \Laminas\Code\Generator\ClassGenerator
     */
    private function createProxyClass($proxyName) {
        $proxyClass = new Generator\ClassGenerator($proxyName);
        $proxyClass->setExtendedClass($this->fullClassName);
        foreach ($this->injectableArguments as $injectableArgument) {
            $injectorMethod = new \Laminas\Code\Generator\MethodGenerator('propertyInjection' . $injectableArgument->getName());
            $injectorMethod->setBody($injectableArgument->getProcessingBody());
            $proxyClass->addMethodFromGenerator($injectorMethod);
        }
        return $proxyClass;
    }
}
