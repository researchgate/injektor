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

class Configuration {

    /**
     * @var array
     */
    private $config = array();

    /**
     * @param string $configurationFilePath
     */
    public function __construct($configurationFilePath) {
        $this->loadConfigFile($configurationFilePath);
    }

    /**
     * @param string $fullClassName
     * @return array
     */
    public function getClassConfig($fullClassName) {
        if (! isset($this->config[$fullClassName])) {
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
    private function loadConfigFile($configurationFilePath) {
        if ($configurationFilePath && file_exists($configurationFilePath)) {
            $this->config = require $configurationFilePath;
        }
    }
}