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
     * @var bool
     */
    private $lazyLoading;

    /**
     * @var bool
     */
    private $lazyServices;

    /**
     * @var bool
     */
    private $lazySingletons;

    /**
     * @param string $configurationFilePath
     * @param string $factoryPath
     * @param bool $lazyLoading
     * @param bool $lazyServices
     * @param bool $lazySingletons
     */
    public function __construct($configurationFilePath = null, $factoryPath = '', $lazyLoading = false, $lazyServices = false, $lazySingletons = false) {
        if ($configurationFilePath) {
            $this->addConfigFile($configurationFilePath);
        }
        $this->factoryPath = $factoryPath;
        $this->lazyLoading = $lazyLoading;
        $this->lazyServices = $lazyServices;
        $this->lazySingletons = $lazySingletons;
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

    /**
     * @return bool
     */
    public function isLazyLoading() {
        return $this->lazyLoading;
    }

    /**
     * @return bool
     */
    public function isLazyServices() {
        return $this->lazyServices;
    }

    /**
     * @return bool
     */
    public function isLazySingletons() {
        return $this->lazySingletons;
    }
}
