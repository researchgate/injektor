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

class FactoryDependencyInjectionContainer extends DependencyInjectionContainer {

    /**
     * @param string $fullClassName
     * @param array $constructorArguments
     * @return object
     */
    public function getInstanceOfClass($fullClassName, array $constructorArguments = array()) {
        $factoryClass = $this->getFullFactoryClassName($fullClassName);

        if (class_exists($factoryClass, true)) {
            return $factoryClass::getInstance($constructorArguments);
        }

        return parent::getInstanceOfClass($fullClassName, $constructorArguments);
    }

    /**
     * @param object $object
     * @param string $methodName
     * @param array $additionalArguments
     * @return mixed
     * @throws InjectionException
     */
    public function callMethodOnObject($object, $methodName, array $additionalArguments = array()) {
        $factoryClass = $this->getFactoryClassName(get_class($object));

        if (class_exists($factoryClass, true)) {
            $factoryMethod = $this->getFactoryMethodName($methodName);
            return $factoryClass::$factoryMethod($object, $additionalArguments);
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
        return $this->getStrippedClassName($fullClassName) . 'Factory';
    }

    /**
     * @param string $fullClassName
     * @return string
     */
    public function getProxyClassName($fullClassName) {
        return $this->getStrippedClassName($fullClassName) . 'Proxy';
    }

    /**
     * @param string $fullClassName
     * @return string
     */
    private function getStrippedClassName($fullClassName) {
        $strippedName = '';
        $classNameParts = explode('\\', $fullClassName);
        foreach ($classNameParts as $classNamePart) {
            $strippedName .= ucfirst($classNamePart);
        }
        return $strippedName;
    }

    /**
     * @param $fullClassName
     * @return string
     */
    public function getFullFactoryClassName($fullClassName) {
        $factoryClass = 'rg\injection\generated\\' . $this->getFactoryClassName($fullClassName);
        return $factoryClass;
    }

}
