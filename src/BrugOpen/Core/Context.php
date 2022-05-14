<?php
namespace BrugOpen\Core;

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
     * @var DataStore
     */
    private $dataStore;

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

    public function getConfig()
    {
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
        return $this->cache;
    }

    /**
     *
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        if ($this->eventDispatcher == null) {

            $eventDispatcher = new EventDispatcher($this);
            $this->eventDispatcher = $eventDispatcher;
        }

        return $this->eventDispatcher;
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
}
