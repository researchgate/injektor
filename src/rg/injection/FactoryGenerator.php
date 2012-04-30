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

abstract class FactoryGenerator {

    /**
     * @var \rg\injection\Configuration
     */
    private $config;

    /**
     * @var string
     */
    private $factoryPath;

    /**
     * @var array
     */
    private $generated = array();

    /**
     * @param Configuration $config
     * @param string $path
     */
    public function __construct(Configuration $config, $path) {
        $this->config = $config;
        $this->factoryPath = $path;
    }

    /**
     * @abstract
     * @param string $fullClassName
     */
    abstract public function processFileForClass($fullClassName);

    /**
     * @param string $fullClassName
     * @return null|\Zend\Code\Generator\FileGenerator
     */
    protected function generateFileForClass($fullClassName) {
        $fullClassName = trim($fullClassName, '\\');
        if (in_array($fullClassName, $this->generated)) {
            return null;
        }
        $fileGenerator = new FileGenerator($this, $this->config, $this->factoryPath, $fullClassName);
        $this->generated[] = $fullClassName;
        return $fileGenerator->getGeneratedFile();
    }

}