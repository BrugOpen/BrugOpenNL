<?php
namespace BrugOpen\Core;

use BrugOpen\Db\Service\DatabaseConnectionManager;
use BrugOpen\Service\ConfigLoader;
use BrugOpen\Service\DataStore;

class Context
{

    /**
     *
     * @var string
     */
    private $appRoot;

    /**
     *
     * @var Config
     */
    private $config;

    /**
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     *
     * @var LogRegistry
     */
    private $logRegistry;

    /**
     *
     * @var DataStore
     */
    private $dataStore;

    /**
     *
     * @var DatabaseConnectionManager
     */
    private $databaseConnectionManager;

    /**
     *
     * @var ServiceRegistry
     */
    private $serviceRegistry;

    public function __construct($param = null)
    {
        if (is_string($param)) {
            // assume appRoot
            $this->appRoot = $param;
        }
    }

    /**
     *
     * @param Config|string $param
     */
    public function initialize($param = null)
    {
        if ($param != null) {

            if (is_string($param)) {
                $this->appRoot = $param;
            } elseif (is_object($param)) {
                $this->config = $param;
            }
        }

        // create event dispatcher
        $eventDispatcher = $this->getEventDispatcher();
        $eventDispatcher->postEvent('Context.initialized', array(
            $this
        ));
    }

    /**
     * This method should only be called by RequestDispatcher::dispatch()
     */
    public function shutdown()
    {

        // shutdown initialized services if needed
        $eventDispatcher = $this->getEventDispatcher();
        $eventDispatcher->postEvent('Context.shutdown', array(
            $this
        ));
    }

    /**
     *
     * @return string
     */
    public function getAppRoot()
    {
        return $this->appRoot;
    }

    public function getConfig()
    {
        if (! $this->config) {

            $iniFile = $this->appRoot . 'config.ini';

            $configLoader = new ConfigLoader();
            $this->config = $configLoader->loadConfig($iniFile);
        }

        return $this->config;
    }

    public function getDataStore()
    {
        if (! $this->dataStore) {

            $dataStore = new DataStore();
            $dataStore->initialize($this);

            $this->dataStore = $dataStore;
        }

        return $this->dataStore;
    }

    public function getCache()
    {
        return null;
    }

    /**
     *
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        if ($this->eventDispatcher == null) {

            $eventDispatcher = new EventDispatcher();
            $this->eventDispatcher = $eventDispatcher;
        }

        return $this->eventDispatcher;
    }

    /**
     *
     * @return LogRegistry
     */
    public function getLogRegistry()
    {
        if ($this->logRegistry == null) {

            $logRegistry = new LogRegistry();
            $logRegistry->initialize($this);

            $this->logRegistry = $logRegistry;
        }

        return $this->logRegistry;
    }

    /**
     *
     * @param string $serviceName
     * @return NULL|object
     */
    public function getService($serviceName)
    {
        $service = null;

        if ($serviceName != '') {

            if ($serviceRegistry = $this->getServiceRegistry()) {

                $service = $serviceRegistry->getService($serviceName);
            }
        }

        return $service;
    }

    /**
     *
     * @return ServiceRegistry
     */
    public function getServiceRegistry()
    {
        if ($this->serviceRegistry == null) {

            $serviceRegistry = new ServiceRegistry();
            $serviceRegistry->initialize($this);

            $this->serviceRegistry = $serviceRegistry;
        }

        return $this->serviceRegistry;
    }

    /**
     *
     * @return \BrugOpen\Db\Service\DatabaseConnectionManager
     */
    public function getDatabaseConnectionManager()
    {
        if ($this->databaseConnectionManager == null) {

            $databaseConnectionManager = new DatabaseConnectionManager();
            $databaseConnectionManager->initialize($this);

            $this->databaseConnectionManager = $databaseConnectionManager;
        }

        return $this->databaseConnectionManager;
    }
}
