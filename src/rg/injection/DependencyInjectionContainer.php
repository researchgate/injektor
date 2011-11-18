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

class DependencyInjectionContainer {

    /**
     * @var \rg\injection\Configuration
     */
    private $config;

    /**
     * @var array
     */
    private $instances = array();

    /**
     * @var \rg\injection\DependencyInjectionContainer
     */
    private static $instance;

    /**
     * @param \rg\injection\Configuration $config
     */
    public function __construct(Configuration $config) {
        $this->config = $config;

        $this->instances[__CLASS__] = $this;
        $this->config->setClassConfig(__CLASS__, array(
            'singleton' => true
        ));

        self::$instance = $this;
    }

    /**
     * @static
     * @return \rg\injection\DependencyInjectionContainer
     */
    public static function getInstance() {
        if (self::$instance) {
            return self::$instance;
        }

        throw new InjectionException('dependency injection container was not instanciated yet');
    }

    /**
     * @param string $fullClassName
     * @param array $constructorArguments
     * @return object
     */
    public function getInstanceOfClass($fullClassName, array $constructorArguments = array()) {
        $fullClassName = trim($fullClassName, '\\');

        $classConfig = $this->config->getClassConfig($fullClassName);

        $classReflection = new \ReflectionClass($fullClassName);

        $fullClassName = $this->getRealConfiguredClassName($classConfig, $classReflection);

        $classReflection = $this->getClassReflection($fullClassName);

        if ($this->isConfiguredAsSingleton($classConfig, $classReflection) &&
            isset($this->instances[$fullClassName])
        ) {
            return $this->instances[$fullClassName];
        }


        if ($this->isConfiguredAsSingleton($classConfig, $classReflection) &&
            $this->isSingleton($classReflection)
        ) {
            $constructorArguments = $this->getConstructorArguments($classReflection, $classConfig, $constructorArguments, 'getInstance');
            $instance = $classReflection->getMethod('getInstance')->invokeArgs(null, $constructorArguments);
        } else {
            $constructorArguments = $this->getConstructorArguments($classReflection, $classConfig, $constructorArguments);
            $instance = $classReflection->newInstanceArgs($constructorArguments);
        }

        if ($this->isConfiguredAsSingleton($classConfig, $classReflection)) {
            $this->instances[$fullClassName] = $instance;
        }

        $instance = $this->injectProperties($classConfig, $classReflection, $instance);

        return $instance;
    }

    /**
     * @param \ReflectionClass $classReflection
     * @return bool
     */
    public function isSingleton(\ReflectionClass $classReflection) {
        return $classReflection->hasMethod('__construct') &&
            ! $classReflection->getMethod('__construct')->isPublic() &&
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

        return $this->getMethodArguments($classConfig,$methodReflection, $defaultConstructorArguments);
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
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @param object $instance
     * @return object
     * @throws InjectionException
     */
    private function injectProperties($classConfig, $classReflection, $instance) {
        $properties = $this->getInjectableProperties($classReflection);
        foreach ($properties as $property) {
            $this->injectProperty($classConfig, $property, $instance);
        }
        return $instance;
    }

    /**
     * @param \ReflectionClass $classReflection
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
     * @param array $classConfig
     * @param \ReflectionProperty $property
     * @param object $instance
     * @throws InjectionException
     */
    private function injectProperty(array $classConfig, $property, $instance) {
        $fullClassName = $this->getClassFromVarTypeHint($classConfig, $property->getDocComment());
        $propertyInstance = $this->getInstanceOfClass($fullClassName);
        $property->setAccessible(true);
        $property->setValue($instance, $propertyInstance);
        $property->setAccessible(false);
    }

    /**
     * @param array $classConfig
     * @param string $docComment
     * @return string
     * @throws InjectionException
     */
    public function getClassFromVarTypeHint(array $classConfig, $docComment) {
        $namedClass = $this->getNamedClassOfArgument($classConfig, $docComment);
        if ($namedClass) {
            return $namedClass;
        }
        return $this->getClassFromTypeHint($docComment, '@var');
    }

    /**
     * @param string $docComment
     * @param string $tag
     * @return string mixed
     * @throws InjectionException
     */
    private function getClassFromTypeHint($docComment, $tag) {
        $matches = array();
        preg_match('/' . $tag . '\s([a-zA-Z0-9\\\]+)/', $docComment, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }
        throw new InjectionException('Expected tag ' . $tag . ' not found in doc comment.');
    }

    /**
     * @param string $fullClassName
     * @return \ReflectionClass
     * @throws InjectionException
     */
    public function getClassReflection($fullClassName) {
        $classReflection = new \ReflectionClass($fullClassName);

        if ($classReflection->isAbstract()) {
            throw new InjectionException('Can not instanciate abstract class ' . $fullClassName);
        }

        if ($classReflection->isInterface()) {
            throw new InjectionException('Can not instanciate interface ' . $fullClassName);
        }
        return $classReflection;
    }

    /**
     * @param array $classConfig
     * @param \ReflectionClass $classReflection
     * @return string
     */
    private function getRealConfiguredClassName($classConfig, \ReflectionClass $classReflection) {
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
     * @return string
     */
    private function getAnnotatedImplementationClass(\ReflectionClass $classReflection) {
        $docComment = $classReflection->getDocComment();

        $matches = array();

        preg_match('/@implementedBy\s+([a-zA-Z0-9\\\]+)/', $docComment, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        return null;
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
     * @param object $object
     * @param string $methodName
     * @return mixed
     * @throws InjectionException
     */
    public function callMethodOnObject($object, $methodName) {
        $fullClassName = get_class($object);
        if (substr($methodName, 0, 2) === '__') {
            throw new InjectionException('You are not allowed to call magic method ' . $methodName . ' on ' . $fullClassName);
        }
        $classReflection = $this->getClassReflection($fullClassName);

        $methodReflection = $this->getMethodReflection($classReflection, $methodName);

        $this->checkAllowedHttpMethodAnnotation($methodReflection);

        $arguments = $this->getMethodArguments(array(), $methodReflection);

        return $methodReflection->invokeArgs($object, $arguments);
    }

    /**
     * @param \ReflectionMethod $methodReflection
     * @return
     */
    public function checkAllowedHttpMethodAnnotation(\ReflectionMethod $methodReflection) {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            return;
        }

        $allowedHttpMethod = $this->getAllowedHttpMethod($methodReflection);

        if ($allowedHttpMethod && strtolower($allowedHttpMethod) !== strtolower($_SERVER['REQUEST_METHOD'])) {
            throw new \RuntimeException('invalid http method ' . $_SERVER['REQUEST_METHOD'] . ' for ' . $methodReflection->class . '::' . $methodReflection->name . '(), ' . $allowedHttpMethod . ' expected');
        }
    }

    /**
     * @param \ReflectionMethod $methodReflection
     * @return string
     */
    public function getAllowedHttpMethod(\ReflectionMethod $methodReflection) {
        $docComment = $methodReflection->getDocComment();
        $matches = array();
        preg_match('/@method\s+([a-z]+)/i', $docComment, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        }

        return null;
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
     * @param array $classConfig
     * @param \ReflectionMethod $methodReflection
     * @param array $defaultArguments
     * @return array
     */
    public function getMethodArguments(array $classConfig, \ReflectionMethod $methodReflection, array $defaultArguments = array()) {
        $arguments = $methodReflection->getParameters();

        $methodIsMarkedInjectible = $this->isInjectable($methodReflection->getDocComment());

        $argumentValues = array();

        foreach ($arguments as $argument) {
            if (isset($defaultArguments[$argument->name])) {
               $argumentValues[$argument->name] = $this->getValueOfDefaultArgument($defaultArguments[$argument->name]);
            } else if ($methodIsMarkedInjectible) {
                $argumentValues[$argument->name] = $this->getInstanceOfArgument($classConfig, $argument);
            } else if (!$argument->isOptional()) {
                throw new InjectionException('Parameter ' . $argument->name . ' in class ' . $methodReflection->class . ' is not injectable');
            }
        }

        return $argumentValues;
    }

    /**
     * @param array $argumentConfig
     * @return mixed
     */
    private function getValueOfDefaultArgument(array $argumentConfig) {
        if (isset($argumentConfig['value'])) {
            return $argumentConfig['value'];
        }
        if (isset($argumentConfig['class'])) {
            return $this->getInstanceOfClass($argumentConfig['class']);
        }
        return null;
    }

    /**
     * @param array $classConfig
     * @param \ReflectionParameter $argument
     * @return object
     * @throws InjectionException
     */
    private function getInstanceOfArgument($classConfig, \ReflectionParameter $argument) {
        $namedClassName = $this->getNamedClassOfArgument($classConfig, $argument->getDeclaringFunction()->getDocComment(), $argument->name);
        if ($namedClassName) {
            return $this->getInstanceOfClass($namedClassName);
        }

        if (!$argument->getClass()) {
            throw new InjectionException('Invalid argument without class typehint ' . $argument->name);
        }

        return $this->getInstanceOfClass($argument->getClass()->name);
    }

    /**
     * @param array $classConfig
     * @param string $docComment
     * @param string $argumentName
     * @return string
     */
    private function getNamedClassOfArgument(array $classConfig, $docComment, $argumentName = null) {
        $matches = array();
        $pattern = '@named\s+([a-zA-Z0-9\\\]+)';
        if ($argumentName) {
            $pattern .= '\s+\$' . preg_quote($argumentName, '/');
        }
        preg_match('/' . $pattern . '/', $docComment, $matches);
        if (isset($matches[1])) {
            if (! isset($classConfig['named']) || ! isset($classConfig['named'][$matches[1]])) {
                throw new InjectionException('Configuration for name ' . $matches[1] . ' not found.');
            }
            return $classConfig['named'][$matches[1]];
        }
        return null;
    }

    /**
     * @param $docComment
     * @return bool
     */
    public function isInjectable($docComment) {
        return strpos($docComment, '@inject') !== false;
    }

}
