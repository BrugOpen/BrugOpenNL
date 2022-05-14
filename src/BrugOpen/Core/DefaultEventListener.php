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
    }
}
