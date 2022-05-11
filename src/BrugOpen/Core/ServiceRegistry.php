<?php

namespace BrugOpen\Core;

class ServiceRegistry
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var object[]
     */
    private $services;

    /**
     *
     * @var callback[]
     */
    private $serviceFactoryRegistry;

    /**
     *
     * @param Context $context
     */
    public function initialize(Context $context)
    {
        $this->context = $context;

        $context->getEventDispatcher()->postEvent('ServiceRegistry.initialize', array(
            $this
        ));
    }

    /**
     *
     * @param string $serviceName
     * @return object|NULL
     */
    public function getService($serviceName)
    {
        $service = null;

        if ($serviceName != '') {

            if (! is_array($this->services)) {

                $this->services = array();
            }

            if (! array_key_exists($serviceName, $this->services)) {

                if ($serviceFactory = $this->getServiceFactory($serviceName)) {

                    if (is_string($serviceFactory)) {

                        $serviceFactoryClassName = $serviceFactory;

                        if (class_exists($serviceFactoryClassName, true)) {

                            $serviceFactory = new $serviceFactoryClassName();

                        } else {

                            trigger_error('Could not create service factory instance for ' . $serviceFactory, E_USER_NOTICE);

                        }

                    }

                    if (is_object($serviceFactory)) {

                        if ($serviceFactory instanceof ServiceFactory) {

                            $service = $serviceFactory->createService($serviceName, $this->context);

                            $this->services[$serviceName] = $service;
                        }

                    }

                }

            }

            if (array_key_exists($serviceName, $this->services)) {

                $service = $this->services[$serviceName];

            }

        }

        return $service;

    }

    /**
     *
     * @param string $serviceName
     * @return ServiceFactory
     */
    public function getServiceFactory($serviceName)
    {
        $serviceFactory = null;

        if ($serviceName != '') {

            if (is_array($this->serviceFactoryRegistry)) {

                if (array_key_exists($serviceName, $this->serviceFactoryRegistry)) {

                    $serviceFactory = $this->serviceFactoryRegistry[$serviceName];
                }
            }
        }

        return $serviceFactory;
    }

    /**
     *
     * @param string $serviceName
     * @param ServiceFactory $serviceFactory
     */
    public function registerServiceFactory($serviceName, $serviceFactory)
    {
        if ($serviceName != '') {

            if (is_object($serviceFactory)) {

                if ($serviceFactory instanceof ServiceFactory) {

                    $this->serviceFactoryRegistry[$serviceName] = $serviceFactory;
                }
            } else if (is_string($serviceFactory)) {

                $this->serviceFactoryRegistry[$serviceName] = $serviceFactory;
            }
        }
    }

    /**
     *
     * @param string $serviceName
     * @param object $service
     */
    public function registerService($serviceName, $service)
    {
        $this->services[$serviceName] = $service;
    }
}
