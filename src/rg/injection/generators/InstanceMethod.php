<?php
/*
 * This file is part of rg\injection.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injection\generators;

class InstanceMethod extends \Zend\Code\Generator\MethodGenerator {

    /**
     * @var bool
     */
    private $singletonSupport = false;

    /**
     * @var \rg\injection\generators\FactoryGenerator
     */
    private $factoryGenerator;

    /**
     * @var InjectionParameter[]
     */
    private $instanceParameters = array();

    /**
     * @param \rg\injection\generators\FactoryGenerator $factoryGenerator
     */
    public function __construct(FactoryGenerator $factoryGenerator) {
        parent::__construct('getInstance');

        $this->factoryGenerator = $factoryGenerator;

        $parameter = new \Zend\Code\Generator\ParameterGenerator('parameters', 'array', array());
        $this->setParameter($parameter);
    }

    public function addSingletonSupport() {
        $this->singletonSupport = true;
    }

    public function generate() {
        $this->body = $this->getSingletonBody() . $this->body;

        foreach ($this->instanceParameters as $instanceParameter) {

        }

        return parent::generate();
    }

    public function addInstanceParameter(InjectionParameter $instanceParameter) {
        $this->instanceParameters[] = $instanceParameter;
    }

    public function addBeforeAspect($aspect) {

    }

    public function addAfterAspect($aspect) {

    }

    public function addInterceptAspect($aspect) {

    }

    private function getSingletonBody() {
        if (! $this->singletonSupport) {
            return '';
        }
        $body = '$singletonKey = json_encode($parameters);' . PHP_EOL;
        $body .= 'if (isset(self::$instance[$singletonKey])) {' . PHP_EOL;
        $body .= '    return self::$instance[$singletonKey];' . PHP_EOL;
        $body .= '}' . PHP_EOL . PHP_EOL;

        return $body;
    }
}