<?php
namespace BrugOpen\Core;

interface ServiceFactory
{

    /**
     *
     * @param string $serviceName
     * @param \BrugOpen\Core\Context $context
     * @param
     *            object
     */
    public function createService($serviceName, $context);
}
