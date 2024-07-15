<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Db\Service\TableManager;
use BrugOpen\Model\WebPushSubscription;

class WebPushSubscriptionService
{

    /**
     * @var Context
     */
    private $context;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @var TableManager
     */
    private $tableManager;

    /**
     * @param Context $context
     */
    public function initialize($context)
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

    public function updateNotificationSchedules($guid, $schedules)
    {

        if (($guid != '') && (is_array($schedules))) {

            $subscription = $this->findSubscription($guid);

            if (!$subscription) {

                $this->insertSubscription($guid);
                $subscription = $this->findSubscription($guid);
            }

            if ($subscription) {

                $subscriptionId = (int)$subscription['id'];

                $existingSchedules = $this->loadSubscriptionSchedules($subscriptionId);

                $dayNumbers = $this->getDayNumbers();

                $keepScheduleIds = array();

                $bridgeIdsByName = $this->loadBridgeIdsByName();

                foreach ($schedules as $schedule) {

                    $bridgeId = null;

                    if (array_key_exists('bridgeId', $schedule)) {

                        $bridgeId = (int)$schedule['bridgeId'];
                    } else if (array_key_exists('bridgeName', $schedule)) {

                        $bridgeName = $schedule['bridgeName'];

                        if (array_key_exists($bridgeName, $bridgeIdsByName)) {

                            $bridgeId = $bridgeIdsByName[$bridgeName];
                        }
                    }

                    if (($bridgeId) && array_key_exists('scheme', $schedule)) {

                        foreach ($schedule['scheme'] as $scheme) {

                            if (array_key_exists('days', $scheme) && array_key_exists('timeStart', $scheme) && array_key_exists('timeEnd', $scheme)) {

                                $days = $scheme['days'];
                                $timeStart = $scheme['timeStart'];
                                $timeEnd = $scheme['timeEnd'];

                                if (is_array($days)) {

                                    foreach ($days as $dayName) {

                                        $dayNumber = null;

                                        if (array_key_exists($dayName, $dayNumbers)) {

                                            $dayNumber = $dayNumbers[$dayName];
                                        }

                                        if ($dayNumber) {

                                            $startHour = null;
                                            $startMinute = null;

                                            $endHour = null;
                                            $endMinute = null;

                                            if (sizeof($timeStart) >= 2) {

                                                if (is_numeric($timeStart[0]) && (((int)$timeStart[0]) >= 0) && (((int)$timeStart[0]) < 24)) {

                                                    $startHour = (int)$timeStart[0];
                                                }

                                                if (is_numeric($timeStart[1]) && (((int)$timeStart[1]) >= 0) && (((int)$timeStart[1]) < 60)) {

                                                    $startMinute = (int)$timeStart[1];
                                                }
                                            }

                                            if (sizeof($timeEnd) >= 2) {

                                                if (is_numeric($timeEnd[0]) && (((int)$timeEnd[0]) >= 0) && (((int)$timeEnd[0]) < 24)) {

                                                    $endHour = (int)$timeEnd[0];
                                                }

                                                if (is_numeric($timeEnd[1]) && (((int)$timeEnd[1]) >= 0) && (((int)$timeEnd[1]) < 60)) {

                                                    $endMinute = (int)$timeEnd[1];
                                                }
                                            }

                                            if (($startHour !== null) && ($startMinute !== null) && ($endHour !== null) && ($endMinute !== null)) {

                                                $start = ($startHour * 100) + $startMinute;
                                                $end = ($endHour * 100) + $endMinute;

                                                $existingSchedule = $this->getExistingSchedule($existingSchedules, $bridgeId, $dayNumber, $start, $end);

                                                if ($existingSchedule) {

                                                    $keepScheduleIds[] = (int)$existingSchedule['id'];
                                                } else {

                                                    if ($insertedScheduleId = $this->insertSubscriptionSchedule($subscriptionId, $bridgeId, $dayNumber, $start, $end)) {

                                                        $keepScheduleIds[] = (int)$insertedScheduleId;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (sizeof($existingSchedules) > 0) {

                    $this->deleteSubscriptionSchedules($subscriptionId, $keepScheduleIds);
                }

                // mark subscription updated
                $values = array();
                $values['datetime_modified'] = new \DateTime();

                $criteria = array();
                $criteria['id'] = $subscriptionId;

                $tableManager = $this->getTableManager();
                $tableManager->updateRecords('bo_push_subscription', $values, $criteria);

                $log = $this->getLog();
                $log->info('Subscription ' . $subscriptionId . ' schedules updated');
            }
        }
    }

    public function loadSubscriptionSchedules($subscriptionId)
    {

        $res = null;

        if ($subscriptionId) {

            $keys = array();
            $keys['subscription_id'] = $subscriptionId;

            $tableManager = $this->getTableManager();

            if ($tableManager) {
                $res = $tableManager->findRecords('bo_push_subscription_schedule', $keys);
            }
        }

        return $res;
    }

    public function deleteSubscriptionSchedules($subscriptionId, $keepScheduleIds)
    {

        if (($subscriptionId > 0) && (is_array($keepScheduleIds))) {

            $tableManager = $this->getTableManager();

            $deleteSchedules = array();

            $criteria = array();
            $criteria['subscription_id'] = $subscriptionId;

            $existingSchedules = $tableManager->findRecords('bo_push_subscription_schedule', $criteria);

            if ($existingSchedules) {

                foreach ($existingSchedules as $existingSchedule) {

                    $scheduleId = $existingSchedule['id'];

                    if (in_array($scheduleId, $keepScheduleIds)) {
                        continue;
                    }

                    $deleteSchedules[$scheduleId] = $existingSchedule;
                }
            }

            if ($deleteSchedules) {

                $log = $this->getLog();

                $log->info('Deleting ' . count($deleteSchedules) . ' schedule' . (count($deleteSchedules) != 1 ? 's' : '') . ' for subscription ' . $subscriptionId);

                $criteria = array();
                $criteria['id'] = array_keys($deleteSchedules);

                $existingSchedules = $tableManager->deleteRecords('bo_push_subscription_schedule', $criteria);
            }
        }
    }

    public function getExistingSchedule($schedules, $bridgeId, $dayNumber, $start, $end)
    {

        $existingSchedule = null;

        if (is_array($schedules)) {

            foreach ($schedules as $schedule) {

                if (($schedule['bridge_id'] == $bridgeId) && ($schedule['day'] == $dayNumber)) {

                    if (($schedule['time_start'] == $start) && ($schedule['time_end'] == $end)) {

                        $existingSchedule = $schedule;
                        break;
                    }
                }
            }
        }

        return $existingSchedule;
    }

    public function insertSubscriptionSchedule($subscriptionId, $bridgeId, $dayNumber, $start, $end)
    {

        $scheduleId = null;

        $values = array();

        $values['subscription_id'] = $subscriptionId;
        $values['bridge_id'] = $bridgeId;
        $values['day'] = $dayNumber;
        $values['time_start'] = $start;
        $values['time_end'] = $end;

        $log = $this->getLog();
        $log->info('Inserting subscription schedule for bridge ' . $bridgeId . ' on day ' . $dayNumber . ' from ' . $start . ' until ' . $end . ' for subscription ' . $subscriptionId);

        $tableManager = $this->getTableManager();

        if ($res = $tableManager->insertRecord('bo_push_subscription_schedule', $values)) {

            $scheduleId = $res;
        }

        return $scheduleId;
    }

    public function getDayNumbers()
    {

        $dayNumbers = array();

        $dayNumbers['mon'] = 1;
        $dayNumbers['tue'] = 2;
        $dayNumbers['wed'] = 3;
        $dayNumbers['thu'] = 4;
        $dayNumbers['fri'] = 5;
        $dayNumbers['sat'] = 6;
        $dayNumbers['sun'] = 7;

        return $dayNumbers;
    }

    public function updatePushSubscription($guid, $pushSubscription)
    {

        if (($guid != '') && (is_array($pushSubscription))) {

            $subscription = $this->findSubscription($guid);

            if (!$subscription) {

                $this->insertSubscription($guid);
                $subscription = $this->findSubscription($guid);
            }

            if ($subscription) {

                $updatedValues = array();

                if (isset($pushSubscription['endpoint']) && ($pushSubscription['endpoint'] != '')) {

                    if ($pushSubscription['endpoint'] != $subscription['endpoint']) {

                        $updatedValues['endpoint'] = $pushSubscription['endpoint'];
                    }
                }

                if (isset($pushSubscription['expirationTime']) && ($pushSubscription['expirationTime'] != '')) {

                    if ($pushSubscription['expirationTime'] != $subscription['expiration_time']) {

                        $updatedValues['expiration_time'] = $pushSubscription['expirationTime'];
                    }
                }

                if (isset($pushSubscription['keys'])) {

                    if (isset($pushSubscription['keys']['auth']) && ($pushSubscription['keys']['auth'] != '')) {

                        if ($pushSubscription['keys']['auth'] != $subscription['auth_key']) {

                            $updatedValues['auth_publickey'] = $pushSubscription['keys']['auth'];
                        }
                    }

                    if (isset($pushSubscription['keys']['p256dh']) && ($pushSubscription['keys']['p256dh'] != '')) {

                        if ($pushSubscription['keys']['p256dh'] != $subscription['auth_p256dh']) {

                            $updatedValues['auth_p256dh'] = $pushSubscription['keys']['p256dh'];
                        }
                    }
                } else {

                    if (isset($pushSubscription['publicKey']) && ($pushSubscription['publicKey'] != '')) {

                        $updatedValues['auth_publickey'] = $pushSubscription['publicKey'];
                    }

                    if (isset($pushSubscription['authToken']) && ($pushSubscription['authToken'] != '')) {

                        $updatedValues['auth_token'] = $pushSubscription['authToken'];
                    }

                    if (isset($pushSubscription['contentEncoding']) && ($pushSubscription['contentEncoding'] != '')) {

                        $updatedValues['content_encoding'] = $pushSubscription['contentEncoding'];
                    }
                }

                if (sizeof($updatedValues) > 0) {

                    $keys = array('id' => $subscription['id']);

                    $values = $updatedValues;
                    $values['datetime_modified'] = new \DateTime();

                    $tableManager = $this->getTableManager();

                    if ($tableManager) {

                        $tableManager->updateRecords('bo_push_subscription', $values, $keys);
                    }
                }
            }
        }
    }

    public function findSubscription($guid)
    {

        $subscription = null;

        if ($guid != '') {

            $keys = array('guid' => $guid);

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                if ($record = $tableManager->findRecord('bo_push_subscription', $keys)) {

                    $subscription = $record;
                }
            }
        }

        return $subscription;
    }

    public function findSubscriptionsByEndpoint($endpoint)
    {

        $subscriptions = null;

        if ($endpoint != '') {

            $keys = array('endpoint' => $endpoint);

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                if ($record = $tableManager->findRecords('bo_push_subscription', $keys)) {

                    $subscriptions = $record;
                }
            }
        }

        return $subscriptions;
    }

    public function insertSubscription($guid)
    {

        if ($guid != '') {

            $now = new \DateTime();
            $values = array('guid' => $guid, 'datetime_created' => $now, 'datetime_modified' => $now);

            $log = $this->getLog();

            $log->info('Inserting subscription ' . $guid);

            $tableManager = $this->getTableManager();
            $tableManager->insertRecord('bo_push_subscription', $values);
        }
    }

    public function loadBridgeIdsByName()
    {

        $bridgeIdsByName = array();

        $tableManager = $this->getTableManager();
        $criteria = null;
        $fields = array('id', 'name');

        $bridges = $tableManager->findRecords('bo_bridge', $criteria, $fields);

        foreach ($bridges as $bridge) {

            $bridgeId = $bridge['id'];
            $bridgeName = $bridge['name'];

            if (($bridgeName != '') && ($bridgeId > 0)) {

                $bridgeIdsByName[$bridgeName] = $bridgeId;
            }
        }

        return $bridgeIdsByName;
    }

    /**
     * @param int $bridgeId
     * @param int $time
     * @return WebPushSubscription[]
     */
    public function findSubscriptionsByBridgeAndTime($bridgeId, $time)
    {
        $subscribers = array();

        $log = $this->getLog();

        $log->debug('Finding subscriptions for bridge ' . $bridgeId . ' on ' . $time);

        if (($bridgeId > 0) && ($time > 0)) {

            // 1 = mon - 7 = sun
            $day = (int)date('w', $time);

            if ($day == 0) {
                $day = 7;
            }

            $hour = (int)date('G', $time);
            $minute = (int)date('i', $time);

            $timeOnDay = ($hour * 100) + $minute;

            $subscriptionIds = array();

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                $criteria = array();
                $criteria['bridge_id'] = (int)$bridgeId;
                $criteria['day'] = $day;

                $fields = array('subscription_id', 'time_start', 'time_end');

                // $sql = 'SELECT subscription_id, time_start, time_end FROM bo_push_subscription_schedule WHERE bridge_id = ' . ((int)$bridgeId) . ' AND day = ' . $day;

                $records = $tableManager->findRecords('bo_push_subscription_schedule', $criteria, $fields);

                $log->debug('Found ' . count($records) . ' record' . (count($records) != 1 ? 's' : '') . ' for bridge ' . $bridgeId . ' on ' . $time);

                if ($records) {

                    foreach ($records as $record) {

                        $subscriptionId = (int)$record['subscription_id'];
                        $timeStart = (int)$record['time_start'];
                        $timeEnd = (int)$record['time_end'];

                        if (($timeStart <= $timeOnDay) && ($timeEnd >= $timeOnDay)) {

                            $subscriptionIds[] = $subscriptionId;
                        }
                    }
                }

                $log->debug('Found ' . count($subscriptionIds) . ' matching subscription id' . (count($subscriptionIds) != 1 ? 's' : '') . ' for bridge ' . $bridgeId . ' on ' . $time);

                if ($subscriptionIds) {

                    $subscriptions = $this->loadSubscriptionsById($subscriptionIds);

                    $log->debug('Loaded ' . count($subscriptions) . ' subscription' . (count($subscriptions) != 1 ? 's' : '') . ' for bridge ' . $bridgeId . ' on ' . $time);

                    if ($subscriptions) {

                        foreach ($subscriptions as $subscription) {

                            $subscriptionId = $subscription->getId();

                            if ($subscription->getEndpoint() == '') {
                                continue;
                            }
                            if ($subscription->getAuthPublickey() == '') {
                                continue;
                            }
                            if ($subscription->getAuthToken() == '') {
                                continue;
                            }

                            $subscribers[$subscriptionId] = $subscription;
                        }
                    }

                    $log->debug('Found ' . count($subscribers) . ' suitable subscriber' . (count($subscribers) != 1 ? 's' : '') . ' for bridge ' . $bridgeId . ' on ' . $time);
                }
            }
        }

        return $subscribers;
    }

    /**
     * @param int[] $subscriptionIds
     * @return WebPushSubscription[]
     */
    public function loadSubscriptionsById($subscriptionIds)
    {

        $subscriptions = array();

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $criteria = array();
            $criteria['id'] = $subscriptionIds;

            $records = $tableManager->findRecords('bo_push_subscription', $criteria);

            if ($records) {

                foreach ($records as $record) {

                    $subscriptionId = (int)$record['id'];

                    $subscription = new WebPushSubscription();
                    $subscription->setId($subscriptionId);
                    $subscription->setGuid($record['guid']);
                    $subscription->setEndpoint($record['endpoint']);
                    $subscription->setExpirationTime($record['expiration_time']);
                    $subscription->setAuthPublickey($record['auth_publickey']);
                    $subscription->setAuthToken($record['auth_token']);
                    $subscription->setContentEncoding($record['content_encoding']);
                    $subscription->setDatetimeCreated($record['datetime_created']);
                    $subscription->setDatetimeModified($record['datetime_modified']);

                    $subscriptions[$subscriptionId] = $subscription;
                }
            }
        }

        return $subscriptions;
    }
}
