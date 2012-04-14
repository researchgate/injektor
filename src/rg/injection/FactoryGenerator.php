<?php
/*
 * This file is part of rg\injection.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injection;

use Zend\Code\Generator;

abstract class FactoryGenerator {

    /**
     * @var array
     */
    private $generated = array();

    /**
     * @var \rg\injection\Configuration
     */
    private $config;

    /**
     * @var string
     */
    private $factoryPath;

    /**
     * @param Configuration $config
     * @param string $path
     */
    public function __construct(Configuration $config, $path) {
        $this->config = $config;
        $this->factoryPath = $path;
    }

    /**
     * @abstract
     * @param string $fullClassName
     */
    abstract public function processFileForClass($fullClassName);

    /**
     * @param string $fullClassName
     * @param bool $addFileDocBlock
     * @return \Zend\Code\Generator\FileGenerator
     */
    protected function generateFileForClass($fullClassName, $addFileDocBlock = true) {
        $fullClassName = trim($fullClassName, '\\');

        if (in_array($fullClassName, $this->generated)) {
            return;
        }

        $dic = new FactoryDependencyInjectionContainer($this->config);

        $classConfig = $this->config->getClassConfig($fullClassName);
        $factoryName = $dic->getFactoryClassName($fullClassName);

        $classReflection = $dic->getClassReflection($fullClassName);

        if (strpos($classReflection->getDocComment(), '@generator ignore') !== false) {
            return;
        }

        $factoryClass = new \rg\injection\generators\FactoryClass($factoryName);
        $instanceMethod = new \rg\injection\generators\InstanceMethod();

        $arguments = array();

        $usedFactories = array();

        $constructorReflection = null;
        if ($dic->isSingleton($classReflection)) {
            //$parameters = $dic->getConstructorArguments($classReflection, $classConfig, array(), 'getInstance');
            $constructorReflection = $classReflection->getMethod('getInstance');
            $arguments = $constructorReflection->getParameters();
        } else {
            //$parameters = $dic->getConstructorArguments($classReflection, $classConfig);
            if ($classReflection->hasMethod('__construct')) {
                $constructorReflection = $classReflection->getMethod('__construct');
                $arguments = $constructorReflection->getParameters();
            }
        }

        $isSingleton = $dic->isConfiguredAsSingleton($classConfig, $classReflection);

        $body = '';

        if ($isSingleton) {
            $property = new Generator\PropertyGenerator('instance', array(), Generator\PropertyGenerator::FLAG_PRIVATE);
            $property->setStatic(true);
            $factoryClass->setProperty($property);

            $instanceMethod->addSingletonSupport();
        }
        $constructorArgumentStringParts = array();
        $realConstructorArgumentStringParts = array();
        $constructorArguments = array();
        $bottomBody = '';

        $this->generated[] = $fullClassName;

        foreach ($arguments as $argument) {
            /** @var \ReflectionParameter $argument  */

            $injectionParameter = new \rg\injection\generators\InjectionParameter(
                $argument,
                $classConfig,
                $this->config,
                $dic
            );

            $instanceMethod->addInstanceParameter($injectionParameter);

            $argumentName = $argument->name;

            $body .= $injectionParameter->getPreProcessingBody();
            $bottomBody .= $injectionParameter->getPostProcessingBody();
            try {
                if ($injectionParameter->getParameterClassName()) {
                    $this->processFileForClass($injectionParameter->getParameterClassName());
                }
                if ($injectionParameter->getParameterFactoryName()) {
                    $usedFactories[] = $injectionParameter->getParameterFactoryName();
                }
            } catch (InjectionException $e) {
                $body .= '$methodParameters[\'' . $argumentName . '\'] = isset($parameters[\'' . $argumentName . '\']) ? $parameters[\'' . $argumentName . '\'] : null;' . PHP_EOL;
                $bottomBody .= '$' . $argumentName . ' = isset($methodParameters[\'' . $argumentName . '\']) ? $methodParameters[\'' . $argumentName . '\'] : null;' . PHP_EOL;
            }
            $constructorArguments[] = $argumentName;
            $constructorArgumentStringParts[] = '$' . $argumentName;
            $realConstructorArgumentStringParts[] = '$' . $argumentName;

        }

        $injectableProperties = array();
        try {
            $injectableProperties = $dic->getInjectableProperties($classReflection);
            $injectableArguments = array();
            foreach ($injectableProperties as $key => $injectableProperty) {
                try {
                    $propertyClass = $dic->getClassFromVarTypeHint($injectableProperty->getDocComment());
                    if (!$propertyClass) {
                        unset($injectableProperties[$key]);
                        continue;
                    }

                    $propertyName = $injectableProperty->name;

                    if ($propertyClass === 'rg\injection\DependencyInjectionContainer') {
                        $injectableArguments[] = $propertyName;
                        $constructorArguments[] = $propertyName;
                        $constructorArgumentStringParts[] = '$' . $propertyName;

                        $body .= '$' . $propertyName . ' = \\' . $propertyClass . '::getInstance();' . PHP_EOL;
                    } else {
                        $providerClassName = $dic->getProviderClassName($this->config->getClassConfig($propertyClass), new \ReflectionClass($propertyClass), $dic->getImplementationName($injectableProperty->getDocComment(), $propertyName));
                        if ($providerClassName && $providerClassName->getClassName()) {
                            $propertyFactory = $dic->getFullFactoryClassName($providerClassName->getClassName());
                            $this->processFileForClass($providerClassName->getClassName());
                            $injectableArguments[] = $propertyName;
                            $constructorArguments[] = $propertyName;
                            $constructorArgumentStringParts[] = '$' . $propertyName;

                            $body .= '$' . $propertyName . ' = \\' . $propertyFactory . '::getInstance(' . var_export($providerClassName->getParameters(), true) . ')->get();' . PHP_EOL;
                            $usedFactories[] = $propertyFactory;
                        } else {
                            $propertyClass = $dic->getRealConfiguredClassName($this->config->getClassConfig($propertyClass), new \ReflectionClass($propertyClass));

                            $propertyFactory = $dic->getFullFactoryClassName($propertyClass);

                            $this->processFileForClass($propertyClass);

                            $injectableArguments[] = $propertyName;
                            $constructorArguments[] = $propertyName;
                            $constructorArgumentStringParts[] = '$' . $propertyName;

                            $body .= '$' . $propertyName . ' = \\' . $propertyFactory . '::getInstance();' . PHP_EOL;
                            $usedFactories[] = $propertyFactory;
                        }
                    }
                } catch (InjectionException $e) {
                    unset($injectableProperties[$key]);
                }
            }
        } catch (InjectionException $e) {
        }

        $proxyClass = null;

        $middleBody = '';
        $endBody = '';
        if ($constructorReflection) {
            $beforeAspects = $dic->getAspects($constructorReflection, 'before');
            foreach ($beforeAspects as $aspect) {
                $aspect['class'] = trim($aspect['class'], '\\');
                $aspectFactory = $dic->getFactoryClassName($aspect['class']);
                $middleBody .= '$aspect = ' . $aspectFactory . '::getInstance();' . PHP_EOL;
                $middleBody .= '$methodParameters = $aspect->execute(' . var_export($aspect['aspectArguments'], true) . ', \'' . $fullClassName . '\', \'' . $constructorReflection->name . '\', $methodParameters);' . PHP_EOL;

                $usedFactories[] = $aspectFactory;
                $this->processFileForClass($aspect['class']);
            }

            $interceptAspects = $dic->getAspects($constructorReflection, 'intercept');
            if (count($interceptAspects) > 0) {
                $middleBody .= '$result = false;' . PHP_EOL;
                foreach ($interceptAspects as $aspect) {
                    $aspect['class'] = trim($aspect['class'], '\\');
                    $aspectFactory = $dic->getFactoryClassName($aspect['class']);
                    $middleBody .= '$aspect = ' . $aspectFactory. '::getInstance();' . PHP_EOL;
                    $middleBody .= '$result = $aspect->execute(' . var_export($aspect['aspectArguments'], true) . ', \'' . $fullClassName . '\', \'' . $constructorReflection->name . '\', $methodParameters, $result);' . PHP_EOL;
                    $usedFactories[] = $aspectFactory;
                    $this->processFileForClass($aspect['class']);
                }
                $middleBody .= 'if ($result !== false) {' . PHP_EOL;
                $middleBody .= '    return $result;' . PHP_EOL;
                $middleBody .= '}' . PHP_EOL;
            }
            $body .= $middleBody . $bottomBody;
            $afterAspects = $dic->getAspects($constructorReflection, 'after');
            foreach ($afterAspects as $aspect) {
                $aspect['class'] = trim($aspect['class'], '\\');
                $aspectFactory = $dic->getFactoryClassName($aspect['class']);
                $endBody .= '$aspect = ' . $aspectFactory . '::getInstance();' . PHP_EOL;
                $endBody .= '$instance = $aspect->execute(' . var_export($aspect['aspectArguments'], true) . ', \'' . $fullClassName . '\', \'' . $constructorReflection->name . '\', $instance);' . PHP_EOL;
                $usedFactories[] = $aspectFactory;
                $this->processFileForClass($aspect['class']);
            }
        } else {
            $body .= $bottomBody;
        }

        if (count($injectableProperties) > 0) {
            $proxyName = $dic->getProxyClassName($fullClassName);
            if ($dic->isSingleton($classReflection)) {
                $proxyClass = $this->getStaticProxyClass($proxyName, $fullClassName, $constructorArguments, $injectableArguments, $realConstructorArgumentStringParts);
                $body .= PHP_EOL . '$instance = ' . $proxyName . '::getProxyInstance(' . implode(', ', $constructorArgumentStringParts) . ');' . PHP_EOL;
            } else {
                $proxyClass = $this->getProxyClass($proxyName, $fullClassName, $constructorArguments, $injectableArguments, $realConstructorArgumentStringParts, $classReflection->hasMethod('__construct'));
                $body .= PHP_EOL . '$instance = new ' . $proxyName . '(' . implode(', ', $constructorArgumentStringParts) . ');' . PHP_EOL;
            }
        } else {
            if ($dic->isSingleton($classReflection)) {
                $body .= PHP_EOL . '$instance = \\' . $fullClassName . '::getInstance(' . implode(', ', $constructorArgumentStringParts) . ');' . PHP_EOL;
            } else {
                $body .= PHP_EOL . '$instance = new \\' . $fullClassName . '(' . implode(', ', $constructorArgumentStringParts) . ');' . PHP_EOL;
            }

        }

        $body .= $endBody;

        if ($isSingleton) {
            $body .= 'self::$instance[$singletonKey] = $instance;' . PHP_EOL;
        }

        $body .= 'return $instance;' . PHP_EOL;

        $instanceMethod->setBody($body);
        $instanceMethod->setStatic(true);
        $factoryClass->setMethod($instanceMethod);

        $methods = $classReflection->getMethods();
        foreach ($methods as $method) {
            /** @var \ReflectionMethod $method */
            if ($method->isPublic() &&
                substr($method->name, 0, 2) !== '__' &&
                !$method->isStatic()
            ) {

                $factoryMethod = new Generator\MethodGenerator($dic->getFactoryMethodName($method->name));
                $factoryMethod->setParameter(new Generator\ParameterGenerator('object'));
                $factoryMethod->setStatic(true);

                $arguments = $method->getParameters();

                $factoryMethodBody = '$methodParameters = array();' . PHP_EOL;

                $constructorArgumentStringParts = array();

                if (count($arguments) > 0) {
                    $factoryMethod->setParameter(new \Zend\Code\Generator\ParameterGenerator('parameters', 'array', array()));
                }

                $topBody = '';
                $bottomBody = '';
                $middleBody = '';
                foreach ($arguments as $argument) {
                    /** @var \ReflectionParameter $argument */

                    $injectionParameter = new \rg\injection\generators\InjectionParameter(
                        $argument,
                        $classConfig,
                        $this->config,
                        $dic
                    );

                    $argumentName = $argument->name;

                    $topBody .= $injectionParameter->getPreProcessingBody();
                    $bottomBody .= $injectionParameter->getPostProcessingBody();
                    try {
                        if ($injectionParameter->getParameterClassName()) {
                            $this->processFileForClass($injectionParameter->getParameterClassName());
                        }
                        if ($injectionParameter->getParameterFactoryName()) {
                            $usedFactories[] = $injectionParameter->getParameterFactoryName();
                        }
                    } catch (InjectionException $e) {
                        $topBody .= '$methodParameters[\'' . $argumentName . '\'] = isset($parameters[\'' . $argumentName . '\']) ? $parameters[\'' . $argumentName . '\'] : null;' . PHP_EOL;
                        $bottomBody .= '$' . $argumentName . ' = isset($methodParameters[\'' . $argumentName . '\']) ? $methodParameters[\'' . $argumentName . '\'] : null;' . PHP_EOL;
                    }

                    $constructorArgumentStringParts[] = '$' . $argumentName;
                }
                $beforeAspects = $dic->getAspects($method, 'before');
                foreach ($beforeAspects as $aspect) {
                    $aspect['class'] = trim($aspect['class'], '\\');
                    $aspectFactory = $dic->getFactoryClassName($aspect['class']);
                    $middleBody .= '$aspect = ' . $aspectFactory . '::getInstance();' . PHP_EOL;
                    $middleBody .= '$methodParameters = $aspect->execute(' . var_export($aspect['aspectArguments'], true) . ', \'' . $fullClassName . '\', \'' . $method->name . '\', $methodParameters);' . PHP_EOL;

                    $usedFactories[] = $aspectFactory;
                    $this->processFileForClass($aspect['class']);
                }

                $interceptAspects = $dic->getAspects($method, 'intercept');
                if (count($interceptAspects) > 0) {
                    $middleBody .= '$result = false;' . PHP_EOL;
                    foreach ($interceptAspects as $aspect) {
                        $aspect['class'] = trim($aspect['class'], '\\');
                        $aspectFactory = $dic->getFactoryClassName($aspect['class']);
                        $middleBody .= '$aspect = ' . $aspectFactory . '::getInstance();' . PHP_EOL;
                        $middleBody .= '$result = $aspect->execute(' . var_export($aspect['aspectArguments'], true) . ', \'' . $fullClassName . '\', \'' . $method->name . '\', $methodParameters, $result);' . PHP_EOL;
                        $usedFactories[] = $aspectFactory;
                        $this->processFileForClass($aspect['class']);
                    }
                    $middleBody .= 'if ($result !== false) {' . PHP_EOL;
                    $middleBody .= '    return $result;' . PHP_EOL;
                    $middleBody .= '}' . PHP_EOL;
                }

                $factoryMethodBody .= PHP_EOL . $topBody . PHP_EOL . $middleBody . PHP_EOL . $bottomBody . PHP_EOL;
                $factoryMethodBody .= '$result = $object->' . $method->name . '(' . implode(', ', $constructorArgumentStringParts) . ');' . PHP_EOL . PHP_EOL;

                $afterAspects = $dic->getAspects($method, 'after');
                foreach ($afterAspects as $aspect) {
                    $aspect['class'] = trim($aspect['class'], '\\');
                    $aspectFactory = $dic->getFactoryClassName($aspect['class']);
                    $factoryMethodBody .= '$aspect = ' . $aspectFactory . '::getInstance();' . PHP_EOL;
                    $factoryMethodBody .= '$result = $aspect->execute(' . var_export($aspect['aspectArguments'], true) . ', \'' . $fullClassName . '\', \'' . $method->name . '\', $result);' . PHP_EOL;
                    $usedFactories[] = $aspectFactory;
                    $this->processFileForClass($aspect['class']);
                }

                $factoryMethodBody .= PHP_EOL . 'return $result;';
                $factoryMethod->setBody($factoryMethodBody);
                $factoryClass->setMethod($factoryMethod);
            }
        }

        $file = new Generator\FileGenerator();
        $file->setNamespace('rg\injection\generated');
        $usedFactories = array_unique($usedFactories);
        foreach ($usedFactories as &$usedFactory) {
            $usedFactory = str_replace('rg\injection\generated\\', '', $usedFactory);
            $usedFactory = $this->factoryPath . DIRECTORY_SEPARATOR . $usedFactory . '.php';
        }
        $file->setRequiredFiles($usedFactories);
        $file->setClass($factoryClass);
        if ($proxyClass) {
            $file->setClass($proxyClass);
        }
        if ($addFileDocBlock) {
            $docblock = new Generator\DocblockGenerator('Generated by ' . get_class($this) . ' on ' . date('Y-m-d H:i:s'));
            $file->setDocblock($docblock);
        }
        $file->setFilename($this->factoryPath . DIRECTORY_SEPARATOR . $factoryName . '.php');

        return $file;
    }

    /**
     * @param string $proxyName
     * @param string $fullClassName
     * @param array $constructorArguments
     * @param array $injectableArguments
     * @param array $realConstructorArgumentStringParts
     * @return \Zend\Code\Generator\ClassGenerator
     */
    private function getProxyClass($proxyName, $fullClassName, $constructorArguments, $injectableArguments, $realConstructorArgumentStringParts, $hasConstructor) {
        $proxyClass = new Generator\ClassGenerator($proxyName);
        $proxyClass->setExtendedClass('\\' . $fullClassName);
        $constructor = new Generator\MethodGenerator('__construct');
        foreach ($constructorArguments as $constructorArgument) {
            $parameter = new Generator\ParameterGenerator($constructorArgument);
            $constructor->setParameter($parameter);
        }
        $constructorBody = '';
        foreach ($injectableArguments as $injectableArgument) {
            $constructorBody .= '$this->' . $injectableArgument . ' = $' . $injectableArgument . ';' . PHP_EOL;
        }
        if ($hasConstructor) {
            $constructorBody .= 'parent::__construct(' . implode(', ', $realConstructorArgumentStringParts) . ');' . PHP_EOL;
        }
        $constructor->setBody($constructorBody);
        $proxyClass->setMethod($constructor);
        return $proxyClass;
    }

    private function getStaticProxyClass($proxyName, $fullClassName, $constructorArguments, $injectableArguments, $realConstructorArgumentStringParts) {
        $proxyClass = new Generator\ClassGenerator($proxyName);
        $proxyClass->setExtendedClass('\\' . $fullClassName);
        $constructor = new Generator\MethodGenerator('getProxyInstance');
        $constructor->setStatic(true);
        foreach ($constructorArguments as $constructorArgument) {
            $parameter = new Generator\ParameterGenerator($constructorArgument);
            $constructor->setParameter($parameter);
        }
        $constructorBody = '$instance = parent::getInstance(' . implode(', ', $realConstructorArgumentStringParts) . ');' . PHP_EOL;
        ;
        foreach ($injectableArguments as $injectableArgument) {
            $constructorBody .= '$instance->' . $injectableArgument . ' = $' . $injectableArgument . ';' . PHP_EOL;
        }
        $constructorBody .= 'return $instance;' . PHP_EOL;
        $constructor->setBody($constructorBody);
        $proxyClass->setMethod($constructor);
        return $proxyClass;
    }

}