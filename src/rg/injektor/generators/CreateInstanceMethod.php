<?php
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injektor\generators;

class CreateInstanceMethod extends \Laminas\Code\Generator\MethodGenerator {

    public function __construct() {
        parent::__construct('createInstance', [], self::FLAG_PRIVATE | self::FLAG_STATIC);

        $parameter = new \Laminas\Code\Generator\ParameterGenerator('parameters', 'array', array());
        $this->setParameter($parameter);
    }
}
