<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Db\Model\Criterium;
use BrugOpen\Db\Model\CriteriumFieldComparison;
use BrugOpen\Db\Service\TableManager;
use BrugOpen\Model\Operation;
use BrugOpen\Model\WebPushSubscription;
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

    public function setDispatcherClient($dispatcherClient)
    {
        $this->dispatcherClient = $dispatcherClient;
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
                    $operationAnnounceText = 'gaat waarschijnlijk open';
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

            $numSent = 0;

            if (is_array($subscriptions) && (count($subscriptions) > 0)) {

                $dispatcherClient = $this->getDispatcherClient();

                if ($dispatcherClient) {

                    $log = $this->getLog();

                    $log->info("Sending push message about operation " . $operationId . ' started to ' . count($subscriptions) . ' subscriber' . (count($subscriptions) != 1 ? 's' : ''));

                    // collect dispatcher messages and log sent messages

                    $messages = array();

                    $payloadJson = json_encode($payload);

                    foreach ($subscriptions as $subscription) {

                        $messageId = null;

                        $insertResult = $this->logPush($subscription->getId(), $operationId, $payload, 1);

                        if (is_numeric($insertResult)) {

                            $messageId = $insertResult;
                        }

                        $numSent++;

                        $webhookUrl = 'https://brugopen.nl/api/push/webhook/?id=' . $messageId . '&guid=' . $subscription->getGuid();

                        $sub = array();
                        $sub['endpoint'] = $subscription->getEndpoint();
                        $sub['keys'] = array();
                        $sub['keys']['auth'] = $subscription->getAuthToken();
                        $sub['keys']['p256dh'] = $subscription->getAuthPublickey();

                        $message = array();
                        $message['subscription'] = $sub;
                        $message['payload'] = $payloadJson;
                        $message['webhook'] = $webhookUrl;

                        $messages[] = $message;
                    }

                    $dispatcherClient->dispatchMessages($messages);

                    $log->info("Sent " . $numSent . ' push message' . ($numSent != 1 ? 's' : ''));
                }
            }

            $markSent = 0;

            if ($numSent > 0) {

                $markSent = $numSent;
            }

            $this->markOperationStartPushSent($operationId, $markSent);

            $res = $numSent;
        }

        return $res;
    }

    public function getPushBridgeTitle($bridge)
    {
        $title = $bridge['title'];

        return $title;
    }

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
     * @param Operation $operation
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

            $payload = array();
            $payload['title'] = $title;
            $payload['body'] = 'van ' . $operation['time_start']->format('H:i') . ' tot ' . $operation['time_end']->format('H:i');
            $payload['link'] = $targetUrl;
            $payload['tag'] = 'operation' . $operationId;

            $numSent = 0;

            if (is_array($subscriptions) && (count($subscriptions) > 0)) {

                $log = $this->getLog();

                $log->info("Sending push message about operation " . $operationId . ' started and ended to ' . count($subscriptions) . ' subscriber' . (count($subscriptions) != 1 ? 's' : ''));

                $dispatcherClient = $this->getDispatcherClient();

                if ($dispatcherClient) {

                    // collect dispatcher messages and log sent messages

                    $messages = array();

                    $payloadJson = json_encode($payload);

                    foreach ($subscriptions as $subscription) {

                        $messageId = null;

                        $insertResult = $this->logPush($subscription->getId(), $operationId, $payload, 1);

                        if (is_numeric($insertResult)) {

                            $messageId = $insertResult;
                        }

                        $numSent++;

                        $webhookUrl = 'https://brugopen.nl/api/push/webhook/?id=' . $messageId . '&guid=' . $subscription->getGuid();

                        $sub = array();
                        $sub['endpoint'] = $subscription->getEndpoint();
                        $sub['keys'] = array();
                        $sub['keys']['auth'] = $subscription->getAuthToken();
                        $sub['keys']['p256dh'] = $subscription->getAuthPublickey();

                        $message = array();
                        $message['subscription'] = $sub;
                        $message['payload'] = $payloadJson;
                        $message['webhook'] = $webhookUrl;

                        $messages[] = $message;
                    }

                    $dispatcherClient->dispatchMessages($messages);
                }

                $log->info("Sent " . $numSent . ' push message' . ($numSent != 1 ? 's' : ''));
            }

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

            $payload = array();
            $payload['title'] = $title;
            $payload['body'] = 'van ' . $operation['time_start']->format('H:i') . ' tot ' . $operation['time_end']->format('H:i');
            $payload['link'] = $targetUrl;
            $payload['tag'] = 'operation' . $operationId;

            $numSent = 0;

            if (is_array($subscriptions) && (count($subscriptions) > 0)) {

                $log = $this->getLog();

                $log->info("Sending push message about operation " . $operationId . ' ended to ' . count($subscriptions) . ' subscriber' . (count($subscriptions) != 1 ? 's' : ''));

                $dispatcherClient = $this->getDispatcherClient();

                if ($dispatcherClient) {

                    // collect dispatcher messages and log sent messages

                    $messages = array();

                    $payloadJson = json_encode($payload);

                    foreach ($subscriptions as $subscription) {

                        $messageId = null;

                        $insertResult = $this->logPush($subscription->getId(), $operationId, $payload, 1);

                        if (is_numeric($insertResult)) {

                            $messageId = $insertResult;
                        }

                        $numSent++;

                        $webhookUrl = 'https://brugopen.nl/api/push/webhook/?id=' . $messageId . '&guid=' . $subscription->getGuid();

                        $sub = array();
                        $sub['endpoint'] = $subscription->getEndpoint();
                        $sub['keys'] = array();
                        $sub['keys']['auth'] = $subscription->getAuthToken();
                        $sub['keys']['p256dh'] = $subscription->getAuthPublickey();

                        $message = array();
                        $message['subscription'] = $sub;
                        $message['payload'] = $payloadJson;
                        $message['webhook'] = $webhookUrl;

                        $messages[] = $message;
                    }

                    $dispatcherClient->dispatchMessages($messages);
                }

                $log->info("Sent " . $numSent . ' push message' . ($numSent != 1 ? 's' : ''));
            }

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
