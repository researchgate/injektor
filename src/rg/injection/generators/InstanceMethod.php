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
     * @var \rg\injection\generators\FactoryGenerator
     */
    private $factoryGenerator;

    /**
     * @param \rg\injection\generators\FactoryGenerator $factoryGenerator
     */
    public function __construct(FactoryGenerator $factoryGenerator) {
        parent::__construct('getInstance');

        $this->factoryGenerator = $factoryGenerator;

        $parameter = new \Zend\Code\Generator\ParameterGenerator('parameters', 'array', array());
        $this->setParameter($parameter);
    }
}