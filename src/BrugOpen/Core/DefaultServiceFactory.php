<?php
namespace BrugOpen\Core;

class DefaultServiceFactory implements ServiceFactory
{

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Core\ServiceFactory::createService()
     */
    public function createService($serviceName, $context)
    {
        $service = null;

        if ($serviceName == 'BrugOpen.NdwQueueProcessor') {

            $service = new \BrugOpen\Ndw\Service\NdwQueueProcessor($context);
        }

        return $service;
    }
}
