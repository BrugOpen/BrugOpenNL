<?php
namespace BrugOpen\Ndw\Service;

use BrugOpen\Db\Service\DatabaseTableManager;
use BrugOpen\Model\Operation;
use BrugOpen\Service\BridgeIndexService;

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

            $connectionManager = $this->context->getDatabaseConnectionManager();
            $connection = $connectionManager->getConnection();
            $tableManager = new DatabaseTableManager($connection);

            $this->tableManager = $tableManager;
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

        // load all situation version records
        $situationVersions = $this->loadSituationVersions($situationId);

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

            if (array_key_exists('time_start', $lastSituationVersion)) {

                if ($lastSituationVersion['time_start']) {

                    $timeGone = $lastSituationVersion['time_start']->getTimestamp();

                }

            }

            if (array_key_exists('certainty', $lastSituationVersion)) {

                if ($lastSituationVersion['certainty']) {

                    // FIXME use getCertainty()
                    $certainty = $lastSituationVersion['certainty'];

                }

            }

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

                        $bridgeId = null;

                        $this->updateOperation($operationId, $timeStart, $timeEnd, $timeGone, $certainty, $bridgeId);

                        $notifyOperationEvent = true;

                    }

                }

            } else {

                // if no operationId, determine certainty
                $certainty = $this->getCertainty($lastSituationVersion);

                if ($certainty == Operation::CERTAINTY_CERTAIN) {

                    // determine bridgeId

                    $bridgeId = null;

                    $bridgeIndexService = $this->getBridgeIndexService();

                    if ($bridgeIndexService != null) {

                        $ndwLocationId = null;
                        $isrs = null;

                        if (array_key_exists('location', $lastSituationVersion)) {

                            if ($lastSituationVersion['location']) {

                                $ndwLocationId = $lastSituationVersion['location'];

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

                        // if no bridgeId, create and insert bridge
                        // notify bridge event

                    }

                    // insert operation
                    $operationId = $this->updateOperation(null, $timeStart, $timeEnd, $timeGone, $certainty, $bridgeId);

                    if ($operationId) {

                        // notify operation event
                        $notifyOperationEvent = true;

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
     * 
     */
    public function updateOperation($operationId, $timeStart, $timeEnd, $timeGone, $certainty, $bridgeId)
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

                $tableManager->updateRecords('bo_operation', $values);

                $res = $operationId;

            } else {

                $res = $tableManager->insertRecord('bo_operation', $values);

            }

        }

        return $res;

    }

    public function populateOperationsInSituations()
    {
        $sql = 'UPDATE bo_situation SET operation_id = (SELECT id FROM bo_operation WHERE bo_operation.event_id = bo_situation.id) WHERE operation_id IS NULL';
        $this->dataStore->executeQuery($sql);
    }

    public function insertMissingOperations()
    {
        $sql = "INSERT IGNORE INTO bo_operation (event_id, datetime_start, time_start) SELECT id, datetime_start, time_start FROM bo_situation WHERE operation_id IS NULL AND probability = 'certain' ORDER BY id, time_start DESC";
        $this->dataStore->executeQuery($sql);
    }

    public function insertMissingBridges()
    {

        // insert missing bridges by NDW id
        $sql = "SELECT DISTINCT location, lat, lng FROM bo_situation WHERE location <> '' AND location REGEXP '^[0-9]+$' AND location NOT IN (SELECT distinct ndw_id FROM bo_bridge)";

        if ($result = $this->dataStore->executeQuery($sql)) {
            while ($row = $result->fetch_assoc()) {

                $ndwId = $row['location'];
                $existingBridge = $this->findBridgeByNdwId($ndwId);
                if (! $existingBridge) {

                    $values = array();
                    $values['ndw_id'] = $ndwId;
                    $values['ndw_lat'] = $row['lat'];
                    $values['ndw_lng'] = $row['lng'];

                    $this->dataStore->insertRecord('bo_bridge', $values);

                    if ($newBridge = $this->findBridgeByNdwId($ndwId)) {

                        $this->notifyNewBridge($newBridge['id']);
                    }
                }
            }
        }

        // insert missing bridges by isrs_code
        $sql = "SELECT DISTINCT location, lat, lng FROM bo_situation WHERE location <> '' AND location NOT REGEXP '^[0-9]+$' AND location NOT IN (SELECT distinct isrs_code FROM bo_bridge WHERE isrs_code IS NOT NULL) AND location NOT IN (SELECT distinct isrs_code FROM bo_bridge_isrs)";

        if ($result = $this->dataStore->executeQuery($sql)) {
            while ($row = $result->fetch_assoc()) {

                $isrs = $row['location'];
                $existingBridge = $this->findBridgeByIsrs($isrs);
                if (! $existingBridge) {

                    $values = array();
                    $values['isrs_code'] = $isrs;
                    $values['ndw_lat'] = $row['lat'];
                    $values['ndw_lng'] = $row['lng'];

                    $this->dataStore->insertRecord('bo_bridge', $values);

                    if ($newBridge = $this->findBridgeByIsrs($isrs)) {

                        $bridgeId = $newBridge['id'];

                        $values = array();
                        $values['isrs_code'] = $isrs;
                        $values['bridge_id'] = $bridgeId;

                        $this->dataStore->insertRecord('bo_bridge_isrs', $values);

                        $this->notifyNewBridge($newBridge['id']);
                    }
                }
            }
        }

        // update bridge_id from NDW id where needed
        $sql = "UPDATE bo_operation SET bridge_id = (SELECT id FROM bo_bridge WHERE ndw_id = (SELECT location FROM bo_situation WHERE bo_situation.id = bo_operation.event_id LIMIT 1)) WHERE bridge_id IS NULL AND (SELECT location FROM bo_situation WHERE bo_situation.id = bo_operation.event_id LIMIT 1) REGEXP '^[0-9]+$'";
        $this->dataStore->executeQuery($sql);

        // update bridge_id from ISRS where needed
        $sql = "UPDATE bo_operation SET bridge_id = (SELECT bridge_id FROM bo_bridge_isrs WHERE isrs_code = (SELECT location FROM bo_situation WHERE bo_situation.id = bo_operation.event_id LIMIT 1))
        WHERE bridge_id IS NULL AND (SELECT COUNT(*) FROM bo_situation WHERE bo_situation.id = bo_operation.event_id AND location <> '' AND location NOT REGEXP '^[0-9]+$') > 0";
        $this->dataStore->executeQuery($sql);
    }

    public function findOperationsWithoutBridge()
    {
        $operationsWithoutBridge = array();

        $sql = "SELECT id, event_id FROM bo_operation WHERE bridge_id IS NULL";

        $operationsByEventId = array();

        if ($res = $this->dataStore->executeQuery($sql)) {

            while ($row = $res->fetch_assoc()) {

                $operationId = $row['id'];
                $eventId = $row['event_id'];

                if (($operationId > 0) && ($eventId != '')) {

                    $operationsByEventId[$eventId][] = $operationId;
                }
            }
        }

        if (sizeof($operationsByEventId)) {

            $eventIdParts = array();

            foreach (array_keys($operationsByEventId) as $eventId) {

                $eventIdParts[] = "'" . addslashes($eventId) . "'";
            }

            $sql = "SELECT DISTINCT id, location, lat, lng FROM bo_situation WHERE id IN (" . implode(',', $eventIdParts) . ")";

            if ($res = $this->dataStore->executeQuery($sql)) {

                while ($row = $res->fetch_assoc()) {

                    $eventId = $row['id'];
                    $location = $row['location'];

                    if ($location == '') {
                        continue;
                    }

                    if (array_key_exists($eventId, $operationsByEventId)) {

                        foreach ($operationsByEventId[$eventId] as $operationId) {

                            $operation = array();
                            $operation['id'] = $operationId;
                            $operation['event_id'] = $eventId;
                            $operation['location'] = $location;
                            $operation['lat'] = $row['lat'];
                            $operation['lng'] = $row['lng'];

                            $operationsWithoutBridge[] = $operation;
                        }
                    }
                }
            }
        }

        return $operationsWithoutBridge;
    }

    public function updateBridgeInOperation($operationId, $bridgeId)
    {
        if (($operationId > 0) && ($bridgeId > 0)) {

            $keys = array();
            $keys['id'] = $operationId;

            $values = array();
            $values['bridge_id'] = $bridgeId;

            $this->dataStore->updateTable('bo_operation', $keys, $values);
        }
    }

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


    public function processSituations()
    {
        // first set operation_id in bo_situation
        $this->populateOperationsInSituations();

        // now insert missing operations
        $this->insertMissingOperations();

        // now populate empty operation ids again
        $this->populateOperationsInSituations();

        if ($operationsWithoutBridge = $this->findOperationsWithoutBridge()) {

            echo 'found ' . sizeof($operationsWithoutBridge) . ' operations without bridge' . "\n";

            foreach ($operationsWithoutBridge as $operation) {

                $location = $operation['location'];
                $operationId = $operation['id'];

                if ($location == '') {
                    continue;
                }

                $bridgeId = null;
                $bridge = null;
                $isrs = null;

                if (preg_match('/^[0-9]+$/', $location)) {

                    $bridge = $this->findBridgeByNdwId($location);
                } else {

                    $bridge = $this->findBridgeByIsrs($location);
                }

                if ($bridge == null) {

                    // check if event id contains ISRS
                    if ($isrs = $this->getIsrsFromEventId($operation['event_id'])) {

                        $bridge = $this->findBridgeByIsrs($isrs);
                    }
                }

                if ($bridge != null) {
                    $bridgeId = $bridge['id'];
                }

                if ($bridgeId > 0) {

                    // update bridge in operation
                    $this->updateBridgeInOperation($operationId, $bridgeId);
                } else {

                    // insert missing bridge

                    if (($location != '') || ($isrs != '')) {

                        $values = array();

                        if ($location != '') {
                            $values['ndw_id'] = $location;
                        }

                        if ($isrs != '') {
                            $values['isrs_code'] = $isrs;
                        }

                        if ($operation['lat'] != '') {
                            $values['ndw_lat'] = $operation['lat'];
                        }

                        if ($operation['lng'] != '') {
                            $values['ndw_lng'] = $operation['lng'];
                        }

                        $bridgeId = $this->dataStore->insertRecord('bo_bridge', $values);

                        if ($bridgeId) {

                            if ($isrs != '') {

                                $values = array();
                                $values['bridge_id'] = $bridgeId;
                                $values['isrs_code'] = $isrs;

                                $this->dataStore->insertRecord('bo_bridge_isrs', $values);

                                $this->notifyNewBridge($bridgeId);
                            }
                        }
                    }
                }
            }
        }

        // loop through all unfinished operations
        $keys = array();
        $keys['finished'] = 0;
        $activeOperations = $this->dataStore->findRecords('bo_operation', $keys);

        foreach ($activeOperations as $activeOperation) {
            // find actual end date if possible

            $situationId = $activeOperation['event_id'];

            $situations = $this->findSituations($situationId);

            $actualEndDate = $this->getEndDate($situations);
            $certainty = $this->getCertainty($situations);

            if (($actualEndDate > 0) || ($certainty != null)) {

                $values = array();

                if ($actualEndDate > 0) {

                    $values['datetime_end'] = $actualEndDate;
                    $values['time_end'] = new \DateTime('@' . $actualEndDate);

                    if ($actualEndDate < time()) {
                        $values['finished'] = 1;
                    }
                }

                if ($certainty !== null) {
                    $values['certainty'] = $certainty;
                }

                $this->dataStore->updateTable('bo_operation', array(
                    'id' => $activeOperation['id']
                ), $values);

                // try to find approaches that are in this operation timespan
                $bridgeId = $activeOperation['bridge_id'];
                if ($bridgeId > 0) {

                    $operationId = $activeOperation['id'];
                    $operationStart = $activeOperation['datetime_start'];
                    $this->checkOperationApproaches($bridgeId, $operationId, $operationStart, $actualEndDate);
                }
            }
        }
    }

    public function findSituations($situationId)
    {
        $keys = array();
        $keys['id'] = $situationId;

        return $this->dataStore->findRecords('bo_situation', $keys, array(
            'version'
        ));
    }

    public function getEndDate($situations)
    {
        $endDate = null;

        foreach ($situations as $situation) {

            if ($situation['datetime_end'] != '') {
                $endDate = $situation['datetime_end'];
            }
        }

        return $endDate;
    }

    public function getLastPublicationDate($situations)
    {
        $publicationDate = null;

        foreach ($situations as $situation) {

            if ($situation['last_publication'] != '') {
                $publicationDate = $situation['last_publication'];
            }
        }

        return $publicationDate;
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
