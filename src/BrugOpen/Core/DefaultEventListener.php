<?php
namespace BrugOpen\Core;

class DefaultEventListener
{

    /**
     *
     * @var \BrugOpen\Core\Context
     */
    private $context;

    /**
     *
     * @param \BrugOpen\Core\Context $context
     */
    public function onContextInitialized($context)
    {
        $this->context = $context;

        $context->getEventDispatcher()->addObserver('ServiceRegistry.initialize', array(
            $this,
            'onServiceRegistryInitialize'
        ));
    }

    /**
     *
     * @param ServiceRegistry $serviceRegistry
     */
    public function onServiceRegistryInitialize($serviceRegistry)
    {
        $serviceFactory = new DefaultServiceFactory();

        $serviceRegistry->registerServiceFactory('BrugOpen.NdwQueueProcessor', $serviceFactory);
    }
}
