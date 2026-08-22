<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Db\Model\Criterium;
use BrugOpen\Db\Model\CriteriumFieldComparison;
use BrugOpen\Db\Service\TableManager;
use BrugOpen\Model\WebPushSubscription;
use BrugOpen\Service\ApplePushNotificationClient;
use BrugOpen\Service\FirebasePushNotificationClient;
use BrugOpen\Service\WebPushDispatcherClient;

class WebPushNotificationService
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     *
     * @var TableManager
     */
    private $tableManager;

    /**
     * @var WebPushSubscriptionService
     */
    private $subscriptionService;

    /**
     * @var WebPushDispatcherClient
     */
    private $dispatcherClient;

    /**
     * @var ApplePushNotificationClient
     */
    private $applePushNotificationClient;

    /**
     * @var FirebasePushNotificationClient
     */
    private $firebasePushNotificationClient;

    /**
     * @param Context $context
     */
    public function initialize(Context $context)
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
     * @return TableManager
     */
    public function getTableManager()
    {
        if ($this->tableManager == null) {

            if ($this->context != null) {

                $this->tableManager = $this->context->getService('BrugOpen.TableManager');
            }
        }

        return $this->tableManager;
    }

    /**
     * @param TableManager $tableManager
     */
    public function setTableManager($tableManager)
    {
        $this->tableManager = $tableManager;
    }

    /**
     * @return WebPushDispatcherClient
     */
    public function getDispatcherClient()
    {
        if ($this->dispatcherClient == null) {

            $dispatcherClient = new WebPushDispatcherClient($this->context);
            $this->dispatcherClient = $dispatcherClient;
        }

        return $this->dispatcherClient;
    }

    /**
     * @param WebPushDispatcherClient $dispatcherClient
     */
    public function setDispatcherClient($dispatcherClient)
    {
        $this->dispatcherClient = $dispatcherClient;
    }

    /**
     * @return ApplePushNotificationClient
     */
    public function getApplePushNotificationClient()
    {
        if ($this->applePushNotificationClient == null) {

            $client = new ApplePushNotificationClient($this->context);
            $this->applePushNotificationClient = $client;
        }

        return $this->applePushNotificationClient;
    }

    /**
     * @param ApplePushNotificationClient $applePushNotificationClient
     */
    public function setApplePushNotificationClient($applePushNotificationClient)
    {
        $this->applePushNotificationClient = $applePushNotificationClient;
    }

    /**
     * @return FirebasePushNotificationClient
     */
    public function getFirebasePushNotificationClient()
    {
        if ($this->firebasePushNotificationClient == null) {

            $client = new FirebasePushNotificationClient($this->context);
            $this->firebasePushNotificationClient = $client;
        }

        return $this->firebasePushNotificationClient;
    }

    /**
     * @param FirebasePushNotificationClient $firebasePushNotificationClient
     */
    public function setFirebasePushNotificationClient($firebasePushNotificationClient)
    {
        $this->firebasePushNotificationClient = $firebasePushNotificationClient;
    }

    /**
     * @return WebPushSubscriptionService
     */
    public function getSubscriptionService()
    {
        if ($this->subscriptionService == null) {
            if ($this->context) {
                $subscriptionService = new WebPushSubscriptionService();
                $subscriptionService->initialize($this->context);
                $this->subscriptionService = $subscriptionService;
            }
        }
        return $this->subscriptionService;
    }

    /**
     * @param WebPushSubscriptionService $subscriptionService
     */
    public function setSubscriptionService($subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * @param int $operationId
     * @return int|null
     */
    public function getBridgeIdByOperationId($operationId)
    {
        $bridgeId = null;

        if ($operationId) {

            $tableManager = $this->getTableManager();
            $row = null;

            if ($tableManager) {

                $criteria = array();
                $criteria['id'] = $operationId;

                $row = $tableManager->findRecord('bo_operation', $criteria);
            }

            if ($row) {

                if ($row['bridge_id']) {

                    $bridgeId = (int)$row['bridge_id'];
                }
            }
        }

        return $bridgeId;
    }

    public function findPushableOperationsByBridge($onlyBridgeId = null, $time = null)
    {

        $pushableOperationsByBridge = array();

        if ($time == null) {
            $time = time();
        }

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $onlySince = $time - 1200;

            $criteria = array();
            $criteria[] = new CriteriumFieldComparison('time_start', Criterium::OPERATOR_GE, new \DateTime('@' . $onlySince));

            $timeStart = microtime(true);
            $records = $tableManager->findRecords('bo_operation', $criteria);
            $timeStop = microtime(true);

            $operationsByBridge = array();

            if ($records) {

                foreach ($records as $operation) {

                    $bridgeId = (int)$operation['bridge_id'];

                    if ($bridgeId) {

                        if ($onlyBridgeId) {
                            if ($bridgeId != $onlyBridgeId) {
                                continue;
                            }
                        }

                        $operationsByBridge[$bridgeId][] = $operation;
                    }
                }
            }

            foreach (array_keys($operationsByBridge) as $bridgeId) {

                // sort operations by timeStart

                $lastStartedOperation = null;
                $nextStartingOperation = null;

                foreach ($operationsByBridge[$bridgeId] as $operation) {

                    $timeStart = $operation['time_start'];

                    if ($timeStart) {

                        $started = $timeStart->getTimestamp() <= $time;

                        if ($started) {

                            if ($operation['certainty'] == 3) {

                                if (($lastStartedOperation == null) || ($timeStart->getTimestamp() > $lastStartedOperation['time_start']->getTimestamp())) {

                                    $lastStartedOperation = $operation;
                                }
                            }
                        } else {

                            if ($operation['certainty'] >= 2) {

                                if (($nextStartingOperation == null) || ($timeStart->getTimestamp() < $nextStartingOperation['time_start']->getTimestamp())) {

                                    $nextStartingOperation = $operation;
                                }
                            }
                        }
                    }

                    $pushableOperationsByBridge[$bridgeId] = array($lastStartedOperation, $nextStartingOperation);
                }
            }
        }

        return $pushableOperationsByBridge;
    }

    public function sendOperationPushMessages($onlyBridgeId = null, $time = null)
    {

        $maxEventAge = 1200; // max 20 minutes
        $maxFutureEvent = 1200; // max 20 minutes in future

        $now = ($time != null) ? $time : time();

        $log = $this->getLog();
        $log->debug('Sending operation push messages started');

        $pushableOperationsByBridge = $this->findPushableOperationsByBridge($onlyBridgeId, $time);

        $log->debug('Found ' . count($pushableOperationsByBridge) . ' pushable operations by bridge');

        $subscriptionService = $this->getSubscriptionService();

        foreach (array_keys($pushableOperationsByBridge) as $bridgeId) {

            $lastStartedOperation = $pushableOperationsByBridge[$bridgeId][0];
            $nextStartingOperation = $pushableOperationsByBridge[$bridgeId][1];

            if ($lastStartedOperation) {

                if ($lastStartedOperation['push_sent_end'] == '') {

                    $operationEnd = 0;

                    if ($lastStartedOperation['time_end']) {
                        $operationEnd = $lastStartedOperation['time_end']->getTimestamp();
                    }

                    $operationStart = $lastStartedOperation['time_start']->getTimestamp();

                    if ($lastStartedOperation['push_sent_start'] != '') {

                        // already pushed message about operation start

                        if ($operationEnd > 0) {

                            // operation end time is known

                            if ($operationEnd < $now) {

                                // operation end time is in past

                                if (($now - $operationEnd) < $maxEventAge) {

                                    $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                    // send push about operation end
                                    $this->pushBridgeClose($lastStartedOperation, $subscriptions);
                                } else {

                                    // operation end is too long ago

                                }
                            } else {

                                // end date is in future but already sent push about start (and possibly expected duration)

                            }
                        } else {

                            // nothing to push - operation is still in progress and no expected end time

                        }
                    } else {

                        // nothing pushed about this operation

                        if ($operationStart > $now) {

                            // start date is in future

                            if (($operationStart - $now) < $maxFutureEvent) {

                                $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                $this->pushBridgeOpen($lastStartedOperation, $subscriptions);
                            }
                        } else {

                            // start date is in past

                            if ($operationEnd > 0) {

                                if ($operationEnd > $now) {

                                    $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                    // end date is in future - send push about operation start (and expected duration if not too far away)
                                    $this->pushBridgeOpen($lastStartedOperation, $subscriptions);
                                } else {

                                    // end date is in past

                                    if (($now - $operationEnd) < $maxEventAge) {

                                        $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                        // send push about operation start and end
                                        $this->pushBridgeOpenAndClose($lastStartedOperation, $subscriptions);
                                    }
                                }
                            } else {

                                // end time unknown

                                if (($now - $operationStart) < $maxEventAge) {

                                    $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                    // send push about operation start
                                    $this->pushBridgeOpen($lastStartedOperation, $subscriptions);
                                } else {

                                    // start was too long ago

                                }
                            }
                        }
                    }
                } else {

                    // already pushed about operation end

                }
            }

            if ($nextStartingOperation) {

                if ($nextStartingOperation['push_sent_end'] == '') {

                    $operationEnd = 0;

                    if ($nextStartingOperation['datetime_end'] > 0) {
                        $operationEnd = $nextStartingOperation['datetime_end'];
                    } else if ($nextStartingOperation['datetime_gone'] > 0) {
                        $operationEnd = $nextStartingOperation['datetime_gone'];
                    }

                    $operationStart = $nextStartingOperation['datetime_start']; // TODO use time_start

                    if ($nextStartingOperation['push_sent_start'] != '') {

                        // already sent push about operation start

                        if ($operationEnd > 0) {

                            // operation end time is known

                            if ($operationEnd < $now) {

                                // operation end time is in past

                                if (($now - $operationEnd) < $maxEventAge) {

                                    $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                    // send push about operation end
                                    $this->pushBridgeClose($nextStartingOperation, $subscriptions);
                                } else {

                                    // operation end is too long ago

                                }
                            } else {

                                // end date is in future but already sent push about start (and possibly expected duration)

                            }
                        } else {

                            // nothing to push - operation is still in progress and no expected end time

                        }
                    } else {

                        // nothing pushed about this operation

                        if ($operationStart > $now) {

                            // start date is in future

                            if (($operationStart - $now) < $maxFutureEvent) {

                                $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                // send push about operation start
                                $this->pushBridgeOpen($nextStartingOperation, $subscriptions);
                            }
                        } else {

                            // start date is in past

                            if ($operationEnd > 0) {

                                if ($operationEnd > $now) {

                                    $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                    // end date is in future - send push about operation start (and expected duration if not too far away)
                                    $this->pushBridgeOpen($nextStartingOperation, $subscriptions);
                                } else {

                                    // end date is in past

                                    if (($now - $operationEnd) < $maxEventAge) {

                                        $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                        // send push about operation start and end
                                        $this->pushBridgeOpenAndClose($nextStartingOperation, $subscriptions);
                                    }
                                }
                            } else {

                                // end time unknown

                                if (($now - $operationStart) < $maxEventAge) {

                                    $subscriptions = $subscriptionService->findSubscriptionsByBridgeAndTime($bridgeId, $now);

                                    // send push about operation start
                                    $this->pushBridgeOpen($nextStartingOperation, $subscriptions);
                                } else {

                                    // start was too long ago

                                }
                            }
                        }
                    }
                } else {

                    // already pushed message about operation end

                }
            }
        }
    }

    /**
     * @param int $bridgeId
     * @return boolean
     */
    public function bridgeHasCurrentOperations($bridgeId)
    {

        $bridgeHasCurrentOperations = false;

        $tableManager = $this->getTableManager();

        if ($tableManager && $bridgeId) {

            $criteria = array();
            $criteria['current'] = 1;
            $criteria['bridge_id'] = $bridgeId;

            $fields = array('bridge_id');

            $rows = $tableManager->findRecords('bo_operation', $criteria, $fields);

            if ($rows) {
                $bridgeHasCurrentOperations = true;
            }
        }

        return $bridgeHasCurrentOperations;
    }

    /**
     * @return array[]
     */
    public function getActiveBridges()
    {
        $bridges = [];

        $currentOperationBridgeIds = array();

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $criteria = array();
            $criteria['current'] = 1;

            $fields = array('bridge_id');

            $rows = $tableManager->findRecords('bo_operation', $criteria, $fields);

            if ($rows) {

                foreach ($rows as $row) {

                    $bridgeId = (int)$row['bridge_id'];

                    if ($bridgeId) {

                        if (!array_key_exists($bridgeId, $currentOperationBridgeIds)) {

                            $currentOperationBridgeIds[$bridgeId] = $bridgeId;
                        }
                    }
                }
            }
        }

        $rows = $tableManager->findRecords('bo_bridge');

        if ($rows) {

            foreach ($rows as $row) {

                if ($row['name'] == '') {
                    continue;
                }

                if ($row['title'] == '') {
                    continue;
                }

                $bridgeId = (int)$row['id'];

                if ($bridgeId) {

                    if (!array_key_exists($bridgeId, $currentOperationBridgeIds)) {
                        continue;
                    }

                    $bridges[$bridgeId] = $row;
                }
            }
        }

        return $bridges;
    }

    /**
     * @param array $operation
     * @param WebPushSubscription[] $subscriptions
     */
    public function pushBridgeOpen($operation, $subscriptions)
    {

        $res = null;

        $operationId = $operation['id'];
        $bridgeId = $operation['bridge_id'];

        if ($bridge = $this->findBridge($bridgeId)) {

            $operationStart = $operation['time_start']->getTimestamp();
            $now = time();

            $bridgeTitle = $this->getPushBridgeTitle($bridge);
            $targetUrl = $this->getTargetUrl($bridge);

            $operationEnd = $operation['time_end'] ? $operation['time_end']->getTimestamp() : 0;

            $durationSecs = 0;
            if ($operationEnd > $operationStart) {
                $durationSecs = $operationEnd - $operationStart;
            }

            $certainty = null;

            if ($operation['certainty'] != '') {
                $certainty = (int)$operation['certainty'];
            }

            if ($operationStart > $now) {
                // start date is in future

                $operationAnnounceText = 'gaat open';

                if ($certainty == 2) {
                    $operationAnnounceText = 'gaat mogelijk open';
                }

                if (($durationSecs > 60) && ($durationSecs < 3600)) {

                    $durationMinutes = ceil($durationSecs / 60);
                    $durationText = $durationMinutes . ' minuten';

                    $title = $bridgeTitle . ' ' . $operationAnnounceText;
                    $body = 'om ' . date('H:i', $operationStart) . ' voor ' . $durationText;
                } else {

                    $title = $bridgeTitle . ' ' . $operationAnnounceText;
                    $body = 'om ' . date('H:i', $operationStart);
                }
            } else {

                if (($operationEnd > $now) && ($durationSecs > 60) && ($durationSecs < 3600)) {

                    $durationMinutes = ceil($durationSecs / 60);
                    $durationText = $durationMinutes . ' minuten';

                    $title = $bridgeTitle . ' is open';
                    $body = 'sinds ' . date('H:i', $operationStart) . ' en zal ' . $durationText . ' open zijn';
                } else {

                    $title = $bridgeTitle . ' is open';
                    $body = 'sinds ' . date('H:i', $operationStart);
                }
            }

            $payload = array();
            $payload['title'] = $title;
            $payload['body'] = $body;
            $payload['link'] = $targetUrl;
            $payload['tag'] = 'operation' . $operationId;

            if ($operationStart > $now) {
                $appleStateText = ($certainty == 2) ? 'gaat mogelijk open' : 'gaat open';
                $appleCurrentText = $appleStateText . ' ' . $body;
                $appleTitle = $bridgeTitle . ' gaat open';
            } else {
                $appleCurrentText = 'is open ' . $body;
                $appleTitle = $bridgeTitle . ' is open';
            }

            $appleBody = $this->getAppleBodyText($bridge, $appleCurrentText);
            $applePayloadJson = $this->createApplePayloadJson($appleTitle, $appleBody, $operationId, $bridge['name']);

            $androidBody = $this->getAndroidBodyText($bridge, $appleCurrentText);
            $androidPayloadJson = $this->createAndroidPayloadJson($title, $androidBody, $operationId, $bridge['name']);

            $numSent = $this->dispatchPayloadToSubscriptions($operationId, $payload, $subscriptions, 'started', $applePayloadJson, $androidPayloadJson, $bridge['name']);

            $markSent = 0;

            if ($numSent > 0) {

                $markSent = $numSent;
            }

            $this->markOperationStartPushSent($operationId, $markSent);

            $res = $numSent;
        }

        return $res;
    }

    /**
     * @param array $bridge
     * @return string
     */
    public function getPushBridgeTitle($bridge)
    {
        $title = $bridge['title'];

        if (array_key_exists('distinctive_title', $bridge) && ($bridge['distinctive_title'] != '')) {
            $title = $bridge['distinctive_title'];
        }

        return $title;
    }

    /**
     * @param array $bridge
     * @return string
     */
    public function getTargetUrl($bridge)
    {

        $targetUrl = 'https://brugopen.nl';

        if (($bridge != null) && (is_array($bridge))) {

            if ($bridge['name'] != '') {
                $targetUrl .= '/' . $bridge['name'] . '/';
            }
        }

        return $targetUrl;
    }

    /**
     * @param array $operation
     * @param WebPushSubscription[] $subscriptions
     */
    public function pushBridgeOpenAndClose($operation, $subscriptions)
    {

        $res = null;

        $operationId = $operation['id'];

        $bridgeId = $operation['bridge_id'];

        if ($bridge = $this->findBridge($bridgeId)) {

            $bridgeTitle = $this->getPushBridgeTitle($bridge);
            $targetUrl = $this->getTargetUrl($bridge);

            $title = $bridgeTitle . ' was open';

            $operationStart = $operation['time_start']->getTimestamp();
            $operationEnd = $operation['time_end']->getTimestamp();

            $payload = array();
            $payload['title'] = $title;
            $payload['body'] = 'van ' . date('H:i', $operationStart) . ' tot ' . date('H:i', $operationEnd);
            $payload['link'] = $targetUrl;
            $payload['tag'] = 'operation' . $operationId;

            $appleCurrentText = 'was open ' . $payload['body'];
            $appleTitle = $bridgeTitle . ' was open';
            $appleBody = $this->getAppleBodyText($bridge, $appleCurrentText);
            $applePayloadJson = $this->createApplePayloadJson($appleTitle, $appleBody, $operationId, $bridge['name']);

            $androidBody = $this->getAndroidBodyText($bridge, $appleCurrentText);
            $androidPayloadJson = $this->createAndroidPayloadJson($title, $androidBody, $operationId, $bridge['name']);

            $numSent = $this->dispatchPayloadToSubscriptions($operationId, $payload, $subscriptions, 'started and ended', $applePayloadJson, $androidPayloadJson, $bridge['name']);

            $markSent = 0;

            if ($numSent > 0) {

                $markSent = $numSent;
            }

            $this->markOperationEndPushSent($operationId, $markSent);

            $res = $numSent;
        }

        return $res;
    }

    /**
     * @param array $operation
     * @param WebPushSubscription[] $subscriptions
     */
    public function pushBridgeClose($operation, $subscriptions)
    {
        $res = null;

        $operationId = $operation['id'];

        $bridgeId = $operation['bridge_id'];

        if ($bridge = $this->findBridge($bridgeId)) {

            $bridgeTitle = $this->getPushBridgeTitle($bridge);
            $targetUrl = $this->getTargetUrl($bridge);

            $title = $bridgeTitle . ' was open';

            $operationStart = $operation['time_start']->getTimestamp();
            $operationEnd = $operation['time_end']->getTimestamp();

            $payload = array();
            $payload['title'] = $title;
            $payload['body'] = 'van ' . date('H:i', $operationStart) . ' tot ' . date('H:i', $operationEnd);
            $payload['link'] = $targetUrl;
            $payload['tag'] = 'operation' . $operationId;

            $appleCurrentText = 'was open ' . $payload['body'];
            $appleTitle = $bridgeTitle . ' was open';
            $appleBody = $this->getAppleBodyText($bridge, $appleCurrentText);
            $applePayloadJson = $this->createApplePayloadJson($appleTitle, $appleBody, $operationId, $bridge['name']);

            $androidBody = $this->getAndroidBodyText($bridge, $appleCurrentText);
            $androidPayloadJson = $this->createAndroidPayloadJson($title, $androidBody, $operationId, $bridge['name']);

            $numSent = $this->dispatchPayloadToSubscriptions($operationId, $payload, $subscriptions, 'ended', $applePayloadJson, $androidPayloadJson, $bridge['name']);

            $markSent = 0;

            if ($numSent > 0) {

                $markSent = $numSent;
            }

            $this->markOperationEndPushSent($operationId, $markSent);

            $res = $numSent;
        }

        return $res;
    }

    /**
     * @param int $operationId
     * @param array $payload
     * @param WebPushSubscription[] $subscriptions
     * @param string $operationStatusText
     * @param string|null $androidPayloadJson
     * @param string|null $applePayloadJson
     * @param string $bridgeName
     * @return int
     */
    private function dispatchPayloadToSubscriptions($operationId, $payload, $subscriptions, $operationStatusText, $applePayloadJson, $androidPayloadJson, $bridgeName)
    {

        $numSent = 0;

        if (!(is_array($subscriptions) && (count($subscriptions) > 0))) {
            return $numSent;
        }

        $log = $this->getLog();

        $log->info('Sending push message about operation ' . $operationId . ' ' . $operationStatusText . ' to ' . count($subscriptions) . ' subscriber' . (count($subscriptions) != 1 ? 's' : ''));

        $webMessages = array();
        $iosMessages = array();
        $androidMessages = array();

        $payloadJson = json_encode($payload);

        if ($applePayloadJson == null) {
            $applePayloadJson = $this->createApplePayloadJson($payload['title'], $payload['body'], $operationId, $bridgeName);
        }

        if ($androidPayloadJson == null) {
            $androidPayloadJson = $this->createAndroidPayloadJson($payload['title'], $payload['body'], $operationId, $bridgeName);
        }

        foreach ($subscriptions as $subscription) {

            $platform = $subscription->getPlatform();
            if ($platform == '') {
                $platform = 'web';
            }

            if (($platform == 'ios') && ($subscription->getClientId() == '')) {
                continue;
            }

            if (($platform == 'android') && ($subscription->getClientId() == '')) {
                continue;
            }

            if (($platform != 'ios') && ($platform != 'android') && (($subscription->getEndpoint() == '') || ($subscription->getAuthToken() == '') || ($subscription->getAuthPublickey() == ''))) {
                continue;
            }

            $messageId = null;

            $insertResult = $this->logPush($subscription->getId(), $operationId, $payload, 1);

            if (is_numeric($insertResult)) {
                $messageId = $insertResult;
            }

            $webhookUrl = 'https://brugopen.nl/api/push/webhook/?id=' . $messageId . '&guid=' . $subscription->getGuid();

            if ($platform == 'ios') {

                $iosMessage = $this->buildApplePushDispatcherMessage($subscription, $applePayloadJson, $webhookUrl, $bridgeName, $operationId);

                if ($iosMessage != null) {
                    $iosMessages[] = $iosMessage;
                    $numSent++;
                }
            } else if ($platform == 'android') {

                $androidMessage = $this->buildFirebasePushDispatcherMessage($subscription, $androidPayloadJson, $webhookUrl);

                if ($androidMessage != null) {
                    $androidMessages[] = $androidMessage;
                    $numSent++;
                }
            } else {

                $webMessage = $this->buildWebPushDispatcherMessage($subscription, $payloadJson, $webhookUrl);

                if ($webMessage != null) {
                    $webMessages[] = $webMessage;
                    $numSent++;
                }
            }
        }

        if (count($webMessages) > 0) {

            $dispatcherClient = $this->getDispatcherClient();

            if ($dispatcherClient) {
                $dispatcherClient->dispatchMessages($webMessages);
            }
        }

        if (count($iosMessages) > 0) {

            $applePushNotificationClient = $this->getApplePushNotificationClient();

            if ($applePushNotificationClient) {
                $applePushNotificationClient->dispatchMessages($iosMessages);
            }
        }

        if (count($androidMessages) > 0) {

            $firebasePushNotificationClient = $this->getFirebasePushNotificationClient();

            if ($firebasePushNotificationClient) {
                $firebasePushNotificationClient->dispatchMessages($androidMessages);
            }
        }

        $log->info('Sent ' . $numSent . ' push message' . ($numSent != 1 ? 's' : ''));

        return $numSent;
    }

    /**
     * @param WebPushSubscription $subscription
     * @param string $payloadJson
     * @param string $webhookUrl
     * @return array|null
     */
    private function buildWebPushDispatcherMessage($subscription, $payloadJson, $webhookUrl)
    {

        if ($subscription->getEndpoint() == '') {
            return null;
        }
        if ($subscription->getAuthToken() == '') {
            return null;
        }
        if ($subscription->getAuthPublickey() == '') {
            return null;
        }

        $sub = array();
        $sub['endpoint'] = $subscription->getEndpoint();
        $sub['keys'] = array();
        $sub['keys']['auth'] = $subscription->getAuthToken();
        $sub['keys']['p256dh'] = $subscription->getAuthPublickey();

        $message = array();
        $message['subscription'] = $sub;
        $message['payload'] = $payloadJson;
        $message['webhook'] = $webhookUrl;

        return $message;
    }

    /**
     * @param WebPushSubscription $subscription
     * @param string $payloadJson
     * @param string $webhookUrl
     * @return array|null
     */
    private function buildApplePushDispatcherMessage($subscription, $payloadJson, $webhookUrl, $bridgeName, $operationId)
    {

        if ($subscription->getClientId() == '') {
            return null;
        }

        $message = array();
        $message['clientId'] = $subscription->getClientId();
        $message['payload'] = $payloadJson;
        $message['webhook'] = $webhookUrl;

        return $message;
    }

    /**
     * @param WebPushSubscription $subscription
     * @param string $payloadJson
     * @param string $webhookUrl
     * @return array|null
     */
    private function buildFirebasePushDispatcherMessage($subscription, $payloadJson, $webhookUrl)
    {

        if ($subscription->getClientId() == '') {
            return null;
        }

        $message = array();
        $message['clientId'] = $subscription->getClientId();
        $message['payload'] = $payloadJson;
        $message['webhook'] = $webhookUrl;

        return $message;
    }

    /**
     * @param string $title
     * @param string $body
     * @param int $operationId
     * @param string $bridgeName
     * @return string
     */
    private function createApplePayloadJson($title, $body, $operationId, $bridgeName)
    {

        $applePayload = array(
            'aps' => array(
                'alert' => array(
                    'title' => $title,
                    'body' => $body
                ),
                'sound' => 'default'
            ),
            'bridgeName' => $bridgeName,
            'operationId' => $operationId
        );

        return json_encode($applePayload);
    }

    /**
     * @param string $title
     * @param string $body
     * @param int $operationId
     * @param string $bridgeName
     * @return string
     */
    private function createAndroidPayloadJson($title, $body, $operationId, $bridgeName)
    {

        $androidPayload = array(
            'notification' => array(
                'title' => $title,
                'body' => $body
            ),
            'data' => array(
                'bridgeName' => $bridgeName,
                'operationId' => (string)$operationId
            )
        );

        return json_encode($androidPayload);
    }

    /**
     * @param array $bridge
     * @param string $currentText
     * @return string
     */
    private function getAppleBodyText($bridge, $currentText)
    {

        $cityPart = '';

        if (array_key_exists('city', $bridge) && ($bridge['city'] != '')) {
            if (array_key_exists('city2', $bridge) && ($bridge['city2'] != '')) {
                $cityPart = ' tussen ' . $bridge['city'] . ' en ' . $bridge['city2'];
            } else {
                $cityPart = ' in ' . $bridge['city'];
            }
        }

        return 'De ' . $bridge['title'] . $cityPart . ' ' . $currentText;
    }

    /**
     * @param array $bridge
     * @param string $currentText
     * @return string
     */
    private function getAndroidBodyText($bridge, $currentText)
    {

        $cityPart = '';

        if (array_key_exists('city', $bridge) && ($bridge['city'] != '')) {
            if (array_key_exists('city2', $bridge) && ($bridge['city2'] != '')) {
                $cityPart = ' tussen ' . $bridge['city'] . ' en ' . $bridge['city2'];
            } else {
                $cityPart = ' in ' . $bridge['city'];
            }
        }

        return 'De ' . $bridge['title'] . $cityPart . ' ' . $currentText;
    }

    /**
     * @param int $operationId
     * @return array
     */
    public function findOperation($operationId)
    {
        $operation = null;

        $keys = array();
        $keys['id'] = $operationId;

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $record = $tableManager->findRecord('bo_operation', $keys);

            if ($record) {
                $operation = $record;
            }
        }
        return $operation;
    }

    /**
     * Find bridge by id. Only returns when bridge has a title
     * @param int $bridgeId
     * @return array
     */
    public function findBridge($bridgeId)
    {
        $bridge = null;

        $keys = array();
        $keys['id'] = $bridgeId;

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $record = $tableManager->findRecord('bo_bridge', $keys);

            if ($record) {

                if ($record['title'] != '') {

                    $bridge = $record;
                }
            }
        }

        return $bridge;
    }

    /**
     * @param int $subscriptionId
     * @param int $operationId
     * @param array $payload
     * @param bool $result
     * @return int|null
     */
    public function logPush($subscriptionId, $operationId, $payload, $result)
    {

        $values = array();
        $values['subscription_id'] = $subscriptionId;
        $values['operation_id'] = $operationId;
        $values['result'] = $result ? 1 : 0;
        $values['payload'] = json_encode($payload);

        $now = date('Y-m-d H:i:s');

        $values['datetime_sent'] = $now;
        $values['datetime_created'] = $now;
        $values['datetime_modified'] = $now;

        $tableManager = $this->getTableManager();

        if ($tableManager) {
            $res = $tableManager->insertRecord('bo_push_message', $values);
        }

        return $res;
    }

    /**
     * @param int $operationId
     * @param int $numSent
     */
    public function markOperationStartPushSent($operationId, $numSent)
    {

        if ($operationId > 0) {

            $keys = array();
            $keys['id'] = $operationId;

            $values['push_sent_start'] = ($numSent > 0) ? $numSent : 0;

            $tableManager = $this->getTableManager();

            if ($tableManager) {
                $tableManager->updateRecords('bo_operation', $values, $keys);
            }
        }
    }

    /**
     * @param int $operationId
     * @param int $numSent
     */
    public function markOperationEndPushSent($operationId, $numSent)
    {

        if ($operationId > 0) {

            $keys = array();
            $keys['id'] = $operationId;

            $values['push_sent_end'] = ($numSent > 0) ? $numSent : 0;

            $tableManager = $this->getTableManager();

            if ($tableManager) {
                $tableManager->updateRecords('bo_operation', $values, $keys);
            }
        }
    }
}
