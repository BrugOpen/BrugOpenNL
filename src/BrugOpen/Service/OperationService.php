<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Db\Model\CriteriumFieldComparison;
use BrugOpen\Model\Operation;

class OperationService
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     * @var TableManager
     */
    private $tableManager;

    /**
     *
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
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
     *
     * @param int[] $operationIds
     * @return Operation[]
     */
    public function loadOperationsById($operationIds)
    {

        $operations = array();

        if ($operationIds) {

            $cleanIds = array();

            foreach ($operationIds as $operationId) {
                $cleanIds[] = (int) $operationId;
            }

            if ($cleanIds) {

                $tableManager = $this->getTableManager();

                $criteria = array();
                $criteria['id'] = $cleanIds;

                $rows = $tableManager->findRecords('bo_operation', $criteria);

                if ($rows) {

                    foreach ($rows as $row) {

                        $operationId = (int)$row['id'];

                        $datetimeStart = $row['time_start'];
                        $datetimeEnd = $row['time_end'];

                        $operation = new Operation();
                        $operation->setId($operationId);
                        $operation->setEventId($row['event_id']);
                        $operation->setBridgeId((int)$row['bridge_id']);
                        $operation->setCertainty((int)$row['certainty']);
                        $operation->setDateTimeStart($datetimeStart);
                        $operation->setDateTimeEnd($datetimeEnd);
                        $operation->setFinished($row['finished']);

                        $operations[$operationId] = $operation;
                    }
                }
            }
        }

        return $operations;
    }

    public function initializeCurrentOperations()
    {

        $context = $this->context;

        $logger = $context->getLogRegistry()->getLog($this);

        $connection = $context->getDatabaseConnectionManager()->getConnection();

        $bridgeIds = array();

        $stmt = $connection->query('SELECT id FROM bo_bridge');

        if ($stmt) {

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                $bridgeId = (int)$row['id'];
                $bridgeIds[] = $bridgeId;
            }
        }

        $midnight = new \DateTimeImmutable('midnight');

        $oneYearAgo = $midnight->sub(new \DateInterval('P1Y'));
        $oneWeekAgo = $midnight->sub(new \DateInterval('P1W'));

        foreach ($bridgeIds as $bridgeId) {

            $currentOperations = array();

            // collect certain operations with finished = 1 from last week

            $params = array();
            $params['bridgeId'] = $bridgeId;
            $params['oneWeekAgo'] = $oneWeekAgo->getTimestamp();

            $stmt = $connection->prepare('SELECT id FROM bo_operation WHERE bridge_id = :bridgeId AND time_start >= FROM_UNIXTIME(:oneWeekAgo) AND finished = 1 AND certainty = 3 AND current IS NULL');

            if ($stmt) {

                if ($stmt->execute($params)) {

                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                        $operationId = (int)$row['id'];

                        $currentOperations[$operationId] = $operationId;
                    }
                }
            }

            // collect last 10 certain operations with finished = 1 from last year

            $params = array();
            $params['bridgeId'] = $bridgeId;
            $params['oneYearAgo'] = $oneYearAgo->getTimestamp();

            $stmt = $connection->prepare('SELECT id, current FROM bo_operation WHERE bridge_id = :bridgeId AND time_start >= FROM_UNIXTIME(:oneYearAgo) AND finished = 1 AND certainty = 3 ORDER BY time_start DESC LIMIT 10');

            if ($stmt) {

                if ($stmt->execute($params)) {

                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                        $operationId = (int)$row['id'];

                        $current = (int)$row['current'];

                        if (!$current) {

                            $currentOperations[$operationId] = $operationId;
                        }
                    }
                }
            }

            // collect all operations with finished = 0

            $params = array();
            $params['bridgeId'] = $bridgeId;

            $stmt = $connection->prepare('SELECT id, current FROM bo_operation WHERE bridge_id = :bridgeId AND finished = 0');

            if ($stmt) {

                if ($stmt->execute($params)) {

                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                        $operationId = (int)$row['id'];

                        $current = (int)$row['current'];

                        if (!$current) {

                            $currentOperations[$operationId] = $operationId;
                        }
                    }
                }
            }

            if ($currentOperations) {

                $updateOperationIds = array();

                if ($stmt) {

                    foreach ($currentOperations as $operationId) {

                        $stmt = $connection->prepare('SELECT id FROM bo_operation WHERE id = :operationId AND current IS NULL');

                        $params = array();
                        $params['operationId'] = $operationId;

                        if ($stmt->execute($params)) {

                            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                                $operationId = (int)$row['id'];

                                $updateOperationIds[] = $operationId;
                            }
                        }
                    }
                }

                if ($updateOperationIds) {

                    $logger->info('Marking ' . count($updateOperationIds) . ' operation' . (count($updateOperationIds) != 1 ? 's' : '') . ' for bridge ' . $bridgeId . ' current');

                    $stmt = $connection->prepare('UPDATE bo_operation SET current = 1 WHERE id = :operationId AND current IS NULL');

                    foreach ($updateOperationIds as $operationId) {

                        $logger->info('Marking operation ' . $operationId . ' for bridge ' . $bridgeId . ' current');

                        $params = array();
                        $params['operationId'] = $operationId;

                        if ($stmt) {

                            $stmt->execute($params);
                        }
                    }
                } else {

                    $logger->info("No new operations found for bridge " . $bridgeId . " to be marked current");
                }
            }
        }
    }

    /**
     *
     * @return Operation[][]
     */
    public function loadCurrentOperationsByBridge()
    {

        $currentOperationsByBridge = array();

        $sql = 'SELECT id, event_id, bridge_id, UNIX_TIMESTAMP(time_start) AS datetime_start, UNIX_TIMESTAMP(time_end) AS datetime_end, certainty, finished FROM bo_operation WHERE current = 1';

        if ($results = $this->context->getDataStore()->executeQuery($sql)) {

            while ($row = $results->fetch_assoc()) {

                $operationId = (int)$row['id'];

                $datetimeStart = null;
                $datetimeEnd = null;

                if ($row['datetime_start'] > 0) {
                    $datetimeStart = new \DateTime();
                    $datetimeStart->setTimestamp($row['datetime_start']);
                }

                if ($row['datetime_end'] > 0) {
                    $datetimeEnd = new \DateTime();
                    $datetimeEnd->setTimestamp($row['datetime_end']);
                }

                $bridgeId = (int)$row['bridge_id'];

                $operation = new Operation();
                $operation->setId($operationId);
                $operation->setEventId($row['event_id']);
                $operation->setBridgeId($bridgeId);
                $operation->setCertainty((int)$row['certainty']);
                $operation->setDateTimeStart($datetimeStart);
                $operation->setDateTimeEnd($datetimeEnd);
                $operation->setFinished($row['finished']);

                $currentOperationsByBridge[$bridgeId][$operationId] = $operation;
            }
        }

        return $currentOperationsByBridge;
    }

    /**
     * @param Operation[] $currentBridgeOperations
     * @param int $time
     */
    public function collectLastWeekOperations($currentBridgeOperations, $time = null)
    {

        $lastWeekBridgeOperations = array();

        if ($time == null) {

            $time = time();
        }

        $midnight = new \DateTimeImmutable(date('Y-m-d 00:00:00', $time));
        $oneWeekAgo = $midnight->sub(new \DateInterval('P1W'))->getTimestamp();

        if ($currentBridgeOperations) {

            // certainty = 3
            // finished = 1
            // end > $oneWeekAgo

            foreach ($currentBridgeOperations as $operation) {

                if ($operation->getCertainty() != 3) {
                    continue;
                }

                if (!$operation->isFinished()) {
                    continue;
                }

                $dateTimeEnd = $operation->getDateTimeEnd();

                if ($dateTimeEnd) {

                    $timeEnd = $dateTimeEnd->getTimestamp();

                    if ($timeEnd >= $oneWeekAgo) {

                        $lastWeekBridgeOperations[] = $operation;
                    }
                }
            }
        }

        return $lastWeekBridgeOperations;
    }

    /**
     * @param Operation[] $lastWeekBridgeOperations
     */
    public function collectLastWeekStats($lastWeekBridgeOperations)
    {

        $stats = array();

        // count number of times open during last week
        $numOperations = count($lastWeekBridgeOperations);

        $averageOperationTime = null;

        // determine average open time last week
        $operationTimes = array();

        $numOperationsPeakMorning = 0;
        $numOperationsPeakEvening = 0;

        if ($lastWeekBridgeOperations) {

            foreach ($lastWeekBridgeOperations as $operation) {

                $dateTimeStart = $operation->getDateTimeStart();
                $dateTimeEnd = $operation->getDateTimeEnd();

                if ($dateTimeStart && $dateTimeEnd) {

                    $timeStart = $dateTimeStart->getTimestamp();
                    $timeEnd = $dateTimeEnd->getTimestamp();

                    if ($timeEnd > $timeStart) {

                        $operationTime = $timeEnd - $timeStart;

                        $operationTimes[] = $operationTime;

                        $startDay = date('N', $timeStart);

                        if (($startDay >= 1) && ($startDay <= 5)) {

                            // mon - fri

                            $startHour = date('G', $timeStart) + (date('i', $timeStart) / 60);
                            $endHour = date('G', $timeEnd) + (date('i', $timeEnd) / 60);

                            if (($startHour >= 7) && ($startHour < 9)) {

                                $numOperationsPeakMorning++;
                            } else if (($endHour >= 7) && ($endHour < 9)) {

                                $numOperationsPeakMorning++;
                            }

                            if (($startHour >= 16.5) && ($startHour < 18.5)) {

                                $numOperationsPeakEvening++;
                            } else if (($endHour >= 16.5) && ($endHour < 18.5)) {

                                $numOperationsPeakEvening++;
                            }
                        }
                    }
                }
            }

            if ($operationTimes) {

                $averageOperationTime = round(array_sum($operationTimes) / count($operationTimes));
            }
        }

        $stats['num'] = $numOperations;
        $stats['avgTime'] = $averageOperationTime;
        $stats['numMorning'] = $numOperationsPeakMorning;
        $stats['numEvening'] = $numOperationsPeakEvening;

        return $stats;
    }

    /**
     * @param Operation[] $sortedBridgeOperations
     * @param int $maxOperations
     * @param int $startedBeforeTime
     */
    public function collectLastStartedOperations($sortedBridgeOperations, $maxOperations = 10, $startedBeforeTime = null)
    {

        $lastStartedOperations = array();

        if ($sortedBridgeOperations) {

            if ($startedBeforeTime == null) {

                $startedBeforeTime = time();
            }

            for ($i = count($sortedBridgeOperations) - 1; $i >= 0; $i--) {

                $operation = $sortedBridgeOperations[$i];

                if ($operation->getCertainty() != 3) {
                    continue;
                }

                $dateTimeStart = $operation->getDateTimeStart();

                if ($dateTimeStart) {

                    $timeStart = $dateTimeStart->getTimestamp();

                    if ($timeStart <= $startedBeforeTime) {

                        $lastStartedOperations[] = $operation;
                    }
                }

                if (count($lastStartedOperations) == $maxOperations) {
                    break;
                }
            }
        }

        return $lastStartedOperations;
    }

    /**
     * @param Operation[] $sortedBridgeOperations
     * @param int $time
     * @return Operation|null
     */
    public function collectNextStartingOperation($sortedBridgeOperations, $time = null)
    {

        $nextStartingOperation = null;

        if ($sortedBridgeOperations) {

            if ($time == null) {

                $time = time();
            }

            foreach ($sortedBridgeOperations as $operation) {

                $dateTimeStart = $operation->getDateTimeStart();

                if ($dateTimeStart) {

                    $timeStart = $dateTimeStart->getTimestamp();

                    if ($timeStart >= $time) {

                        if ($operation->getCertainty() >= 2) {

                            $nextStartingOperation = $operation;
                            break;
                        }
                    }
                }
            }
        }

        return $nextStartingOperation;
    }

    /**
     * @param Operation[] $operations
     * @return Operation[]
     */
    public function sortOperationsByStartTime($operations)
    {

        $sortedOperations = array();

        $operationsByStartTime = array();

        if ($operations) {

            $now = time();

            foreach ($operations as $operation) {

                $startTime = $operation->getDateTimeStart();

                if ($startTime) {

                    $key = $startTime->getTimestamp();
                } else {

                    $key = $now;
                }

                if (!array_key_exists($key, $operationsByStartTime)) {

                    $operationsByStartTime[$key] = array();
                }

                $operationsByStartTime[$key][] = $operation;
            }

            ksort($operationsByStartTime);

            foreach (array_keys($operationsByStartTime) as $key) {

                foreach ($operationsByStartTime[$key] as $operation) {

                    $sortedOperations[] = $operation;
                }
            }
        }

        return $sortedOperations;
    }

    /**
     * @param int[] $operationIds
     * @return int[][]
     */
    public function loadShipTypesByOperationId($operationIds)
    {

        $shipTypesByOperationId = array();

        $batchSize = 100;

        $batches = array();
        $batch = array();

        foreach ($operationIds as $operationId) {

            $batch[] = $operationId;

            if (count($batch) == $batchSize) {

                $batches[] = $batch;
                $batch = array();
            }
        }

        if (count($batch)) {

            $batches[] = $batch;
            $batch = array();
        }

        if ($batches) {

            $connection = $this->context->getDatabaseConnectionManager()->getConnection();

            foreach ($batches as $batch) {

                $stmt = $connection->prepare('SELECT operation_id, vessel_type FROM bo_bridge_passage WHERE operation_id IN (' . implode(',', $batch) . ')');

                if ($stmt->execute()) {

                    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                        $operationId = (int)$row['operation_id'];
                        $vesselType = (int)$row['vessel_type'];

                        if ($operationId && $vesselType) {

                            $shipTypesByOperationId[$operationId][] = $vesselType;
                        }
                    }
                }
            }
        }

        return $shipTypesByOperationId;
    }

    /*
    public function getDefaultPeakHours()
    {

        $peakHours = array();

        for ($day = 1; $day <= 5; $day++) {

            $peakHour = array();
            $peakHour['day'] = $day;
            $peakHour['start'] = array(7,0);
            $peakHour['end']   = array(9,0);

            $peakHours[] = $peakHour;

            $peakHour = array();
            $peakHour['day'] = $day;
            $peakHour['start'] = array(16,30);
            $peakHour['end']   = array(18,30);

            $peakHours[] = $peakHour;

        }

        return $peakHours;

    }
    */

    public function updateCurrentOperations()
    {

        $context = $this->context;

        $connection = $context->getDatabaseConnectionManager()->getConnection();

        $logger = $context->getLogRegistry()->getLog($this);

        // collect existing current operations by bridge

        $currentOperationsByBridge = $this->loadCurrentOperationsByBridge();

        foreach ($currentOperationsByBridge as $bridgeId => $existingCurrentOperations) {

            $currentOperations = $this->getCurrentOperations($existingCurrentOperations);

            $goneOperations = array();

            foreach ($existingCurrentOperations as $operation) {

                $operationId = $operation->getId();

                if (!array_key_exists($operationId, $currentOperations)) {

                    $goneOperations[$operationId] = $operation;
                }
            }

            if ($goneOperations) {

                $logger->info('Removing ' . count($goneOperations) . ' operation' . (count($goneOperations) != 1 ? 's' : '') . ' from bridge ' . $bridgeId . ' current operations');

                $stmt = $connection->prepare('UPDATE bo_operation SET current = NULL WHERE id = :operationId');

                if ($stmt) {

                    foreach ($goneOperations as $goneOperation) {

                        $goneOperationId = $goneOperation->getId();

                        $logger->info('Removing operation ' . $goneOperationId . ' from bridge ' . $bridgeId . ' current operations');

                        $params = array();
                        $params['operationId'] = $goneOperationId;

                        $stmt->execute($params);
                    }
                }
            }
        }
    }

    /**
     * @param Operation[] $bridgeOperations
     * @param int $time
     */
    public function getCurrentOperations($bridgeOperations, $time = null)
    {

        $today = new \DateTimeImmutable('midnight');

        if ($time !== null) {

            $time = time();

            $today = new \DateTimeImmutable(date('Y-m-d 00:00:00', $time));
        }

        $oneYearAgo = $today->sub(new \DateInterval('P1Y'));

        // remove operations longer than one year ago

        $thisYearOperations = array();

        foreach ($bridgeOperations as $operation) {

            if ($operation->getDateTimeStart()->getTimestamp() >= $oneYearAgo->getTimestamp()) {

                $thisYearOperations[] = $operation;
            }
        }

        // collect last 10 certain operations with finished = 1 from last year

        $lastStartedOperations = $this->collectLastStartedOperations($thisYearOperations, 10, $time);

        // collect certain operations with finished = 1 from last week

        $lastWeekBridgeOperations = $this->collectLastWeekOperations($bridgeOperations, $time);

        // collect all operations with finished = 0

        $unfinishedOperations = array();

        foreach ($thisYearOperations as $operation) {

            if (!$operation->isFinished()) {

                $unfinishedOperations[] = $operation;
            }
        }

        // collect new current operation ids

        $currentOperationIds = array();

        foreach ($lastStartedOperations as $operation) {

            $operationId = $operation->getId();
            $currentOperationIds[$operationId] = $operationId;
        }

        foreach ($lastWeekBridgeOperations as $operation) {

            $operationId = $operation->getId();
            $currentOperationIds[$operationId] = $operationId;
        }

        $currentOperations = array();

        if ($currentOperationIds) {

            foreach ($bridgeOperations as $operation) {

                $operationId = $operation->getId();

                if (array_key_exists($operationId, $currentOperationIds)) {

                    $currentOperations[$operationId] = $operation;
                }
            }
        }

        return $currentOperations;
    }
}
