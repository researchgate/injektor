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
class Configuration {

    /**
     * @var array
     */
    private $config = array();

    /**
     * @var string
     */
    private $factoryPath;

    /**
     * @param string $configurationFilePath
     * @param string $factoryPath
     */
    public function __construct($configurationFilePath, $factoryPath) {
        $this->addConfigFile($configurationFilePath);
        $this->factoryPath = $factoryPath;
    }

    /**
     * @param string $fullClassName
     * @return array
     */
    public function getClassConfig($fullClassName) {
        if (!isset($this->config[$fullClassName])) {
            return array();
        }

        return $this->config[$fullClassName];
    }

    /**
     * @param string $fullClassName
     * @param array $config
     */
    public function setClassConfig($fullClassName, array $config) {
        $this->config[$fullClassName] = $config;
    }

    /**
     * @param string $configurationFilePath
     */
    public function addConfigFile($configurationFilePath) {
        if ($configurationFilePath && file_exists($configurationFilePath)) {
            $additionalConfiguration = require $configurationFilePath;
            if (!is_array($additionalConfiguration)) {
                return;
            }
            $this->config = array_merge($this->config, $additionalConfiguration);
        }
    }

    /**
     * @param array $config
     */
    public function setConfig($config) {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param string $factoryPath
     */
    public function setFactoryPath($factoryPath) {
        $this->factoryPath = $factoryPath;
    }

    /**
     * @return string
     */
    public function getFactoryPath() {
        return $this->factoryPath;
    }

}