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

use Doctrine\Common\Annotations\PhpParser;
use Psr\Log\LoggerInterface;

/**
 * @implementedBy rg\injektor\FactoryDependencyInjectionContainer
 * @generator ignore
 */
class DependencyInjectionContainer {

    public static $CLASS = __CLASS__;

    /**
     * @var \rg\injektor\Configuration
     */
    protected $config;

    /**
     * @var array
     */
    private $instances = array();

    /**
     * @var \rg\injektor\DependencyInjectionContainer
     */
    private static $defaultInstance;

    /**
     * @var \rg\injektor\SimpleAnnotationReader
     */
    private $annotationReader;

    /**
     * iteration depth used for intelligent logging, makes it easier to detect circular iterations
     *
     * @var int
     */
    private $iterationDepth = 0;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \rg\injektor\Configuration $config
     */
    public function __construct(Configuration $config = null) {
        $this->config = $config ? : new Configuration();

        if (!self::$defaultInstance) {
            self::$defaultInstance = $this;
        }

        $this->annotationReader = new SimpleAnnotationReader();
    }

    /**
     * @static
     * @throws InjectionException
     * @return \rg\injektor\DependencyInjectionContainer
     */
    public static function getDefaultInstance() {
        if (self::$defaultInstance) {
            return self::$defaultInstance;
        }

        throw new InjectionException('dependency injection container was not instantiated yet');
    }

    /**
     * @param \rg\injektor\DependencyInjectionContainer $instance
     */
    public static function setDefaultInstance(\rg\injektor\DependencyInjectionContainer $instance) {
        self::$defaultInstance = $instance;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * @return \rg\injektor\Configuration
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param string $fullClassName
     * @param array $constructorArguments
     * @return object
     */
    public function getInstanceOfClass($fullClassName, array $constructorArguments = array()) {
        $this->iterationDepth++;

        $fullClassName = trim($fullClassName, '\\');

        if ($fullClassName === __CLASS__) {
            return $this;
        }

        $this->log('Trying to get instance of [' . $fullClassName . ']');

        $classConfig = $this->config->getClassConfig($fullClassName);

        $classReflection = new \ReflectionClass($fullClassName);

        if ($configuredInstance = $this->getConfiguredInstance($classConfig)) {
            $this->log('Found configured instance [' . spl_object_hash($configuredInstance) . '] of class [' . get_class($configuredInstance) . ']');
            if ($this->iterationDepth > 0) {
                $this->iterationDepth--;
            }
            return $configuredInstance;
        }

        if ($providedClass = $this->getProvidedConfiguredClass($classConfig, $classReflection)) {
            $this->log('Got provided instance [' . spl_object_hash($providedClass) . '] of class [' . get_class($providedClass) . ']');
            if ($this->iterationDepth > 0) {
                $this->iterationDepth--;
            }
            return $providedClass;
        }

        $fullClassName = $this->getRealConfiguredClassName($classConfig, $classReflection);

        $classReflection = $this->getClassReflection($fullClassName);

        $singletonKey = null;
        $isConfiguredAsSingleton = $this->isConfiguredAsSingleton($classConfig, $classReflection);
        if ($isConfiguredAsSingleton) {
            $singletonKey = $this->getSingletonKey($fullClassName, $constructorArguments);

            if (isset($this->instances[$singletonKey])) {
                $this->log('Found singleton instance [' . spl_object_hash($this->instances[$singletonKey]) . '] of class [' . get_class($this->instances[$singletonKey]) . '], Singleton Key: [' . $singletonKey . ']');
                if ($this->iterationDepth > 0) {
                    $this->iterationDepth--;
                }
                return $this->instances[$singletonKey];
            }
        }

        $isConfiguredAsService = $this->isConfiguredAsService($classConfig, $classReflection);
        if ($isConfiguredAsService && isset($this->instances[$fullClassName])) {
            $this->log('Found service instance of class [' . $fullClassName . ']');
            if ($this->iterationDepth > 0) {
                $this->iterationDepth--;
            }
            return $this->instances[$fullClassName];
        }

        $methodReflection = null;

        if (($isConfiguredAsSingleton || $isConfiguredAsService) && $this->isSingleton($classReflection)
        ) {
            $constructorArguments = $this->getConstructorArguments($classReflection, $classConfig, $constructorArguments, 'getInstance');
            $instance = $classReflection->getMethod('getInstance')->invokeArgs(null, $constructorArguments);
        } else {
            $constructorArguments = $this->getConstructorArguments($classReflection, $classConfig, $constructorArguments);
            if ($constructorArguments) {
                $instance = $classReflection->newInstanceArgs($constructorArguments);
            } else {
                $instance = $classReflection->newInstanceArgs();
            }
        }

        $this->log('Created instance [' . spl_object_hash($instance) . '] of class [' . get_class($instance) . ']');

        if ($isConfiguredAsSingleton) {
            $this->log('Added singleton instance [' . spl_object_hash($instance) . '] of class [' . get_class($instance) . '], Singleton Key: [' . $singletonKey . ']');
            $this->instances[$singletonKey] = $instance;
        }
        if ($isConfiguredAsService) {
            $this->log('Added service instance [' . spl_object_hash($instance) . '] of class [' .  $fullClassName . ']');
            $this->instances[$fullClassName] = $instance;
        }

        $instance = $this->injectProperties($classReflection, $instance);

        if ($this->iterationDepth > 0) {
            $this->iterationDepth--;
        }

        return $instance;
    }

    /**
     * @param string $fullClassName
     * @param array $constructorArguments
     * @return string
     */
    private function getSingletonKey($fullClassName, $constructorArguments) {
        return $fullClassName . serialize($constructorArguments) . '#' . getmypid();
    }

    /**
     * @param array $classConfig
     * @return object
     */
    protected function getConfiguredInstance($classConfig) {
        if (isset($classConfig['instance'])) {
            return $classConfig['instance'];
        }

        return null;
    }

    /**
     * @param \ReflectionClass $classReflection
     * @return bool
     */
    public function isSingleton(\ReflectionClass $classReflection) {
        return $classReflection->hasMethod('__construct') &&
            !$classReflection->getMethod('__construct')->isPublic() &&
            $classReflection->hasMethod('getInstance') &&
            $classReflection->getMethod('getInstance')->isStatic() &&
            $classReflection->getMethod('getInstance')->isPublic();
    }

    /**
     * @param \ReflectionClass $classReflection
     * @param array $classConfig
     * @param array $defaultConstructorArguments
     * @param string $constructorMethod
     * @return array
     */
    public function getConstructorArguments($classReflection, $classConfig, array $defaultConstructorArguments = array(), $constructorMethod = '__construct') {
        $methodReflection = $this->getMethodReflection($classReflection, $constructorMethod);

        if (!$methodReflection) {
            return array();
        }

        $defaultConstructorArguments = $this->getDefaultArguments($classConfig, $defaultConstructorArguments);

        return $this->getMethodArguments($methodReflection, $defaultConstructorArguments);
    }

    /**
     * @param $classConfig
     * @param $defaultConstructorArguments
     * @return array
     */
    private function getDefaultArguments($classConfig, $defaultConstructorArguments) {
        if (isset($classConfig['params']) && is_array($classConfig['params'])) {
            return array_merge($defaultConstructorArguments, $classConfig['params']);
        }
        return $defaultConstructorArguments;
    }

    /**
     * @param \ReflectionClass $classReflection
     * @param object $instance
     * @return object
     * @throws InjectionException
     */
    private function injectProperties($classReflection, $instance) {
        $properties = $this->getInjectableProperties($classReflection);
        foreach ($properties as $property) {
            $this->injectProperty($property, $instance);
        }
        return $instance;
    }

    /**
     * @param \ReflectionClass $classReflection
     * @throws InjectionException
     * @return array
     */
    public function getInjectableProperties($classReflection) {
        $properties = $classReflection->getProperties();

        $injectableProperties = array();

        foreach ($properties as $property) {
            if ($this->isInjectable($property->getDocComment())) {
                if ($property->isPrivate()) {
                    throw new InjectionException('Property ' . $property->name . ' must not be private for property injection.');
                }
                $injectableProperties[] = $property;
            }
        }

        return $injectableProperties;
    }

    /**
     * @param \ReflectionProperty $property
     * @param object $instance
     * @throws InjectionException
     */
    private function injectProperty($property, $instance) {
        $fullClassName = $this->getClassFromVarTypeHint($property->getDocComment());
        if (!$fullClassName) {
            throw new InjectionException('Expected tag @var not found in doc comment.');
        }

        $fullClassName = $this->getFullClassNameBecauseOfImports($property, $fullClassName);

        $arguments = $this->getParamsFromPropertyTypeHint($property);

        $argumentClassConfig = $this->config->getClassConfig($fullClassName);
        $propertyInstance = $this->getNamedProvidedInstance($fullClassName, $argumentClassConfig, $property->getDocComment(), null, $arguments);
        if (!$propertyInstance) {
            $namedClass = $this->getNamedClassOfArgument($fullClassName, $property->getDocComment());
            if ($namedClass) {
                $fullClassName = $namedClass;
            }
            $propertyInstance = $this->getInstanceOfClass($fullClassName, is_array($arguments) ? $arguments : array());
        }
        $property->setAccessible(true);
        $property->setValue($instance, $propertyInstance);
        $property->setAccessible(false);
    }

    /**
     * @param \ReflectionProperty $property
     * @param string $fullClassName
     * @return string
     */
    public function getFullClassNameBecauseOfImports($property, $fullClassName) {
        if (!class_exists($fullClassName) || !interface_exists($fullClassName)) {
            $parser = new PhpParser();
            $useStatements = $parser->parseClass($property->getDeclaringClass());
            if ($property->getDeclaringClass()->inNamespace()) {
                $useStatements['__NAMESPACE__'] = $property->getDeclaringClass()->getNamespaceName();
            }
            // only process names which are not fully qualified, yet
            // fully qualified names must start with a \
            if ('\\' !== $fullClassName[0]) {
                $alias = (false === $pos = strpos($fullClassName, '\\')) ? $fullClassName : substr($fullClassName, 0, $pos);

                if (isset($useStatements[$loweredAlias = strtolower($alias)])) {
                    if (false !== $pos) {
                        $fullClassName = $useStatements[$loweredAlias] . substr($fullClassName, $pos);
                    } else {
                        $fullClassName = $useStatements[$loweredAlias];
                    }
                } elseif (isset($useStatements['__NAMESPACE__']) && class_exists($useStatements['__NAMESPACE__'] . '\\' . $fullClassName)) {
                    $fullClassName = $useStatements['__NAMESPACE__'] . '\\' . $fullClassName;
                }
            }
        }
        return $fullClassName;
    }

    /**
     * @param string $docComment
     * @return string
     * @throws InjectionException
     */
    public function getClassFromVarTypeHint($docComment) {
        return $this->annotationReader->getClassFromVarTypeHint($docComment);
    }

    /**
     * @param string $fullClassName
     * @return \ReflectionClass
     * @throws InjectionException
     */
    public function getClassReflection($fullClassName) {
        $classReflection = new \ReflectionClass($fullClassName);

        if ($classReflection->isAbstract()) {
            throw new InjectionException('Can not instantiate abstract class ' . $fullClassName);
        }

        if ($classReflection->isInterface()) {
            throw new InjectionException('Can not instantiate interface ' . $fullClassName);
        }
        return $classReflection;
    }

    /**
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @param string $name
     * @param array $additionalArgumentsForProvider
     * @return null|object
     */
    public function getProvidedConfiguredClass($classConfig, \ReflectionClass $classReflection, $name = null, $additionalArgumentsForProvider = array()) {
        if ($namedAnnotation = $this->getProviderClassName($classConfig, $classReflection, $name)) {
            return $this->getRealClassInstanceFromProvider($namedAnnotation->getClassName(), $classReflection->name, array_merge($namedAnnotation->getParameters(), $additionalArgumentsForProvider));
        }

        return null;
    }

    /**
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @param string $name
     * @return annotations\Named
     */
    public function getProviderClassName($classConfig, $classReflection, $name) {
        if ($name && isset($classConfig['namedProviders']) && isset($classConfig['namedProviders'][$name])
            && isset($classConfig['namedProviders'][$name]['class'])
        ) {
            $parameters = isset($classConfig['namedProviders'][$name]['parameters']) ? $classConfig['namedProviders'][$name]['parameters'] : array();
            $annotation = new \rg\injektor\annotations\Named();
            $annotation->setClassName($classConfig['namedProviders'][$name]['class']);
            $annotation->setParameters($parameters);
            return $annotation;
        }
        if (isset($classConfig['provider']) && isset($classConfig['provider']['class'])) {
            $parameters = isset($classConfig['provider']['parameters']) ? $classConfig['provider']['parameters'] : array();
            $annotation = new \rg\injektor\annotations\Named();
            $annotation->setClassName($classConfig['provider']['class']);
            $annotation->setParameters($parameters);
            return $annotation;
        }

        return $this->getProvidedByAnnotation($classReflection->getDocComment(), $name);
    }

    /**
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @return string
     */
    public function getRealConfiguredClassName($classConfig, \ReflectionClass $classReflection) {
        if (isset($classConfig['class'])) {
            return $classConfig['class'];
        }

        $annotatedClassName = $this->getAnnotatedImplementationClass($classReflection);
        if ($annotatedClassName) {
            return $annotatedClassName;
        }

        return $classReflection->name;
    }

    /**
     * @param \ReflectionClass $classReflection
     * @param null $name
     * @return string
     */
    private function getAnnotatedImplementationClass(\ReflectionClass $classReflection, $name = null) {
        $docComment = $classReflection->getDocComment();

        if ($namedAnnotation = $this->getImplementedByAnnotation($docComment, $name)) {
            return $namedAnnotation->getClassName();
        }

        return null;
    }

    /**
     * @param string $providerClassName
     * @param string $originalName
     * @param array $parameters
     * @return object
     * @throws InjectionException
     */
    private function getRealClassInstanceFromProvider($providerClassName, $originalName, array $parameters = array()) {
        /** @var Provider $provider  */
        $provider = $this->getInstanceOfClass($providerClassName, $parameters);

        if (!$provider instanceof Provider) {
            throw new InjectionException('Provider class ' . $providerClassName . ' specified in ' . $originalName . ' does not implement rg\injektor\provider');
        }

        return $provider->get();
    }

    /**
     * @param string $docComment
     * @param string $name
     * @return \rg\injektor\annotations\Named
     */
    private function getImplementedByAnnotation($docComment, $name) {
        return $this->getMatchingAnnotationByNamedPatter($docComment, '@implementedBy', $name);
    }

    /**
     * @param string $docComment
     * @param string $name
     * @return \rg\injektor\annotations\Named
     */
    private function getProvidedByAnnotation($docComment, $name) {
        return $this->getMatchingAnnotationByNamedPatter($docComment, '@providedBy', $name);
    }

    /**
     * @param string $docComment
     * @param string $type
     * @param string $name
     * @return \rg\injektor\annotations\Named
     */
    private function getMatchingAnnotationByNamedPatter($docComment, $type, $name) {
        $matches = array();

        $pattern = $this->createNamedPattern($type, $name);

        preg_match('/' . $pattern . '/', $docComment, $matches);

        if (isset($matches['className'])) {
            $annotation = new \rg\injektor\annotations\Named();
            $annotation->setName($name);
            $annotation->setClassName($matches['className']);
            if (isset($matches['parameters'])) {
                $parameters = json_decode($matches['parameters'], true);
                if ($parameters) {
                    $annotation->setParameters($parameters);
                }
            }
            return $annotation;
        }

        return null;
    }

    private function createNamedPattern($type, $name) {
        $pattern = $type;
        if ($name) {
            $pattern .= '\s+' . preg_quote($name, '/');
        } else {
            $pattern .= '(\s+default)?';
        }
        $pattern .= '\s+(?P<className>[a-zA-Z0-9\\\]+)';
        $pattern .= '(\s+(?P<parameters>{[\s\:\'\",a-zA-Z0-9\\\]+}))?';

        return $pattern;
    }

    /**
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @return bool
     */
    public function isConfiguredAsSingleton(array $classConfig, \ReflectionClass $classReflection) {
        if (isset($classConfig['singleton'])) {
            return (bool) $classConfig['singleton'];
        }

        $classComment = $classReflection->getDocComment();

        return strpos($classComment, '@singleton') !== false;
    }

    /**
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @return bool
     */
    public function isConfiguredAsService(array $classConfig, \ReflectionClass $classReflection) {
        if (isset($classConfig['service'])) {
            return (bool) $classConfig['service'];
        }

        $classComment = $classReflection->getDocComment();

        return strpos($classComment, '@service') !== false;
    }

    /**
     * @param object $object
     * @param string $methodName
     * @param array $additionalArguments
     * @return mixed
     * @throws InjectionException
     */
    public function callMethodOnObject($object, $methodName, array $additionalArguments = array()) {
        $fullClassName = get_class($object);

        if (substr($methodName, 0, 2) === '__') {
            throw new InjectionException('You are not allowed to call magic method ' . $methodName . ' on ' . $fullClassName);
        }
        $classReflection = $this->getClassReflection($fullClassName);

        $methodReflection = $this->getMethodReflection($classReflection, $methodName);

        $arguments = $this->getMethodArguments($methodReflection, $additionalArguments);

        return $methodReflection->invokeArgs($object, $arguments);
    }

    /**
     * @param \ReflectionClass $classReflection
     * @param string $methodName
     * @return null|\ReflectionMethod
     * @throws InjectionException
     */
    private function getMethodReflection(\ReflectionClass $classReflection, $methodName) {
        if (!$classReflection->hasMethod($methodName)) {
            if ($methodName === '__construct') {
                return null;
            }

            throw new InjectionException('Method ' . $methodName . ' not found in ' . $classReflection->name);
        }

        return $classReflection->getMethod($methodName);
    }

    /**
     * @param \ReflectionMethod $methodReflection
     * @param array $defaultArguments
     * @throws InjectionException
     * @return array
     */
    public function getMethodArguments(\ReflectionMethod $methodReflection, array $defaultArguments = array()) {
        $arguments = $methodReflection->getParameters();

        $methodIsMarkedInjectible = $this->isInjectable($methodReflection->getDocComment());

        $argumentValues = array();

        foreach ($arguments as $argument) {
            /** @var \ReflectionParameter $argument */
            if (array_key_exists($argument->name, $defaultArguments)) {
                $argumentValues[$argument->name] = $this->getValueOfDefaultArgument($defaultArguments[$argument->name]);
            } else if ($methodIsMarkedInjectible) {
                $argumentValues[$argument->name] = $this->getInstanceOfArgument($argument);
            } else if ($argument->isOptional()) {
                $argumentValues[$argument->name] = $argument->getDefaultValue();
            } else if (!$argument->isOptional()) {
                throw new InjectionException('Parameter ' . $argument->name . ' in class ' . $methodReflection->class . ' is not injectable. Given fixed parameters: [' . implode("], [", array_keys($defaultArguments)) . ']');
            }
        }

        return $argumentValues;
    }

    /**
     * @param array $argumentConfig
     * @return mixed
     */
    private function getValueOfDefaultArgument($argumentConfig) {
        if (!is_array($argumentConfig)) {
            return $argumentConfig;
        }
        if (isset($argumentConfig['value'])) {
            return $argumentConfig['value'];
        }
        if (isset($argumentConfig['class'])) {
            return $this->getInstanceOfClass($argumentConfig['class']);
        }
        return $argumentConfig;
    }

    /**
     * @param \ReflectionParameter $argument
     * @return object
     * @throws InjectionException
     */
    private function getInstanceOfArgument(\ReflectionParameter $argument) {
        if (!$argument->getClass()) {
            if ($argument->isOptional()) {
                return $argument->getDefaultValue();
            }
            throw new InjectionException('Invalid argument without class typehint class: [' . $argument->getDeclaringClass()->name . '] method: [' . $argument->getDeclaringFunction()->name . '] argument: [' . $argument->name . ']');
        }

        $argumentClassConfig = $this->config->getClassConfig($argument->getClass()->name);

        $arguments = $this->getParamsFromTypeHint($argument);

        $providedInstance = $this->getNamedProvidedInstance($argument->getClass()->name, $argumentClassConfig, $argument->getDeclaringFunction()->getDocComment(), $argument->name, $arguments);
        if ($providedInstance) {
            return $providedInstance;
        }

        $namedClassName = $this->getNamedClassOfArgument($argument->getClass()->name, $argument->getDeclaringFunction()->getDocComment(), $argument->name);

        if ($namedClassName) {
            return $this->getInstanceOfClass($namedClassName, $arguments);
        }

        return $this->getInstanceOfClass($argument->getClass()->name, $arguments);
    }

    /**
     * @param \ReflectionParameter $argument
     * @return array
     */
    public function getParamsFromTypeHint(\ReflectionParameter $argument) {
        return $this->annotationReader->getParamsFromTypeHint($argument->getDeclaringFunction()->getDocComment(), $argument->name, 'param');
    }

    /**
     * @param \ReflectionProperty $property
     * @return array
     */
    public function getParamsFromPropertyTypeHint(\ReflectionProperty $property) {
        return $this->annotationReader->getParamsFromTypeHint($property->getDocComment(), $property->name, 'var');
    }

    /**
     * @param string $argumentClass
     * @param array $classConfig
     * @param string$docComment
     * @param string $argumentName
     * @param array $additionalArgumentsForProvider
     * @return null|object
     */
    public function getNamedProvidedInstance($argumentClass, array $classConfig, $docComment, $argumentName = null, $additionalArgumentsForProvider = array()) {
        $implementationName = $this->getImplementationName($docComment, $argumentName);

        return $this->getProvidedConfiguredClass($classConfig, new \ReflectionClass($argumentClass), $implementationName, $additionalArgumentsForProvider);
    }

    /**
     * @param string $argumentClass
     * @param string $docComment
     * @param string $argumentName
     * @return string
     */
    public function getNamedClassOfArgument($argumentClass, $docComment, $argumentName = null) {
        $argumentClassConfig = $this->config->getClassConfig($argumentClass);

        $implementationName = $this->getImplementationName($docComment, $argumentName);

        if ($implementationName) {
            return $this->getImplementingClassBecauseOfName($argumentClass, $argumentClassConfig, $implementationName);
        }
        return null;
    }

    /**
     * @param string $docComment
     * @param string $argumentName
     * @return string
     */
    public function getImplementationName($docComment, $argumentName) {
        $matches = array();
        $pattern = '@named\s+([a-zA-Z0-9\\\]+)';
        if ($argumentName) {
            $pattern .= '\s+\$' . preg_quote($argumentName, '/');
        }
        preg_match('/' . $pattern . '/', $docComment, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param string $argumentClass
     * @param array $classConfig
     * @param string $name
     * @return string
     * @throws InjectionException
     */
    private function getImplementingClassBecauseOfName($argumentClass, $classConfig, $name) {
        if (!isset($classConfig['named']) || !isset($classConfig['named'][$name])) {
            $classReflection = new \ReflectionClass($argumentClass);
            $annotatedConfigurationClassName = $this->getAnnotatedImplementationClass($classReflection, $name);
            if ($annotatedConfigurationClassName) {
                return $annotatedConfigurationClassName;
            }

            throw new InjectionException('Named Configuration [' . $name . '] for class [' . $argumentClass . '] not found. Given ClassConfig: [' . var_export($classConfig, true) . '].');
        }
        return $classConfig['named'][$name];
    }

    /**
     * @param $docComment
     * @return bool
     */
    public function isInjectable($docComment) {
        return strpos($docComment, '@inject') !== false;
    }

    /**
     * @param string $string
     */
    protected function log($string) {
        if ($this->logger) {
            $this->logger->debug(sprintf("%02d", $this->iterationDepth) . str_repeat('-', $this->iterationDepth * 2) . '> ' . $string);
        }
    }

}
