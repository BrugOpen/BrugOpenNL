<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Db\Model\Criterium;
use BrugOpen\Db\Model\CriteriumFieldComparison;
use BrugOpen\Model\Operation;
use BrugOpen\Projection\Model\ProjectedOperation;

class OperationProjectionEventProcessor
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

    /**
     * @param string $eventId
     */
    public function onOperationProjectionUpdated($eventId)
    {

        $log = $this->getLog();

        // load all situation version records
        $allOperationProjectionVersions = $this->loadOperationProjectionVersions($eventId);

        $projectionVersions = $allOperationProjectionVersions;

        if ($projectionVersions) {

            // determine existing operationId

            $operationId = null;

            foreach ($projectionVersions as $projectionVersion) {

                if ($projectionVersion['operation_id']) {

                    $operationId = (int)$projectionVersion['operation_id'];
                    break;
                }
            }

            /**
             * @var array
             */
            $lastProjectionVersion = array_pop($projectionVersions);

            /**
             * @var int
             */
            $timeStart = null;

            /**
             * @var int
             */
            $timeEnd = null;

            /**
             * @var int
             */
            $certainty = null;

            if (array_key_exists('time_start', $lastProjectionVersion)) {

                if ($lastProjectionVersion['time_start']) {

                    $timeStart = $lastProjectionVersion['time_start']->getTimestamp();
                }
            }

            if (array_key_exists('time_end', $lastProjectionVersion)) {

                if ($lastProjectionVersion['time_end']) {

                    $timeEnd = $lastProjectionVersion['time_end']->getTimestamp();
                }
            }

            $certainty = 0;

            if (array_key_exists('certainty', $lastProjectionVersion)) {

                if ($lastProjectionVersion['certainty']) {

                    $certainty = $lastProjectionVersion['certainty'];
                }
            }

            $bridgeId = $lastProjectionVersion['bridge_id'];

            $notifyOperationEvent = false;

            if ($operationId == null) {

                // check if operation from other source already exists for this projection

                $startingFrom = new \DateTime('@' . ($timeStart - 600));

                $existingOperations = $this->loadCurrentOperationsByBridge($bridgeId, $startingFrom);

                if ($existingOperations) {

                    $machingTimeStart = $timeStart - 120;
                    $matchingTimeEnd = $timeEnd + 120;

                    foreach ($existingOperations as $existingOperation) {

                        if (strpos($existingOperation->getEventId(), ProjectedOperation::EVENT_PREFIX . '_') === 0) {
                            // do not link to BONL operations
                            continue;
                        }

                        $existingOperationTimeStart = $existingOperation->getDateTimeStart() ? $existingOperation->getDateTimeStart()->getTimestamp() : null;
                        $existingOperationTimeEnd = $existingOperation->getDateTimeEnd() ? $existingOperation->getDateTimeEnd()->getTimestamp() : null;

                        if ($existingOperationTimeEnd == null) {

                            // assume existing operation takes as long as the projection
                            $existingOperationTimeEnd = $existingOperationTimeStart + ($timeEnd - $timeStart);
                        }

                        $operationMatches = false;

                        if ($existingOperationTimeStart >= $machingTimeStart && $existingOperationTimeStart <= $matchingTimeEnd) {
                            $operationMatches = true;
                        } else if ($existingOperationTimeEnd >= $machingTimeStart && $existingOperationTimeEnd <= $matchingTimeEnd) {
                            $operationMatches = true;
                        } else if ($existingOperationTimeStart <= $machingTimeStart && $existingOperationTimeEnd >= $matchingTimeEnd) {
                            $operationMatches = true;
                        }

                        if ($operationMatches) {

                            $operationId = $existingOperation->getId();
                            break;
                        }
                    }
                }
            }

            if ($operationId != null) {

                // if operationId,
                // check if operation was updated
                // if so, update operation and trigger operation event

                $existingOperation = $this->loadOperation($operationId);

                if ($existingOperation) {

                    // only update operation if event is still BONL01

                    if (strpos($existingOperation['event_id'], ProjectedOperation::EVENT_PREFIX . '_') === 0) {

                        $operationTimeStart = null;
                        $operationTimeEnd = null;
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

                        if ($operationCertainty != $certainty) {

                            $operationNeedsUpdate = true;
                        }

                        if ($operationNeedsUpdate) {

                            $log->info('Updating operation ' . $operationId);

                            $this->updateOperation($operationId, null, $timeStart, $timeEnd, $certainty, $bridgeId);

                            $notifyOperationEvent = true;
                        }
                    } else {

                        $log->info('Not overwriting operation ' . $existingOperation['event_id'] . ' with projection ' . $eventId);
                    }
                } else {

                    $log->error('Could not load operation ' . $operationId);
                }
            } else {

                // if no operationId, determine certainty

                if ($certainty >= Operation::CERTAINTY_PROBABLE) {

                    $log->info('Creating operation for ' . $eventId);

                    // insert operation
                    $operationId = $this->updateOperation(null, $eventId, $timeStart, $timeEnd, $certainty, $bridgeId);

                    if ($operationId) {

                        // notify operation event
                        $notifyOperationEvent = true;
                    }
                }
            }

            // update operationId in operation projections if needed

            if ($operationId) {

                $tableManager = $this->getTableManager();

                if ($allOperationProjectionVersions && $tableManager) {

                    foreach ($allOperationProjectionVersions as $projectionVersion) {

                        if ($projectionVersion['operation_id']) {
                            continue;
                        }

                        $keys = array();
                        $keys['event_id'] = $projectionVersion['event_id'];
                        $keys['version'] = $projectionVersion['version'];

                        $values['operation_id'] = $operationId;

                        $tableManager->updateRecords('bo_operation_projection', $values, $keys);
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
     * @param string $eventId
     * @return array[]
     */
    public function loadOperationProjectionVersions($eventId)
    {
        $operationProjetions = array();

        if ($eventId) {

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                $criteria = array();
                $criteria['event_id'] = $eventId;

                $order = array();
                $order[] = 'version';
                $order[] = 'asc';

                $orders = array();
                $orders[] = $order;

                $records = $tableManager->findRecords('bo_operation_projection', $criteria, null, $orders);

                if ($records) {

                    $operationProjetions = $records;
                }
            }
        }

        return $operationProjetions;
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
     * @param string $eventId
     * @param int $timeStart
     * @param int $timeEnd
     * @param int $certainty
     * @param int $bridgeId
     */
    public function updateOperation($operationId, $eventId, $timeStart, $timeEnd, $certainty, $bridgeId)
    {
        $res = null;

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $values = array();

            if (!$operationId) {

                $values['datetime_start'] = null;
                $values['time_start'] = null;
                $values['datetime_end'] = null;
                $values['time_end'] = null;
                $values['datetime_gone'] = null;
                $values['time_gone'] = null;
                $values['certainty'] = null;
            }

            if ($eventId) {

                $values['event_id'] = $eventId;
            }

            if ($timeStart) {

                $values['datetime_start'] = $timeStart;
                $values['time_start'] = new \DateTime('@' . $timeStart);
            }

            if ($timeEnd) {

                $values['datetime_end'] = $timeEnd;
                $values['time_end'] = new \DateTime('@' . $timeEnd);
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
     * @param int $bridgeId
     * @param \DateTime $from
     * @return Operation[]
     */
    public function loadCurrentOperationsByBridge($bridgeId, $from)
    {
        // load recent and future operations by bridge

        $currentOperationsByBridge = [];

        $tableManager = $this->getTableManager();

        $criteria = array();
        $criteria[] = new CriteriumFieldComparison('bridge_id', Criterium::OPERATOR_EQUALS, $bridgeId);
        $criteria[] = new CriteriumFieldComparison('time_start', Criterium::OPERATOR_GE, $from);

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

                $currentOperationsByBridge[] = $operation;
            }
        }

        return $currentOperationsByBridge;
    }
}
