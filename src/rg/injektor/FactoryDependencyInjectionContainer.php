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

/**
 * @generator ignore
 */
class FactoryDependencyInjectionContainer extends DependencyInjectionContainer {
    public static $prefix = '';

    /**
     * @param string $className
     * @param array $constructorArguments
     * @return object
     * @throws \rg\injektor\InjectionLoopException
     */
    public function getInstanceOfClass($className, array $constructorArguments = array()) {
        $fullFactoryClassName = $this->getFullFactoryClassName($className);
        $factoryClassName = $this->getFactoryClassName($className);

        if ($this->factoryClassExists($fullFactoryClassName, $factoryClassName)) {
            return $fullFactoryClassName::getInstance($constructorArguments);
        }

        return parent::getInstanceOfClass($className, $constructorArguments);
    }

    /**
     * @param string $className
     * @param array $constructorArguments
     * @return object
     */
    protected function createInstanceOfClass($className, array $constructorArguments = array()) {
        $fullFactoryClassName = $this->getFullFactoryClassName($className);
        $factoryClassName = $this->getFactoryClassName($className);
        if ($this->factoryClassExists($fullFactoryClassName, $factoryClassName)) {
            return $fullFactoryClassName::getInstance($constructorArguments);
        }
        return parent::createInstanceOfClass($className, $constructorArguments);
    }

    /**
     * @param object $object
     * @param string $methodName
     * @param array $additionalArguments
     * @return mixed
     * @throws InjectionException
     */
    public function callMethodOnObject($object, $methodName, array $additionalArguments = array()) {
        $className = get_class($object);
        $fullFactoryClassName = $this->getFullFactoryClassName($className);
        $factoryClassName = $this->getFactoryClassName($className);

        if ($this->factoryClassExists($fullFactoryClassName, $factoryClassName)) {
            $factoryMethod = $this->getFactoryMethodName($methodName);
            if (! method_exists($fullFactoryClassName, $factoryMethod)) {
                throw new InjectionException('Method ' . $factoryMethod . ' not found in class ' . $fullFactoryClassName);
            }

            return $fullFactoryClassName::$factoryMethod($object, $additionalArguments);
        }

        return parent::callMethodOnObject($object, $methodName, $additionalArguments);
    }

    /**
     * @param string $methodName
     * @return string
     */
    public function getFactoryMethodName($methodName) {
        return 'call' . ucfirst($methodName);
    }

    /**
     * @param string $fullClassName
     * @return string
     */
    public function getFactoryClassName($fullClassName) {
        return self::$prefix . $this->getStrippedClassName($fullClassName) . 'Factory';
    }

    /**
     * @param string $fullClassName
     * @return string
     */
    public function getProxyClassName($fullClassName) {
        return self::$prefix . $this->getStrippedClassName($fullClassName) . 'Proxy';
    }

    /**
     * @param string $fullClassName
     * @return string
     */
    private function getStrippedClassName($fullClassName) {
        return strtr(trim($fullClassName, '\\'), '\\', '_');
    }

    /**
     * @param $fullClassName
     * @return string
     */
    public function getFullFactoryClassName($fullClassName) {
        $factoryClass = 'rg\injektor\generated\\' . $this->getFactoryClassName($fullClassName);
        return $factoryClass;
    }

    /**
     * @param string $fullFactoryClassName
     * @param string $factoryClassName
     * @return bool
     */
    protected function factoryClassExists($fullFactoryClassName, $factoryClassName) {
        if (class_exists($fullFactoryClassName, false)) {
            return true;
        }

        $fileName = $this->config->getFactoryPath() . DIRECTORY_SEPARATOR . $factoryClassName . '.php';
        if (file_exists($fileName)) {
            require_once $fileName;
            return true;
        }

        return false;
    }

}
