<?php

namespace BrugOpen\Core;

use BrugOpen\Db\Service\DatabaseTableManagerFactory;
use BrugOpen\Service\OperationDispatcher;

class DefaultEventListener
{

    /**
     *
     * @var \BrugOpen\Core\Context
     */
    protected $context;

    /**
     *
     * @param \BrugOpen\Core\Context $context
     */
    public function onContextInitialized($context)
    {

        $this->context = $context;

        $eventDispatcher = $context->getEventDispatcher();

        $eventHooks = $this->getEventHooks();

        foreach ($eventHooks as $eventHook) {

            $eventName = $eventHook[0];
            $callback = $eventHook[1];

            $eventDispatcher->addObserver($eventName, $callback);
        }
    }

    public function getEventHooks()
    {

        $eventHooks = array();

        $context = $this->context;

        $operationDispatcher = new OperationDispatcher($context);

        $eventHooks[] = array('ServiceRegistry.initialize', array($this, 'onServiceRegistryInitialize'));
        $eventHooks[] = array('Operation.update', array($operationDispatcher, 'onOperationUpdate'));

        return $eventHooks;
    }

    /**
     *
     * @param ServiceRegistry $serviceRegistry
     */
    public function onServiceRegistryInitialize($serviceRegistry)
    {
        $serviceFactory = new DefaultServiceFactory();

        $serviceRegistry->registerServiceFactory('BrugOpen.BridgePassageCalculator', $serviceFactory);
        $serviceRegistry->registerServiceFactory('BrugOpen.BridgeService', $serviceFactory);
        $serviceRegistry->registerServiceFactory('BrugOpen.JourneyDataStore', $serviceFactory);
        $serviceRegistry->registerServiceFactory('BrugOpen.NdwQueueProcessor', $serviceFactory);
        $serviceRegistry->registerServiceFactory('BrugOpen.OperationIndexService', $serviceFactory);
        $serviceRegistry->registerServiceFactory('BrugOpen.OperationProjectionService', $serviceFactory);
        $serviceRegistry->registerServiceFactory('BrugOpen.PassageService', $serviceFactory);
        $serviceRegistry->registerServiceFactory('BrugOpen.VesselPositionProcessor', $serviceFactory);
        $serviceRegistry->registerServiceFactory('BrugOpen.WaterwayService', $serviceFactory);

        $tableManagerFactory = new DatabaseTableManagerFactory();

        $serviceRegistry->registerServiceFactory('BrugOpen.TableManager', $tableManagerFactory);
    }
}
