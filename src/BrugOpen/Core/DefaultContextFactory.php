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

        $eventListener = $this->createDefaultEventListener();
        $context->getEventDispatcher()->addObserver('Context.initialized', array(
            $eventListener,
            'onContextInitialized'
        ));

        $context->initialize();

        return $context;
    }

    /**
     * @return DefaultEventListener
     */
    public function createDefaultEventListener()
    {
        $eventListener = new DefaultEventListener();
        return $eventListener;
    }
}
