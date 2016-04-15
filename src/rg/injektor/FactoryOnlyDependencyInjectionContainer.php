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

class FactoryOnlyDependencyInjectionContainer extends FactoryDependencyInjectionContainer {

    /**
     * @param string $className
     * @param array $constructorArguments
     * @throws InjectionException
     * @return object
     */
    protected function createInstanceOfClass($className, array $constructorArguments = array()) {
        $className = trim($className, '\\');
        $classConfig = $this->config->getClassConfig($className);
        $className = $this->getRealConfiguredClassName($classConfig, new \ReflectionClass($className));
        $fullFactoryClassName = $this->getFullFactoryClassName($className);
        $factoryClassName = $this->getFactoryClassName($className);

        if ($this->factoryClassExists($fullFactoryClassName, $factoryClassName)) {
            return $fullFactoryClassName::getInstance($constructorArguments);
        }

        throw new InjectionException('factory ' . $fullFactoryClassName . ' was not created');
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
        if (substr($className, -5) === 'Proxy') {
            $classReflection = new \ReflectionClass($className);
            if ($classReflection->getParentClass()) {
                $className = $classReflection->getParentClass()->name;
            }
        }
        $fullFactoryClassName = $this->getFullFactoryClassName($className);
        $factoryClassName = $this->getFactoryClassName($className);

        if ($this->factoryClassExists($fullFactoryClassName, $factoryClassName)) {
            $factoryMethod = $this->getFactoryMethodName($methodName);
            if (! method_exists($object, $methodName)) {
                throw new InjectionException('Method ' . $methodName . ' not found in ' . get_class($object));
            }
            return $fullFactoryClassName::$factoryMethod($object, $additionalArguments);
        }

        throw new InjectionException('factory ' . $fullFactoryClassName . ' was not created');
    }


}
