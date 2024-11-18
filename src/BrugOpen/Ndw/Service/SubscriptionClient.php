<?php

namespace BrugOpen\Ndw\Service;

class SubscriptionClient
{

    /**
     *
     * @var \BrugOpen\Core\Context
     */
    private $context;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     *
     * @param \BrugOpen\Core\Context $context
     */
    public function __construct($context)
    {
        $this->context = $context;
        $this->log = $context->getLogRegistry()->getLog($this);
    }

    public function registerPushSubscription($username, $password)
    {
        $includeDir = $this->context->getAppRoot() . 'include' . DIRECTORY_SEPARATOR;
        $wsdlFile = $includeDir . 'Provider2xNCIS.wsdl';

        $this->log->info('Registering push subscription with NDW');

        $client = new \SoapClient($wsdlFile, array("trace" => 1, "exception" => 1, 'features' => SOAP_WAIT_ONE_WAY_CALLS));

        $params = array('clientIdentification' => $username, 'clientPasskey' => $password);

        $res = $client->register($params);

        return $res;
    }
}
