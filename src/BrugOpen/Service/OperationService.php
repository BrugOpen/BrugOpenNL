<?php

namespace BrugOpen\Service;

use BrugOpen\Db\Service\DatabaseTableManager;
use BrugOpen\Db\Service\TableManager;
use BrugOpen\Core\Context;
use BrugOpen\Model\Operation;

class OperationService
{

    /**
     *
     * @var Context
     */
    private $context;

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
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
    }

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
     * @param \BrugOpen\Db\Service\TableManager $tableManager
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
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog()
    {
        if ($this->log == null) {

            $context = $this->context;
            $this->log = $context->getLogRegistry()->getLog($this);
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
     * Returns operations that are 'current' meaning the last started operation and
     * all unfinished operations (started or not), grouped by bridgeId
     * @return Operation[][]
     */
    public function getCurrentOperationsByBridgeId($time = null)
    {

    }

    /**
     * 
     */
    public function updateLastStartedOperation($bridgeId, $operationId)
    {

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

                $criteria = array('id' => $cleanIds);

                $records = $this->getTableManager()->findRecords('bo_operation', $criteria);

                if ($records) {

                    $operations = $this->createOperationsFromRecords($records);

                }

            }

        }

        return $operations;

    }

    /**
     * @return Operation[]
     */
    public function loadUnfinishedOperations()
    {

        $operations = array();

        $criteria = array('finished' => 0);

        $records = $this->getTableManager()->findRecords('bo_operation', $criteria);

        if ($records) {

            $operations = $this->createOperationsFromRecords($records);

        }

        return $operations;

    }

    /**
     * @param array $records
     * @return Operation[]
     */
    public function createOperationsFromRecords($records)
    {
        $operations = array();

        foreach ($records as $row) {
                    
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

        return $operations;

    }

}
