<?php
namespace rg\injection\annotations;

class Named {

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $className;

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * @param string $className
     */
    public function setClassName($className) {
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName() {
        return $this->className;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getParameters() {
        return $this->parameters;
    }
}