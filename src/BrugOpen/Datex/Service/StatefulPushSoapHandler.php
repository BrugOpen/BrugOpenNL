<?php

namespace BrugOpen\Datex\Service;

use BrugOpen\Core\Context;
use BrugOpen\Datex\Model\ExchangeInformation;
use BrugOpen\Ndw\Service\PushSessionStateService;

class StatefulPushSoapHandler
{
    /**
     * @var Context
     */
    private $context;

    /**
     *
     * @var PushSessionStateService
     */
    private $pushSessionStateService;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @param Context $context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }

    /**
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog()
    {
        if ($this->log == null) {

            $context = $this->context;
            if ($context != null) {

                $this->log = $context->getLogRegistry()->getLog($this);
            }
        }

        return $this->log;
    }

    /**
     *
     * @return PushSessionStateService
     */
    public function getPushSessionStateService()
    {
        if ($this->pushSessionStateService == null) {
            $this->pushSessionStateService = new PushSessionStateService($this->context);
        }

        return $this->pushSessionStateService;
    }

    /**
     *
     * @param \Psr\Log\LoggerInterface $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    /**
     * @param ExchangeInformation $exchangeInformation
     * @return object
     */
    public function openSession($openSessionInput)
    {

        $sessionId = $this->createSessionID();

        $logger = $this->getLog();
        if ($logger != null) {
            $logger->info('openSession called, created session ' . $sessionId);
        }

        $output = [];

        $output['modelBaseVersion'] = '3';

        $exchangeContext = [];
        $exchangeContext['codedExchangeProtocol'] = 'statefulPush';
        $exchangeContext['exchangeSpecificationVersion'] = '2020';
        $exchangeContext['supplierOrCisRequester'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['country'] = 'NL';
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['nationalIdentifier'] = 'NLNDW';

        $output['exchangeContext'] = $exchangeContext;

        $dynamicInformation = [];
        $dynamicInformation['exchangeStatus'] = 'openingSession';

        $dynamicInformation['messageGenerationTimestamp'] = (new \DateTime())->format('c');

        $dynamicInformation['returnInformation'] = [];
        $dynamicInformation['returnInformation']['returnStatus'] = 'snapshotSynchronisationRequest';

        $dynamicInformation['sessionInformation'] = [];
        $dynamicInformation['sessionInformation']['sessionID'] = $sessionId;

        $output['dynamicInformation'] = $dynamicInformation;

        // update session state
        $pushSessionStateService = $this->getPushSessionStateService();
        $pushSessionStateService->storeSessionState($sessionId, 'openingSession');

        return $output;
    }

    public function putSnapshotData($snapshotDataInput)
    {
        $logger = $this->getLog();

        $sessionId = $this->getSessionIDFromInput($snapshotDataInput);

        if ($logger != null) {
            if ($sessionId != null) {
                $logger->info('putSnapshotData called for session ' . $sessionId);
            } else {
                $logger->info('putSnapshotData called with no session ID');
            }
        }

        if (!$sessionId) {
            $sessionId = $this->getCurrentSessionId();
        }

        if (!$sessionId) {
            $sessionId = $this->createSessionID();
        }

        $this->storeCurrentSessionState($sessionId, 'online');

        $output = [];

        $output['modelBaseVersion'] = '3';

        $exchangeContext = [];
        $exchangeContext['codedExchangeProtocol'] = 'statefulPush';
        $exchangeContext['exchangeSpecificationVersion'] = '2020';
        $exchangeContext['supplierOrCisRequester'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['country'] = 'NL';
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['nationalIdentifier'] = 'NLNDW';

        $output['exchangeContext'] = $exchangeContext;

        $dynamicInformation = [];
        $dynamicInformation['exchangeStatus'] = 'online';

        $dynamicInformation['messageGenerationTimestamp'] = (new \DateTime())->format('c');

        $dynamicInformation['returnInformation'] = [];
        $dynamicInformation['returnInformation']['returnStatus'] = 'ack';

        // if ($openSessionInput)

        $dynamicInformation['sessionInformation'] = [];
        $dynamicInformation['sessionInformation']['sessionID'] = $sessionId;

        $output['dynamicInformation'] = $dynamicInformation;

        return $output;
    }

    public function putData($putDataInput)
    {
        $logger = $this->getLog();

        $sessionId = $this->getSessionIDFromInput($putDataInput);

        if ($logger != null) {
            if ($sessionId != null) {
                $logger->info('putData called for session ' . $sessionId);
            } else {
                $logger->info('putData called with no session ID');
            }
        }

        if (!$sessionId) {
            $sessionId = $this->getCurrentSessionId();
        }

        if (!$sessionId) {
            $sessionId = $this->createSessionID();
        }

        $this->storeCurrentSessionState($sessionId, 'online');

        $output = [];

        $output['modelBaseVersion'] = '3';

        $exchangeContext = [];
        $exchangeContext['codedExchangeProtocol'] = 'statefulPush';
        $exchangeContext['exchangeSpecificationVersion'] = '2020';
        $exchangeContext['supplierOrCisRequester'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['country'] = 'NL';
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['nationalIdentifier'] = 'NLNDW';

        $output['exchangeContext'] = $exchangeContext;

        $dynamicInformation = [];
        $dynamicInformation['exchangeStatus'] = 'online';

        $dynamicInformation['messageGenerationTimestamp'] = (new \DateTime())->format('c');

        $dynamicInformation['returnInformation'] = [];
        $dynamicInformation['returnInformation']['returnStatus'] = 'ack';

        // if ($openSessionInput)

        $dynamicInformation['sessionInformation'] = [];
        $dynamicInformation['sessionInformation']['sessionID'] = $sessionId;

        $output['dynamicInformation'] = $dynamicInformation;

        return $output;
    }

    public function closeSession($putDataInput)
    {
        $logger = $this->getLog();

        $sessionId = $this->getSessionIDFromInput($putDataInput);

        if ($logger != null) {
            if ($sessionId != null) {
                $logger->info('closeSession called for session ' . $sessionId);
            } else {
                $logger->info('closeSession called with no session ID');
            }
        }

        $this->storeCurrentSessionState($sessionId, 'offline');

        $output = [];

        $output['modelBaseVersion'] = '3';

        $exchangeContext = [];
        $exchangeContext['codedExchangeProtocol'] = 'statefulPush';
        $exchangeContext['exchangeSpecificationVersion'] = '2020';
        $exchangeContext['supplierOrCisRequester'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['country'] = 'NL';
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['nationalIdentifier'] = 'NLNDW';

        $output['exchangeContext'] = $exchangeContext;

        $dynamicInformation = [];
        $dynamicInformation['exchangeStatus'] = 'offline';

        $dynamicInformation['messageGenerationTimestamp'] = (new \DateTime())->format('c');

        $dynamicInformation['returnInformation'] = [];
        $dynamicInformation['returnInformation']['returnStatus'] = 'ack';

        $output['dynamicInformation'] = $dynamicInformation;

        return $output;
    }

    public function keepAlive($keepAliveInput)
    {
        $logger = $this->getLog();

        $sessionId = $this->getCurrentSessionId();

        if ($logger != null) {
            if ($sessionId != null) {
                $logger->info('keepAlive called for session ' . $sessionId);
            } else {
                $logger->info('keepAlive called with no session ID');
            }
        }

        if (!$sessionId) {
            $sessionId = $this->createSessionID();
        }

        $this->storeCurrentSessionState($sessionId, 'online');

        $output = [];

        $output['modelBaseVersion'] = '3';

        $exchangeContext = [];
        $exchangeContext['codedExchangeProtocol'] = 'statefulPush';
        $exchangeContext['exchangeSpecificationVersion'] = '2020';
        $exchangeContext['supplierOrCisRequester'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier'] = [];
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['country'] = 'NL';
        $exchangeContext['supplierOrCisRequester']['internationalIdentifier']['nationalIdentifier'] = 'NLNDW';

        $output['exchangeContext'] = $exchangeContext;

        $dynamicInformation = [];
        $dynamicInformation['exchangeStatus'] = 'online';

        $dynamicInformation['messageGenerationTimestamp'] = (new \DateTime())->format('c');

        $dynamicInformation['returnInformation'] = [];
        $dynamicInformation['returnInformation']['returnStatus'] = 'ack';

        $dynamicInformation['sessionInformation'] = [];
        $dynamicInformation['sessionInformation']['sessionID'] = $sessionId;

        $output['dynamicInformation'] = $dynamicInformation;

        return $output;
    }

    /**
     * Create a new session ID using the PushSessionStateService.
     *
     * @return string|null
     */
    public function createSessionID()
    {
        $sessionId = null;
        $pushSessionStateService = $this->getPushSessionStateService();
        if ($pushSessionStateService != null) {
            $sessionId = $pushSessionStateService->createSessionID();
        }
        return $sessionId;
    }

    /**
     * Get the current session ID from the session state service.
     *
     * @return string|null
     */
    public function getCurrentSessionId()
    {
        $sessionId = null;
        $pushSessionStateService = $this->getPushSessionStateService();
        if ($pushSessionStateService != null) {
            $pushState = $pushSessionStateService->loadCurrentSessionState();
            if ($pushState != null) {
                $sessionId = $pushState->getSessionID();
            }
        }
        return $sessionId;
    }

    public function storeCurrentSessionState($sessionId, $state)
    {
        $pushSessionStateService = $this->getPushSessionStateService();
        if ($pushSessionStateService != null) {
            $pushSessionStateService->storeSessionState($sessionId, $state);
        }
    }

    public function getSessionIDFromInput($input)
    {
        $sessionId = null;

        $exchangeInformation = null;

        if (isset($input->exchangeInformation)) {
            $exchangeInformation = $input->exchangeInformation;
        }

        $dynamicInformation = null;

        if (isset($exchangeInformation->dynamicInformation)) {
            $dynamicInformation = $exchangeInformation->dynamicInformation;
        }

        $sessionInformation = null;

        if (isset($dynamicInformation->sessionInformation)) {
            $sessionInformation = $dynamicInformation->sessionInformation;
        }

        if ($sessionInformation != null) {
            if (isset($sessionInformation->sessionID)) {
                $sessionId = $sessionInformation->sessionID;
            }
        }

        return $sessionId;
    }
}
