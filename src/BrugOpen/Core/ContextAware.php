<?php
namespace BrugOpen\Core;

interface ContextAware
{

    /**
     * @param Context $context
     */
    public function setContext($context);

}
