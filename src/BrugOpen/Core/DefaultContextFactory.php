<?php
namespace BrugOpen\Core;

class DefaultContextFactory implements ContextFactory
{

    /**
     *
     * @return Context
     */
    public function createContext($appRoot)
    {
        $appRoot = $appRoot;
        $context = new Context($appRoot);

        $eventListener = new DefaultEventListener();
        $context->getEventDispatcher()->addObserver('Context.initialized', array(
            $eventListener,
            'onContextInitialized'
        ));

        $context->initialize();

        return $context;
    }
}
