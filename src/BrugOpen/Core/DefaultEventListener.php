<?php

namespace BrugOpen\Core;

class DefaultEventListener
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @param Context $context
     */
    public function onContextInitialized($context)
    {

        $this->context = $context;

        echo 'context initialized, add default listeners';

    }

}
