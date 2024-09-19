<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Db\Model\Criterium;
use BrugOpen\Db\Model\CriteriumFieldComparison;
use BrugOpen\Db\Service\TableManager;
use BrugOpen\Model\Bridge;
use BrugOpen\Model\Operation;
use BrugOpen\Projection\Model\ProjectedBridgePassage;
use BrugOpen\Projection\Model\ProjectedOperation;

class OperationProjectionService
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
     * @var ProjectedPassageDataStore
     */
    private $projectedPassageDataStore;

    /**
     * @var TableManager
     */
    private $tableManager;

    /**
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

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
     * @param LoggerInterface $loggerInterface
     */
    public function setLog($loggerInterface)
    {
        $this->log = $loggerInterface;
    }

    /**
     * @return ProjectedPassageDataStore
     */
    public function getProjectedPassageDataStore()
    {
        if ($this->projectedPassageDataStore === null) {
            $this->projectedPassageDataStore = new ProjectedPassageDataStore();
            $this->projectedPassageDataStore->initialize($this->context);
        }
        return $this->projectedPassageDataStore;
    }

    /**
     * @param ProjectedPassageDataStore $projectedPassageDataStore
     */
    public function setProjectedPassageDataStore($projectedPassageDataStore)
    {
        $this->projectedPassageDataStore = $projectedPassageDataStore;
    }

    /**
     * @return TableManager
     */
    public function getTableManager()
    {
        if ($this->tableManager === null) {
            $this->tableManager = $this->context->getService('BrugOpen.TableManager');
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
     * @return \BrugOpen\Core\EventDispatcher
     */
    public function getEventDispatcher()
    {
        if ($this->eventDispatcher == null) {

            $this->eventDispatcher = $this->context->getEventDispatcher();
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
     * @param Bridge $bridge
     * @param ProjectedBridgePassage[] $passageProjections
     * @param int $maxStandardDeviation
     * @param float $minOperationProbability
     * @param \DateTime $maxDatetimePassage
     * @return ProjectedOperation[]
     */
    public function createOperationProjections($bridge, $passageProjections, $maxStandardDeviation, $minOperationProbability, $maxDatetimePassage)
    {

        $normalOperationDuration = 60 * 4; // default to 4 minutes

        if ($bridge->getMinOperationDuration() !== null) {
            $normalOperationDuration = $bridge->getMinOperationDuration();
        }

        $operationProjections = [];

        if (is_array($passageProjections) && (count($passageProjections) > 0)) {

            // collect passages that would cause an operation

            $eligiblePassageProjections = [];

            foreach ($passageProjections as $passageProjection) {

                $standardDeviation = $passageProjection->getStandardDeviation();
                $operationProbability = $passageProjection->getOperationProbability();

                if (($standardDeviation !== null) && ($operationProbability !== null)) {
                    if (($standardDeviation <= $maxStandardDeviation) && ($operationProbability >= $minOperationProbability)) {
                        if ($passageProjection->getDatetimeProjectedPassage()->getTimestamp() <= $maxDatetimePassage->getTimestamp()) {
                            $eligiblePassageProjections[] = $passageProjection;
                        }
                    }
                }
            }

            // sort passage projections by time

            $passagesByTime = array();

            foreach ($eligiblePassageProjections as $passageProjection) {
                $passageTime = $passageProjection->getDatetimeProjectedPassage()->getTimestamp();
                $passagesByTime[$passageTime][] = $passageProjection;
            }

            ksort($passagesByTime);

            $passageProjectionsStack = [];

            foreach ($passagesByTime as $passages) {
                foreach ($passages as $passage) {
                    $passageProjectionsStack[] = $passage;
                }
            }

            if (count($passageProjectionsStack) >= 2) {

                // if the first two passages are within the normal operation duration, first ship must wait

                $passageTime1 = $passageProjectionsStack[0]->getDatetimeProjectedPassage()->getTimestamp();
                $passageTime2 = $passageProjectionsStack[1]->getDatetimeProjectedPassage()->getTimestamp();

                if (abs($passageTime1 - $passageTime2) < ($normalOperationDuration)) {

                    $timeStart = $passageTime2 - $normalOperationDuration;
                    $timeEnd = $passageTime2 + ($normalOperationDuration / 2);

                    $operationProjection = new ProjectedOperation();
                    $operationProjection->setBridgeId($passageProjectionsStack[0]->getBridgeId());
                    $operationProjection->setTimeStart(new \DateTime('@' . $timeStart));
                    $operationProjection->setTimeEnd(new \DateTime('@' . $timeEnd));
                    $operationProjection->setCertainty(2); // probable

                    $projectedPassages = array();
                    $projectedPassages[] = array_shift($passageProjectionsStack);
                    $projectedPassages[] = array_shift($passageProjectionsStack);

                    $operationProjection->setProjectedPassages($projectedPassages);

                    $operationProjections[] = $operationProjection;
                }
            }

            while (count($passageProjectionsStack) > 0) {

                $projectedPassage = array_shift($passageProjectionsStack);

                $nextPassageTime = $projectedPassage->getDatetimeProjectedPassage()->getTimestamp();

                $lastProjectedOperation = null;

                if (count($operationProjections) > 0) {
                    $lastProjectedOperation = $operationProjections[count($operationProjections) - 1];
                }

                if ($lastProjectedOperation) {

                    $timeAfterLastProjectedOperation = $nextPassageTime - $lastProjectedOperation->getTimeEnd()->getTimestamp();

                    if ($timeAfterLastProjectedOperation < $normalOperationDuration) {

                        // extend last operation
                        $newTimeEnd = $nextPassageTime + ($normalOperationDuration / 2);
                        $lastProjectedOperation->setTimeEnd(new \DateTime('@' . $newTimeEnd));

                        continue;
                    }
                }

                // create new operation
                $timeStart = $nextPassageTime - ($normalOperationDuration / 2);
                $timeEnd = $nextPassageTime + ($normalOperationDuration / 2);

                $operationProjection = new ProjectedOperation();
                $operationProjection->setBridgeId($projectedPassage->getBridgeId());
                $operationProjection->setTimeStart(new \DateTime('@' . $timeStart));
                $operationProjection->setTimeEnd(new \DateTime('@' . $timeEnd));
                $operationProjection->setCertainty(2); // probable

                $projectedPassages = array();
                $projectedPassages[] = $projectedPassage;

                $operationProjection->setProjectedPassages($projectedPassages);

                $operationProjections[] = $operationProjection;
            }
        }

        return $operationProjections;
    }

    /**
     * Load projectable bridges
     * @return Bridge[]
     */
    public function loadProjectableBridges()
    {

        $bridges = array();

        $tableManager = $this->getTableManager();

        $criteria = array('announce_approach' => 1);

        $records = $tableManager->findRecords('bo_bridge', $criteria);

        if ($records) {

            foreach ($records as $record) {

                $bridgeId = $record['id'];

                $active = $record['active'];
                if ($active !== '') {
                    if ($active === 0) {
                        continue;
                    }
                }

                $bridge = new Bridge();
                $bridge->setId($bridgeId);
                $bridge->setTitle($record['title']);
                $bridge->setCity($record['city']);
                $bridge->setCity2($record['city2']);
                $bridge->setIsrsCode($record['isrs_code']);
                $bridge->setMinOperationDuration(isset($record['min_operation_duration']) ? $record['min_operation_duration'] : null);

                $bridges[$bridgeId] = $bridge;
            }
        }

        return $bridges;
    }

    /**
     * Update operation projections based on latest passage projections
     * @param \DateTime $datetimeProjection
     */
    public function updateOperationProjections($datetimeProjection = null)
    {

        $logger = $this->getLog();

        if ($datetimeProjection === null) {
            $datetimeProjection = new \DateTime();
        }

        $logger->info('Updating operation projections based on latest passage projections at ' . $datetimeProjection->format('Y-m-d H:i:s'));

        $tableManager = $this->getTableManager();

        $bridgesWithPassageProjections = $this->loadProjectableBridges();

        // load passage projections by bridge
        $passageProjectionsByBridge = $this->loadPassageProjectionsByBridge($datetimeProjection->getTimestamp());

        // load last operation projections
        $lastOperationProjections = $this->loadLastOperationProjections();

        // load existing operation projections by bridge
        $lastOperationProjectionsByBridge = $this->getOperationProjectionsByBridge($lastOperationProjections);

        // load future operations by bridge
        $futureOperationsByBridge = $this->loadFutureOperationsByBridge($datetimeProjection->getTimestamp());

        $minOperationProbability = 0.5; // 50%
        $maxStandardDeviation = 90; // 90 seconds
        $maxDatetimePassage = new \DateTime('@' . ($datetimeProjection->getTimestamp() + (30 * 60))); // 30 minutes

        $latestVersionByEventId = array();

        foreach ($bridgesWithPassageProjections as $bridge) {

            $bridgeId = $bridge->getId();

            $futureOperations = isset($futureOperationsByBridge[$bridgeId]) ? $futureOperationsByBridge[$bridgeId] : [];

            $maxGap = 5 * 60; // 5 minutes

            // use larger max gap if bridge has longer minimal operation time
            if ($bridge->getMinOperationDuration() > 0) {
                $maxGap = $bridge->getMinOperationDuration();
            }

            $passageProjections = isset($passageProjectionsByBridge[$bridgeId]) ? $passageProjectionsByBridge[$bridgeId] : [];

            $operationProjections = $this->createOperationProjections($bridge, $passageProjections, $maxStandardDeviation, $minOperationProbability, $maxDatetimePassage);

            $matchingEventId = null;
            $version = 1;

            foreach ($operationProjections as $operationProjection) {

                // look for existing operation projection with nearly the same time start and time end

                foreach ($futureOperations as $futureOperation) {

                    $gap = $this->calculateGap($operationProjection->getTimeStart(), $operationProjection->getTimeEnd(), $futureOperation->getDateTimeStart(), $futureOperation->getDateTimeEnd());

                    if ($gap <= $maxGap) {
                        $matchingEventId = $futureOperation->getEventId();
                    }

                    if ($matchingEventId) {
                        break;
                    }
                }

                $existingOperationProjections = isset($lastOperationProjectionsByBridge[$bridgeId]) ? $lastOperationProjectionsByBridge[$bridgeId] : [];

                // adjust version if needed
                if ($matchingEventId) {
                    if ($existingOperationProjections) {
                        foreach ($existingOperationProjections as $existingOperationProjection) {
                            if ($existingOperationProjection->getEventId() == $matchingEventId) {
                                $version = $existingOperationProjection->getVersion() + 1;
                                break;
                            }
                        }
                    }
                }

                if ($matchingEventId == null) {

                    // look for existing operation projection with about the same time start and time end

                    foreach ($existingOperationProjections as $existingOperationProjection) {

                        if ($existingOperationProjection->getCertainty() == 0) {
                            continue;
                        }

                        $gap = $this->calculateGap($operationProjection->getTimeStart(), $operationProjection->getTimeEnd(), $existingOperationProjection->getTimeStart(), $existingOperationProjection->getTimeEnd());

                        if ($gap <= $maxGap) {
                            $matchingEventId = $existingOperationProjection->getEventId();
                            $version = $existingOperationProjection->getVersion() + 1;

                            $logger->info('Found existing operation ' . $matchingEventId . ' for bridge ' . $bridge->getId() . ' with gap ' . $gap . ' seconds, next version ' . $version);
                        }

                        if ($matchingEventId) {
                            break;
                        }
                    }
                }

                if ($matchingEventId) {
                    $eventId = $matchingEventId;
                } else {

                    // determine new event id
                    $lastProjectedOperationId = $this->getLastOperationProjectionId();
                    $nextProjectedOperationId = $lastProjectedOperationId + 1;
                    $eventId = ProjectedOperation::EVENT_PREFIX . '_' . $bridge->getIsrsCode() . '_' . $nextProjectedOperationId;

                    $logger->info('New event ' . $eventId . ' for bridge ' . $bridge->getId());
                }

                if (strpos($eventId, ProjectedOperation::EVENT_PREFIX . '_') === 0) {

                    $operationProjectionValues = [];

                    $operationProjectionValues['event_id'] = $eventId;
                    $operationProjectionValues['version'] = $version;
                    $operationProjectionValues['bridge_id'] = $bridgeId;
                    $operationProjectionValues['time_start'] = $operationProjection->getTimeStart();
                    $operationProjectionValues['time_end'] = $operationProjection->getTimeEnd();
                    $operationProjectionValues['certainty'] = $operationProjection->getCertainty();
                    $operationProjectionValues['datetime_projection'] = $datetimeProjection;

                    $logger->info('Inserting version ' . $version . ' for event ' . $eventId . ' at bridge ' . $bridge->getId());

                    // insert new operation projection
                    $tableManager->insertRecord('bo_operation_projection', $operationProjectionValues);

                    // update event id in projected passages
                    $projectedPassages = $operationProjection->getProjectedPassages();

                    foreach ($projectedPassages as $projectedPassage) {
                        $values = [];
                        $values['event_id'] = $eventId;
                        $keys = ['id' => $projectedPassage->getId()];
                        $tableManager->updateRecords('bo_passage_projection', $values, $keys);
                    }

                    // store latest version by event id
                    $latestVersionByEventId[$eventId] = $version;

                    // notify listeners
                    $eventDispatcher = $this->getEventDispatcher();
                    $eventDispatcher->postEvent('OperationProjection.update', array($eventId));
                } else {

                    $logger->info("Projected operation for bridge " . $bridgeId . " matches with operation " . $eventId);
                }
            }

            $futureOperationsByBridge[$bridgeId] = $operationProjections;

            // check for operations that are no longer current

            if (isset($lastOperationProjectionsByBridge[$bridgeId])) {

                $existingOperationProjections = $lastOperationProjectionsByBridge[$bridgeId];

                foreach ($existingOperationProjections as $existingOperationProjection) {
                    $eventId = $existingOperationProjection->getEventId();
                    $newerVersionExists = isset($latestVersionByEventId[$eventId]);

                    if ($existingOperationProjection->getCertainty() == 0) {
                        continue;
                    }

                    if ($existingOperationProjection->getTimeStart()->getTimestamp() < $datetimeProjection->getTimestamp()) {
                        continue;
                    }

                    if (!$newerVersionExists) {

                        // create new version with certainty 0
                        $version = $existingOperationProjection->getVersion() + 1;

                        $operationProjectionValues['event_id'] = $eventId;
                        $operationProjectionValues['version'] = $version;
                        $operationProjectionValues['bridge_id'] = $bridgeId;
                        $operationProjectionValues['time_start'] = null;
                        $operationProjectionValues['time_end'] = null;
                        $operationProjectionValues['certainty'] = 0;
                        $operationProjectionValues['datetime_projection'] = $datetimeProjection;

                        // insert new operation projection
                        $tableManager->insertRecord('bo_operation_projection', $operationProjectionValues);

                        $logger->info('Marking event ' . $eventId . ' as no longer current');

                        $eventDispatcher = $this->getEventDispatcher();
                        $eventDispatcher->postEvent('OperationProjection.update', array($eventId));
                    }
                }
            }
        }

        $logger->info('Done updating operation projections based on latest passage projections at ' . $datetimeProjection->format('Y-m-d H:i:s'));
    }

    /**
     * @param int $time
     * @return ProjectedBridgePassage[][]
     */
    public function loadPassageProjectionsByBridge($time = null)
    {
        // load passage projections by bridge
        $passageProjectionsByBridge = [];

        $projectedPassageDataStore = $this->getProjectedPassageDataStore();

        $currentPassageProjections = $projectedPassageDataStore->loadCurrentPassageProjections($time);

        foreach ($currentPassageProjections as $passageProjection) {
            $bridgeId = $passageProjection->getBridgeId();
            if (!isset($passageProjectionsByBridge[$bridgeId])) {
                $passageProjectionsByBridge[$bridgeId] = [];
            }
            $passageProjectionsByBridge[$bridgeId][] = $passageProjection;
        }

        return $passageProjectionsByBridge;
    }

    /**
     * @return ProjectedOperation[]
     */
    public function loadLastOperationProjections()
    {

        $tableManager = $this->getTableManager();

        $operationProjections = array();

        $tableManager = $this->getTableManager();

        $lastId = null;
        $limit = 1000;
        $onlySince = null;

        do {

            $orders = array(array('id', 'DESC'));
            $criteria = array();
            if ($lastId !== null) {
                $criteria[] = new CriteriumFieldComparison('id', Criterium::OPERATOR_LT, $lastId);
            }
            $records = $tableManager->findRecords('bo_operation_projection', $criteria, null, $orders, $limit);

            $lastId = null;

            if ($records) {

                foreach ($records as $record) {

                    $lastId = $record['id'];

                    $datetimeProjection = $record['datetime_projection'];
                    if ($datetimeProjection === null) {
                        continue;
                    }

                    if ($onlySince === null) {
                        $onlySince = $datetimeProjection->getTimestamp();
                    }

                    if ($datetimeProjection->getTimestamp() < $onlySince) {
                        $lastId = null;
                        break;
                    }

                    $operationProjection = new ProjectedOperation();
                    $operationProjection->setId($record['id']);
                    $operationProjection->setEventId($record['event_id']);
                    $operationProjection->setVersion($record['version']);
                    $operationProjection->setBridgeId($record['bridge_id']);
                    $operationProjection->setTimeStart($record['time_start']);
                    $operationProjection->setTimeEnd($record['time_end']);
                    $operationProjection->setCertainty($record['certainty']);
                    $operationProjection->setDatetimeProjection($record['datetime_projection']);

                    $operationProjections[] = $operationProjection;

                    if ($datetimeProjection->getTimestamp() > $onlySince) {
                        $onlySince = $datetimeProjection->getTimestamp();
                    }
                }
            }
        } while ($lastId !== null);

        return $operationProjections;
    }

    /**
     * @param ProjectedOperation[] $operationProjections
     * @return ProjectedOperation[][]
     */
    public function getOperationProjectionsByBridge($operationProjections)
    {
        // load operation projections by bridge
        $operationProjectionsByBridge = [];

        foreach ($operationProjections as $operationProjection) {
            $bridgeId = $operationProjection->getBridgeId();
            if (!isset($operationProjectionsByBridge[$bridgeId])) {
                $operationProjectionsByBridge[$bridgeId] = [];
            }
            $operationProjectionsByBridge[$bridgeId][] = $operationProjection;
        }

        return $operationProjectionsByBridge;
    }

    /**
     * @param int $time
     * @return Operation[][]
     */
    public function loadFutureOperationsByBridge($time = null)
    {
        // load future operations by bridge

        $futureOperationsByBridge = [];

        $tableManager = $this->getTableManager();

        $now = new \DateTime();

        if ($time !== null) {
            $now = new \DateTime('@' . $time);
        }

        $criteria = array();
        $criteria[] = new CriteriumFieldComparison('time_start', Criterium::OPERATOR_GE, $now);

        $records = $tableManager->findRecords('bo_operation', $criteria);

        if ($records) {

            foreach ($records as $record) {

                $operationId = $record['id'];

                $operationIds[] = $operationId;

                $operation = new Operation();
                $operation->setId($operationId);
                $operation->setEventId($record['event_id']);
                $operation->setBridgeId($record['bridge_id']);
                $operation->setDateTimeStart($record['time_start']);
                $operation->setDateTimeEnd($record['time_end']);
                $operation->setCertainty($record['certainty']);

                $futureOperationsByBridge[$operation->getBridgeId()][] = $operation;
            }
        }

        return $futureOperationsByBridge;
    }

    /**
     * @return int
     */
    public function getLastOperationProjectionId()
    {
        $lastProjectedOperationId = 0;

        $tableManager = $this->getTableManager();

        $orders = array(array('id', 'DESC'));
        $records = $tableManager->findRecords('bo_operation_projection', null, null, $orders, 1);

        if ($records) {
            $lastProjectedOperationId = (int)$records[0]['id'];
        }

        return $lastProjectedOperationId;
    }

    /**
     * @param \DateTime $startTime1
     * @param \DateTime $endTime1
     * @param \DateTime $startTime2
     * @param \DateTime $endTime2
     */
    public function calculateGap($startTime1, $endTime1, $startTime2, $endTime2)
    {
        $gap = 0;

        if ($endTime1->getTimestamp() < $startTime2->getTimestamp()) {
            $gap = $startTime2->getTimestamp() - $endTime1->getTimestamp();
        } else if ($endTime2->getTimestamp() < $startTime1->getTimestamp()) {
            $gap = $startTime1->getTimestamp() - $endTime2->getTimestamp();
        }

        return $gap;
    }
}
