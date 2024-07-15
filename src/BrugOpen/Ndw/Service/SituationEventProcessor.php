<?php
namespace BrugOpen\Ndw\Service;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Model\Operation;
use BrugOpen\Service\BridgeIndexService;
use BrugOpen\Service\BridgeService;

class SituationEventProcessor
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
     * @var TableManager
     */
    private $tableManager;

    /**
     * @var BridgeIndexService
     */
    private $bridgeIndexService;

    /**
     * @var BridgeService
     */
    private $bridgeService;

    /**
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     *
     * @param \BrugOpen\Core\Context $context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }

    /**
     *
     * @return \BrugOpen\Db\Service\TableManager
     */
    public function getTableManager()
    {
        if ($this->tableManager == null) {

            $this->tableManager = $this->context->getService('BrugOpen.TableManager');

        }

        return $this->tableManager;
    }

    /**
     *
     * @param TableManager $tableManager
     */
    public function setTableManager($tableManager)
    {
        $this->tableManager = $tableManager;
    }

    /**
     * @return BridgeIndexService
     */
    public function getBridgeIndexService()
    {

        if ($this->bridgeIndexService == null) {

            if ($this->context != null) {

                $bridgeIndexService = new BridgeIndexService();
                $bridgeIndexService->initialize($this->context);
                $this->bridgeIndexService = $bridgeIndexService;

            }

        }

        return $this->bridgeIndexService;

    }

    /**
     * @param BridgeIndexService $bridgeIndexService
     */
    public function setBridgeIndexService($bridgeIndexService)
    {
        $this->bridgeIndexService = $bridgeIndexService;
    }

    /**
     * @return BridgeService
     */
    public function getBridgeService()
    {
        if ($this->bridgeService == null) {

            if ($this->context != null) {

                $bridgeService = new BridgeService();
                $bridgeService->initialize($this->context);

                $this->bridgeService = $bridgeService;

            }

        }

        return $this->bridgeService;
    }

    /**
     * @param BridgeService $bridgeService
     */
    public function setBridgeService($bridgeService)
    {
        $this->bridgeService = $bridgeService;
    }

    /**
     *
     * @return \BrugOpen\Core\EventDispatcher
     */
    public function getEventDispatcher()
    {
        if ($this->eventDispatcher == null) {

            if ($this->context != null) {

                $this->eventDispatcher = $this->context->getEventDispatcher();

            }
        }

        return $this->eventDispatcher;
    }

    /**
     *
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
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
     * @param \Psr\Log\LoggerInterface $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    public function onSituationUpdate($situationId)
    {

        $log = $this->getLog();

        // load all situation version records
        $allSituationVersions = $this->loadSituationVersions($situationId);

        $situationVersions = $allSituationVersions;

        if ($situationVersions) {

            // determine existing operationId

            $operationId = null;

            foreach ($situationVersions as $situationVersion) {

                if ($situationVersion['operation_id']) {

                    $operationId = (int)$situationVersion['operation_id'];
                    break;

                }

            }

            $lastSituationVersion = array_pop($situationVersions);

            $timeStart = null;
            $timeEnd = null;
            $timeGone = null;
            $certainty = null;

            if (array_key_exists('time_start', $lastSituationVersion)) {

                if ($lastSituationVersion['time_start']) {

                    $timeStart = $lastSituationVersion['time_start']->getTimestamp();

                }

            }

            if (array_key_exists('time_end', $lastSituationVersion)) {

                if ($lastSituationVersion['time_end']) {

                    $timeEnd = $lastSituationVersion['time_end']->getTimestamp();

                }

            }

            // if (array_key_exists('time_start', $lastSituationVersion)) {

            //     if ($lastSituationVersion['time_start']) {

            //         $timeGone = $lastSituationVersion['time_start']->getTimestamp();

            //     }

            // }

            $certainty = $this->getCertainty($allSituationVersions);

            $notifyOperationEvent = false;

            if ($operationId != null) {

                // if operationId,
                // check if operation updated
                // if so, trigger operation event

                $existingOperation = $this->loadOperation($operationId);

                if ($existingOperation) {

                    $operationTimeStart = null;
                    $operationTimeEnd = null;
                    $operationTimeGone = null;
                    $operationCertainty = null;

                    if (array_key_exists('time_start', $existingOperation)) {

                        if ($existingOperation['time_start']) {

                            $operationTimeStart = $existingOperation['time_start']->getTimestamp();

                        }

                    }

                    if (array_key_exists('time_end', $existingOperation)) {

                        if ($existingOperation['time_end']) {

                            $operationTimeEnd = $existingOperation['time_end']->getTimestamp();

                        }

                    }

                    if (array_key_exists('time_start', $existingOperation)) {

                        if ($existingOperation['time_start']) {

                            $operationTimeGone = $existingOperation['time_start']->getTimestamp();

                        }

                    }

                    if (array_key_exists('certainty', $existingOperation)) {

                        if ($existingOperation['certainty']) {

                            $operationCertainty = $existingOperation['certainty'];

                        }

                    }

                    $operationNeedsUpdate = false;

                    if ($operationTimeStart != $timeStart) {

                        $operationNeedsUpdate = true;

                    }

                    if ($operationTimeEnd != $timeEnd) {

                        $operationNeedsUpdate = true;

                    }

                    if ($operationTimeGone != $timeGone) {

                        $operationNeedsUpdate = true;

                    }

                    if ($operationCertainty != $certainty) {

                        $operationNeedsUpdate = true;

                    }

                    if ($operationNeedsUpdate) {

                        $log->info('Updating operation ' . $operationId);

                        $bridgeId = null;

                        $this->updateOperation($operationId, null, $timeStart, $timeEnd, $timeGone, $certainty, $bridgeId);

                        $notifyOperationEvent = true;

                    }

                } else {

                    $log->error('Could not load operation ' . $operationId);

                }

            } else {

                // if no operationId, determine certainty
                $certainty = $this->getCertainty($lastSituationVersion);

                if ($certainty == Operation::CERTAINTY_CERTAIN) {

                    $ndwLocationId = null;
                    $isrs = null;

                    // determine bridgeId

                    $bridgeId = null;

                    $bridgeIndexService = $this->getBridgeIndexService();

                    if ($bridgeIndexService != null) {

                        if (array_key_exists('location', $lastSituationVersion)) {

                            if ($lastSituationVersion['location']) {

                                if (preg_match('/^[1-9]+[0-9]*$/', $lastSituationVersion['location'])) {

                                    $ndwLocationId = $lastSituationVersion['location'];

                                }

                            }

                        }

                        $isrsFromSituationId = $this->getIsrsFromEventId($situationId);

                        if ($isrsFromSituationId) {

                            $isrs = $isrsFromSituationId;

                        }

                        if ($ndwLocationId) {

                            $bridgeByNdwId = $bridgeIndexService->getBridgeIdByNdwLocationId($ndwLocationId);

                            if ($bridgeByNdwId) {

                                $bridgeId = $bridgeByNdwId;

                            }

                        }

                        if ($bridgeId == null) {

                            if ($isrs) {

                                $bridgeIdByIsrs = $bridgeIndexService->getBridgeIdByIsrs($isrs);

                                if ($bridgeIdByIsrs) {

                                    $bridgeId = $bridgeIdByIsrs;

                                }

                            }

                        }

                    }

                    if ($bridgeId == null) {

                        if ($isrs || $ndwLocationId) {

                            // if no bridgeId, create and insert bridge

                            $bridgeService = $this->getBridgeService();

                            if ($bridgeService) {

                                $latLng = null;

                                $lat = null;
                                $lng = null;

                                if (array_key_exists('lat', $lastSituationVersion)) {

                                    $lat = $lastSituationVersion['lat'];

                                }

                                if (array_key_exists('lng', $lastSituationVersion)) {

                                    $lng = $lastSituationVersion['lng'];

                                }

                                if ($lat && $lng) {

                                    $latLng = new LatLng($lat, $lng);

                                }

                                $log->info('Inserting bridge for ' . ($isrs ? ('isrs ' . $isrs) : ('ndw id ' . $ndwLocationId)));

                                $insertedBridge = $bridgeService->insertBridgeFromNdwData($ndwLocationId, $isrs, $latLng);

                                if ($insertedBridge) {

                                    $bridgeId = $insertedBridge->getId();

                                    if ($bridgeId) {

                                        // update index

                                        if ($isrs) {

                                            if ($bridgeIndexService) {

                                                $bridgeIndexService->addBridgeIsrs($bridgeId, $isrs);

                                            }

                                        }

                                        // post bridge insert event
                                        $eventDispatcher = $this->getEventDispatcher();

                                        if ($eventDispatcher) {

                                            $eventDispatcher->postEvent('Bridge.insert', array($bridgeId));

                                        }

                                    } else {

                                        $log->error('Inserted bridge for ' . ($isrs ? ('isrs ' . $isrs) : ('ndw id ' . $ndwLocationId)) . ' does not have bridge id');

                                    }

                                } else {

                                    $log->error('Could not insert bridge for ' . ($isrs ? ('isrs ' . $isrs) : ('ndw id ' . $ndwLocationId)));

                                }

                            }

                        }

                    }

                    $log->info('Creating operation for ' . $situationId);

                    // insert operation
                    $operationId = $this->updateOperation(null, $situationId, $timeStart, $timeEnd, $timeGone, $certainty, $bridgeId);

                    if ($operationId) {

                        // notify operation event
                        $notifyOperationEvent = true;

                    }

                }

            }

            // update operationId in situation versions if needed

            if ($operationId) {

                $tableManager = $this->getTableManager();

                if ($allSituationVersions && $tableManager) {

                    foreach ($allSituationVersions as $situationVersion) {

                        if ($situationVersion['operation_id']) {
                            continue;
                        }

                        $keys = array();
                        $keys['id'] = $situationVersion['id'];
                        $keys['version'] = $situationVersion['version'];

                        $values['operation_id'] = $operationId;

                        $tableManager->updateRecords('bo_situation', $values, $keys);

                    }

                }

            }

            if ($notifyOperationEvent) {

                if ($operationId) {

                    $eventDispatcher = $this->getEventDispatcher();

                    if ($eventDispatcher) {

                        $eventDispatcher->postEvent('Operation.update', array($operationId));

                    }

                }

            }

        }

    }

    /**
     * @param string $situationId
     * @return array[]
     */
    public function loadSituationVersions($situationId)
    {
        $situationVersions = array();

        if ($situationId) {

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                $criteria = array();
                $criteria['id'] = $situationId;

                $order = array();
                $order[] = 'version';
                $order[] = 'asc';

                $orders = array();
                $orders[] = $order;

                $records = $tableManager->findRecords('bo_situation', $criteria, null, $orders);

                if ($records) {

                    $situationVersions = $records;

                }

            }

        }

        return $situationVersions;
    }

    /**
     * @param int $operationId
     * @return array
     */
    public function loadOperation($operationId)
    {

        $operation = null;

        if ($operationId) {

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                $criteria = array();
                $criteria['id'] = $operationId;

                $record = $tableManager->findRecord('bo_operation', $criteria);

                if ($record) {

                    $operation = $record;

                }

            }

        }

        return $operation;

    }

    /**
     * @param int $operationId
     * @param string $situationId
     * @param int $timeStart
     * @param int $timeEnd
     * @param int $timeGone
     * @param int $certainty
     * @param int $bridgeId
     */
    public function updateOperation($operationId, $situationId, $timeStart, $timeEnd, $timeGone, $certainty, $bridgeId)
    {
        $res = null;

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $values = array();

            $values['datetime_start'] = null;
            $values['time_start'] = null;
            $values['datetime_end'] = null;
            $values['time_end'] = null;
            $values['datetime_gone'] = null;
            $values['time_gone'] = null;
            $values['certainty'] = null;

            if ($situationId) {

                $values['event_id'] = $situationId;

            }

            if ($timeStart) {

                $values['datetime_start'] = $timeStart;
                $values['time_start'] = new \DateTime('@' . $timeStart);

            }

            if ($timeEnd) {

                $values['datetime_end'] = $timeEnd;
                $values['time_end'] = new \DateTime('@' . $timeEnd);

            }

            if ($timeGone) {

                $values['datetime_gone'] = $timeGone;
                $values['time_gone'] = new \DateTime('@' . $timeGone);

            }

            if ($certainty !== null) {

                $values['certainty'] = $certainty;

            }

            if ($bridgeId !== null) {

                $values['bridge_id'] = $bridgeId;

            }

            if ($operationId) {

                $criteria = array();
                $criteria['id'] = $operationId;

                $tableManager->updateRecords('bo_operation', $values, $criteria);

                $res = $operationId;

            } else {

                $values['current'] = 1;

                $res = $tableManager->insertRecord('bo_operation', $values);

            }

        }

        return $res;

    }

    /**
     * @param string $eventId
     * @return string|null
     */
    public function getIsrsFromEventId($eventId)
    {
        $isrs = null;

        if ($eventId != '') {

            $matches = array();

            if (preg_match('/^([^\_]+)\_([^\_]+)\_([^\_]+)$/', $eventId, $matches)) {

                $isrs = $matches[2];
            }
        }

        return $isrs;
    }

    /**
     * @param array|array[] $situations
     * @return int
     */
    public function getCertainty($situations)
    {
        $certainty = null;

        $lastSituation = null;

        if (is_array($situations)) {

            if (array_key_exists('probability', $situations) || array_key_exists('status', $situations)) {

                $lastSituation = $situations;

            } else {

                foreach ($situations as $situation) {

                    if (array_key_exists('probability', $situation) || array_key_exists('status', $situation)) {

                        $lastSituation = $situation;

                    }

                }

            }

        }

        if ($lastSituation) {

            $situation = $lastSituation;

            if (isset($situation['probability'])) {
                if ($situation['probability'] == 'certain') {
                    $certainty = 3;
                } else if ($situation['probability'] == 'probable') {
                    $certainty = 2;
                } else if ($situation['probability'] == 'riskOf') {
                    $certainty = 1;
                }
            }

            if (isset($situation['status'])) {
                if ($situation['status'] == 'cancelled') {
                    $certainty = 0;
                }
            }
        }

        return $certainty;
    }

}
