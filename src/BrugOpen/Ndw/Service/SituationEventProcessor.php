<?php
namespace BrugOpen\Service;

class SituationEventProcessor
{
   
    public function onSituationUpdate($situationId)
    {

        // load all situation version records

        // determine existing operationId

        // if no operationId, determine certainty
        // if certain, create operation
        // determine bridgeId
        // if no bridgeId, create and insert bridge
        // notify bridge event
        // update bridgeId in operation if needed

        // if operation update, trigger operation event

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
                    $values['time_end'] = new DateTime('@' . $actualEndDate);

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

    public function getCertainty($situations)
    {
        $certainty = null;

        foreach ($situations as $situation) {

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
