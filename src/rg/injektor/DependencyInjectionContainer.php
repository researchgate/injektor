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
use ProxyManager\Configuration as ProxyManagerConfiguration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\Proxy\LazyLoadingInterface;
use Psr\Log\LoggerInterface;
use rg\injektor\annotations\Named;

/**
 * @implementedBy rg\injektor\FactoryDependencyInjectionContainer
 * @generator ignore
 */
class DependencyInjectionContainer {

    public static $CLASS = __CLASS__;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var array
     */
    private $instances = [];

    /**
     * @var DependencyInjectionContainer
     */
    private static $defaultInstance;

    /**
     * @var SimpleAnnotationReader
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
     * @var bool
     */
    private $supportsLazyLoading = false;

    /**
     * @var LazyLoadingValueHolderFactory|null
     */
    private $lazyProxyFactory;

    /**
     * used for injection loop detection
     *
     * @var array
     */
    protected $alreadyVisitedClasses = [];

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config = null) {
        $this->config = $config ? : new Configuration();

        if (!self::$defaultInstance) {
            self::$defaultInstance = $this;
        }

        $this->annotationReader = new SimpleAnnotationReader();
        $this->supportsLazyLoading = class_exists('ProxyManager\Configuration');
    }

    /**
     * @static
     * @throws InjectionException
     * @return DependencyInjectionContainer
     */
    public static function getDefaultInstance() {
        if (self::$defaultInstance) {
            return self::$defaultInstance;
        }

        throw new InjectionException('dependency injection container was not instantiated yet');
    }

    /**
     * @param DependencyInjectionContainer $instance
     */
    public static function setDefaultInstance(DependencyInjectionContainer $instance) {
        self::$defaultInstance = $instance;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * @return Configuration
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param string $className
     * @throws InjectionLoopException
     */
    protected function checkForInjectionLoop($className) {
        if ($this->iterationDepth > 1000) {
            throw new InjectionLoopException(
                'Injection loop detected ' . $className . ' ' . $this->iterationDepth . PHP_EOL . print_r(
                    $this->alreadyVisitedClasses,
                    true
                )
            );
        }
        $this->alreadyVisitedClasses[] = $className;
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
            if ($this->iterationDepth > 0) {
                $this->iterationDepth--;
            }
            return $this;
        }

        $this->log('Trying to get instance of [' . $fullClassName . ']');

        $this->checkForInjectionLoop($fullClassName);

        $classConfig = $this->config->getClassConfig($fullClassName);

        if ($configuredInstance = $this->getConfiguredInstance($classConfig)) {
            $this->log('Found configured instance [' . spl_object_hash($configuredInstance) . '] of class [' . get_class($configuredInstance) . ']');
            if ($this->iterationDepth > 0) {
                $this->iterationDepth--;
            }
            return $configuredInstance;
        }

        $classReflection = new \ReflectionClass($fullClassName);

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

        if (($isConfiguredAsSingleton || $isConfiguredAsService) && $this->isSingleton($classReflection)
        ) {
            $constructorArguments = $this->getConstructorArguments($classReflection, $classConfig, $constructorArguments, 'getInstance');
            $instance = $classReflection->getMethod('getInstance')->invokeArgs(null, $constructorArguments);
        } else {
            $constructorArguments = $this->getConstructorArguments($classReflection, $classConfig, $constructorArguments);
            $instanceConstructor = function () use ($classReflection, $constructorArguments, $isConfiguredAsSingleton, $isConfiguredAsService, $singletonKey, $fullClassName) {
                $instance = $classReflection->newInstanceArgs($constructorArguments ? $constructorArguments : []);

                if ($isConfiguredAsSingleton) {
                    $this->log('Added singleton instance [' . spl_object_hash($instance) . '] of class [' . get_class($instance) . '], Singleton Key: [' . $singletonKey . ']');
                    $this->instances[$singletonKey] = $instance;
                }
                if ($isConfiguredAsService) {
                    $this->log('Added service instance [' . spl_object_hash($instance) . '] of class [' .  $fullClassName . ']');
                    $this->instances[$fullClassName] = $instance;
                }

                $instance = $this->injectProperties($classReflection, $instance);

                return $instance;
            };
            $instance = $this->createNewInstance($classConfig, $classReflection, $instanceConstructor);
        }

        if ($isConfiguredAsSingleton) {
            $this->log('Added singleton instance [' . spl_object_hash($instance) . '] of class [' . get_class($instance) . '], Singleton Key: [' . $singletonKey . ']');
            $this->instances[$singletonKey] = $instance;
        }
        if ($isConfiguredAsService) {
            $this->log('Added service instance [' . spl_object_hash($instance) . '] of class [' .  $fullClassName . ']');
            $this->instances[$fullClassName] = $instance;
        }

        $instance = $this->injectProperties($classReflection, $instance);

        $this->log('Created instance [' . spl_object_hash($instance) . '] of class [' . get_class($instance) . ']');

        if ($this->iterationDepth > 0) {
            $this->iterationDepth--;
        }

        return $instance;
    }

    /**
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @param callable $instanceConstructor
     *
     * @return object
     */
    private function createNewInstance(array $classConfig, \ReflectionClass $classReflection, $instanceConstructor) {
        if ($this->supportsLazyLoading && $this->config->isLazyLoading() && $this->isConfiguredAsLazy($classConfig, $classReflection)) {
            return $this->wrapInstanceWithLazyProxy($classReflection->getName(), $instanceConstructor);
        } else {
            return $instanceConstructor();
        }
    }

    /**
     * @param string $className
     * @param callable $instanceConstructor
     *
     * @return \ProxyManager\Proxy\VirtualProxyInterface
     */
    private function wrapInstanceWithLazyProxy($className, $instanceConstructor) {
        $proxyParameters = [

        ];
        return $this->getLazyProxyFactory()->createProxy(
            $className,
            function (&$wrappedObject, LazyLoadingInterface $proxy) use ($instanceConstructor) {
                $proxy->setProxyInitializer(null);
                $wrappedObject = $instanceConstructor();
                return true;
            },
            $proxyParameters
        );
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
        if ($instance instanceof \ProxyManager\Proxy\LazyLoadingInterface) {
            return $instance; // Not inject into lazy proxies
        }

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
        // only process names which are not fully qualified, yet
        // fully qualified names must start with a \
        if ('\\' !== $fullClassName[0]) {
            $parser = new PhpParser();
            $useStatements = $parser->parseClass($property->getDeclaringClass());
            if ($property->getDeclaringClass()->inNamespace()) {
                $parentNamespace = $property->getDeclaringClass()->getNamespaceName();
            }
            $alias = (false === $pos = strpos($fullClassName, '\\')) ? $fullClassName : substr($fullClassName, 0, $pos);

            if (isset($useStatements[$loweredAlias = strtolower($alias)])) {
                if (false !== $pos) {
                    $fullClassName = $useStatements[$loweredAlias] . substr($fullClassName, $pos);
                } else {
                    $fullClassName = $useStatements[$loweredAlias];
                }
            } elseif (isset($parentNamespace)) {
                $fullClassName = $parentNamespace . '\\' . $fullClassName;
            }
        }

        return trim($fullClassName, '\\');
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
            $instanceConstructor = function () use ($namedAnnotation, $classReflection, $additionalArgumentsForProvider) {
                return $this->getRealClassInstanceFromProvider($namedAnnotation->getClassName(), $classReflection->name, array_merge($namedAnnotation->getParameters(), $additionalArgumentsForProvider));
            };

            if ($this->supportsLazyLoading && $this->config->isLazyLoading() && $this->isConfiguredAsLazy($classConfig, $classReflection)) {
                return $this->wrapInstanceWithLazyProxy($classReflection->name, $instanceConstructor);
            } else {
                return $instanceConstructor();
            }
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
            $annotation = new Named();
            $annotation->setClassName($classConfig['namedProviders'][$name]['class']);
            $annotation->setParameters($parameters);
            return $annotation;
        }
        if (isset($classConfig['provider']) && isset($classConfig['provider']['class'])) {
            $parameters = isset($classConfig['provider']['parameters']) ? $classConfig['provider']['parameters'] : array();
            $annotation = new Named();
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
     * @return Named
     */
    private function getImplementedByAnnotation($docComment, $name) {
        return $this->getMatchingAnnotationByNamedPatter($docComment, '@implementedBy', $name);
    }

    /**
     * @param string $docComment
     * @param string $name
     * @return Named
     */
    private function getProvidedByAnnotation($docComment, $name) {
        return $this->getMatchingAnnotationByNamedPatter($docComment, '@providedBy', $name);
    }

    /**
     * @param string $docComment
     * @param string $type
     * @param string $name
     * @return Named
     */
    private function getMatchingAnnotationByNamedPatter($docComment, $type, $name) {
        $matches = array();

        // try for default first
        if (!$name) {
            $defaultMatch = $this->getMatchingAnnotationByNamedPatter($docComment, $type, 'default');
            if ($defaultMatch) {
                return $defaultMatch;
            }
        }

        $pattern = $this->createNamedPattern($type, $name);

        preg_match('/' . $pattern . '/', $docComment, $matches);

        if (isset($matches['className'])) {
            $annotation = new Named();
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
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @return bool
     */
    public function isConfiguredAsLazy(array $classConfig, \ReflectionClass $classReflection) {
        // Force no lazy loading
        if ($this->isConfiguredAsNoLazy($classConfig, $classReflection)) {
            return false;
        }

        // Lazy services
        if ($this->config->isLazyServices() && $this->isConfiguredAsService($classConfig, $classReflection)) {
            return true;
        }

        // Lazy singletons
        if ($this->config->isLazySingletons() && $this->isConfiguredAsSingleton($classConfig, $classReflection)) {
            return true;
        }

        if (isset($classConfig['lazy'])) {
            return (bool) $classConfig['lazy'];
        }

        $classComment = $classReflection->getDocComment();

        return strpos($classComment, '@lazy') !== false;
    }

    /**
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @return bool
     */
    public function isConfiguredAsNoLazy(array $classConfig, \ReflectionClass $classReflection) {
        if (isset($classConfig['noLazy'])) {
            return (bool) $classConfig['noLazy'];
        }

        $classComment = $classReflection->getDocComment();

        return strpos($classComment, '@noLazy') !== false;
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

        $isNumericDefaultArguments = !(bool) count(array_filter(array_keys($defaultArguments), 'is_string'));
        foreach ($arguments as $key => $argument) {
            /** @var \ReflectionParameter $argument */
            if ($isNumericDefaultArguments && array_key_exists($key, $defaultArguments)) {
                $argumentValues[$argument->name] = $this->getValueOfDefaultArgument($defaultArguments[$key]);
            } else if (array_key_exists($argument->name, $defaultArguments)) {
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
     * @return \ProxyManager\Configuration
     */
    private function getLazyProxyFactoryConfiguration() {
        $config = new ProxyManagerConfiguration();
        $config->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        return $config;
    }

    /**
     * @return LazyLoadingValueHolderFactory|null
     */
    private function getLazyProxyFactory() {
        if ($this->supportsLazyLoading && !$this->lazyProxyFactory) {
            $config = $this->getLazyProxyFactoryConfiguration();
            $this->lazyProxyFactory = new LazyLoadingValueHolderFactory($config);
        }

        return $this->lazyProxyFactory;
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

    /**
     * @return bool
     */
    public function supportsLazyLoading()
    {
        return $this->supportsLazyLoading;
    }

    /**
     * This clears all internal caches and memories.
     * 
     * This can be useful in tests where a new injector is created for each test.
     * In order to avoid cyclic memory leaks, it is advised to call this before creating 
     * a new instance of this class.
     */
    public function clear()
    {
        $this->alreadyVisitedClasses = [];
        $this->instances = [];
    }
}
