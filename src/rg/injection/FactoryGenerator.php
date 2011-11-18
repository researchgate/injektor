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
     * @param Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;
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

        $this->generated[] = $fullClassName;

        $dic = new FactoryDependencyInjectionContainer($this->config);

        $classConfig = $this->config->getClassConfig($fullClassName);
        $factoryName = $dic->getFactoryClassName($fullClassName);

        $factoryClass = new Generator\ClassGenerator($factoryName);
        $instanceMethod = new Generator\MethodGenerator('getInstance');
        $parameter = new \Zend\Code\Generator\ParameterGenerator('parameters', 'array', array());
        $instanceMethod->setParameter($parameter);
        $classReflection = $dic->getClassReflection($fullClassName);
        if ($dic->isSingleton($classReflection)) {
            $arguments = $dic->getConstructorArguments($classReflection, $classConfig, array(), 'getInstance');
        } else {
            $arguments = $dic->getConstructorArguments($classReflection, $classConfig);
        }

        $isSingleton = $dic->isConfiguredAsSingleton($classConfig, $classReflection);

        $body = '';

        if ($isSingleton) {
            $property = new Generator\PropertyGenerator('instance', null, Generator\PropertyGenerator::FLAG_PRIVATE);
            $property->setStatic(true);
            $factoryClass->setProperty($property);

            $body .= 'if (self::$instance) {' . PHP_EOL;
            $body .= '    return self::$instance;' . PHP_EOL;
            $body .= '}' . PHP_EOL . PHP_EOL;
        }
        $constructorArgumentStringParts = array();
        $realConstructorArgumentStringParts = array();
        $constructorArguments = array();

        foreach ($arguments as $argumentName => $argumentClass) {
            if (is_object($argumentClass)) {
                $argumentClass = get_class($argumentClass);
                $argumentFactory = $dic->getFullFactoryClassName($argumentClass);
                $body .= '$' . $argumentName . ' = isset($parameters[\'' . $argumentName . '\']) ? $parameters[\'' . $argumentName . '\'] : ' . $argumentFactory . '::getInstance();' . PHP_EOL;

                $this->processFileForClass($argumentClass);
            } else {
                $body .= '$' . $argumentName . ' = isset($parameters[\'' . $argumentName . '\']) ? $parameters[\'' . $argumentName . '\'] : \'' . $argumentClass . '\';' . PHP_EOL;
            }
            $constructorArguments[] = $argumentName;
            $constructorArgumentStringParts[] = '$' . $argumentName;
            $realConstructorArgumentStringParts[] = '$' . $argumentName;

        }

        $injectableProperties = $dic->getInjectableProperties($classReflection);
        $injectableArguments = array();
        foreach ($injectableProperties as $injectableProperty) {

            $propertyClass = $dic->getClassFromVarTypeHint($classConfig, $injectableProperty->getDocComment());

            $propertyName = $injectableProperty->name;
            $propertyFactory = $dic->getFullFactoryClassName($propertyClass);
            $body .= '$' . $propertyName . ' = ' . $propertyFactory . '::getInstance();' . PHP_EOL;

            $injectableArguments[] = $propertyName;
            $constructorArguments[] = $propertyName;
            $constructorArgumentStringParts[] = '$' . $propertyName;

            $this->processFileForClass($propertyClass);
        }

        $proxyClass = null;

        if (count($injectableProperties) > 0) {
            $proxyName = $dic->getProxyClassName($fullClassName);
            if ($dic->isSingleton($classReflection)) {
                $proxyClass = $this->getStaticProxyClass($proxyName, $fullClassName, $constructorArguments, $injectableArguments, $realConstructorArgumentStringParts);
                $body .= PHP_EOL . '$instance = ' . $proxyName . '::getInstance(' . implode(', ', $constructorArgumentStringParts) . ');' . PHP_EOL;
            } else {
                $proxyClass = $this->getProxyClass($proxyName, $fullClassName, $constructorArguments, $injectableArguments, $realConstructorArgumentStringParts);
                $body .= PHP_EOL . '$instance = new ' . $proxyName . '(' . implode(', ', $constructorArgumentStringParts) . ');' . PHP_EOL;
            }
        } else {
            if ($dic->isSingleton($classReflection)) {
                $body .= PHP_EOL . '$instance = \\' . $fullClassName . '::getInstance(' . implode(', ', $constructorArgumentStringParts) . ');' . PHP_EOL;
            } else {
                $body .= PHP_EOL . '$instance = new \\' . $fullClassName . '(' . implode(', ', $constructorArgumentStringParts) . ');' . PHP_EOL;
            }

        }

        if ($isSingleton) {
            $body .= 'self::$instance = $instance;' . PHP_EOL;
        }

        $body .= 'return $instance;' . PHP_EOL;

        $instanceMethod->setBody($body);
        $instanceMethod->setStatic(true);
        $factoryClass->setMethod($instanceMethod);

        $methods = $classReflection->getMethods();
        foreach ($methods as $method) {
            if ($method->isPublic() &&
                $method->name !== '__construct' &&
                !$method->isStatic()
            ) {

                $factoryMethod = new Generator\MethodGenerator($dic->getFactoryMethodName($method->name));
                $factoryMethod->setParameter(new Generator\ParameterGenerator('object'));
                $factoryMethod->setStatic(true);

                try {
                    $arguments = $dic->getMethodArguments($classConfig, $method);
                } catch (InjectionException $e) {
                    continue;
                }

                $factoryMethodBody = '';

                $allowedHttpMethod = $dic->getAllowedHttpMethod($method);

                if ($allowedHttpMethod) {
                    $factoryMethodBody .= 'if (isset($_SERVER["request_method"]) && strtolower($_SERVER["request_method"]) !== "'
                        . strtolower($allowedHttpMethod) .'") {' . PHP_EOL;
                    $factoryMethodBody .= '    throw new \RuntimeException("invalid http method " . $_SERVER["REQUEST_METHOD"] . " for '
                        . $method->class . '::' . $method->name . '(), ' . $allowedHttpMethod . ' expected");' . PHP_EOL;
                    $factoryMethodBody .= '}' . PHP_EOL . PHP_EOL;
                }
                $constructorArgumentStringParts = array();

                foreach ($arguments as $argumentName => $argument) {
                    if (is_object($argument)) {
                        $argument = get_class($argument);
                        $argumentFactory = $dic->getFullFactoryClassName($argument);
                        $factoryMethodBody .= '$' . $argumentName . ' = ' . $argumentFactory . '::getInstance();' . PHP_EOL;

                        $this->processFileForClass($argument);
                    } else {
                        $factoryMethodBody .= '$' . $argumentName . ' = \'' . $argument . '\';' . PHP_EOL;
                    }
                    $constructorArgumentStringParts[] = '$' . $argumentName;
                }

                $factoryMethodBody .= PHP_EOL . 'return $object->' . $method->name . '(' . implode(', ', $constructorArgumentStringParts) . ');';
                $factoryMethod->setBody($factoryMethodBody);
                $factoryClass->setMethod($factoryMethod);
            }
        }

        $file = new Generator\FileGenerator();
        $file->setNamespace('rg\injection\generated');
        $file->setClass($factoryClass);
        if ($proxyClass) {
            $file->setClass($proxyClass);
        }
        if ($addFileDocBlock) {
            $docblock = new Generator\DocblockGenerator('Generated by ' . get_class($this) . ' on ' . date('Y-m-d H:i:s'));
            $file->setDocblock($docblock);
        }
        $file->setFilename(__DIR__ . '/generated/' . $factoryName . '.php');

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
    private function getProxyClass($proxyName, $fullClassName, $constructorArguments, $injectableArguments, $realConstructorArgumentStringParts) {
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
        $constructorBody .= 'parent::__construct(' . implode(', ', $realConstructorArgumentStringParts) . ');' . PHP_EOL;
        $constructor->setBody($constructorBody);
        $proxyClass->setMethod($constructor);
        return $proxyClass;
    }

    private function getStaticProxyClass($proxyName, $fullClassName, $constructorArguments, $injectableArguments, $realConstructorArgumentStringParts) {
        $proxyClass = new Generator\ClassGenerator($proxyName);
        $proxyClass->setExtendedClass('\\' . $fullClassName);
        $constructor = new Generator\MethodGenerator('getInstance');
        $constructor->setStatic(true);
        foreach ($constructorArguments as $constructorArgument) {
            $parameter = new Generator\ParameterGenerator($constructorArgument);
            $constructor->setParameter($parameter);
        }
        $constructorBody = '$instance = parent::getInstance(' . implode(', ', $realConstructorArgumentStringParts) . ');' . PHP_EOL;;
        foreach ($injectableArguments as $injectableArgument) {
            $constructorBody .= '$this->' . $injectableArgument . ' = $' . $injectableArgument . ';' . PHP_EOL;
        }
        $constructorBody .= 'return $instance;' . PHP_EOL;
        $constructor->setBody($constructorBody);
        $proxyClass->setMethod($constructor);
        return $proxyClass;
    }

}