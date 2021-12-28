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
     * @template InstanceType
     *
     * @param class-string<InstanceType> $fullClassName
     * @param array $constructorArguments
     *
     * @return InstanceType
     * @throws InjectionException
     */
    public function getInstanceOfClass($fullClassName, array $constructorArguments = array()) {
        $fullClassName = trim($fullClassName, '\\');
        $classConfig = $this->config->getClassConfig($fullClassName);
        $fullClassName = $this->getRealConfiguredClassName($classConfig, new \ReflectionClass($fullClassName));
        $fullFactoryClassName = $this->getFullFactoryClassName($fullClassName);
        $factoryClassName = $this->getFactoryClassName($fullClassName);

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
