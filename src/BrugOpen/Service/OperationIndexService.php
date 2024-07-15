<?php

namespace BrugOpen\Service;

use BrugOpen\Db\Model\Criterium;
use BrugOpen\Db\Model\CriteriumFieldComparison;
use BrugOpen\Db\Service\TableManager;
use BrugOpen\Model\Operation;

class OperationIndexService
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
     * @param int $bridgeId
     * @param int $timestamp
     * @return Operation|null
     */
    public function getLastStartedOperation($bridgeId, $timestamp)
    {

        $operation = null;

        $record = null;

        $tableManager = $this->getTableManager();

        if ($tableManager && $bridgeId && $timestamp) {

            $dateTimeStart = new \DateTime('@' . $timestamp);

            $criteria = array();
            $criteria['bridge_id'] = $bridgeId;
            $criteria[] = new CriteriumFieldComparison('time_start', Criterium::OPERATOR_LE, $dateTimeStart);

            $orders = array();
            $orders[] = array('time_start', 'desc');

            $records = $tableManager->findRecords('bo_operation', $criteria, null, $orders, 1);

            if ($records) {

                $record = $records[0];
            }
        }

        if ($record) {

            $operation = new Operation();
            $operation->setId((int)$record['id']);
            $operation->setBridgeId((int)$record['bridge_id']);
            $operation->setDateTimeStart($record['time_start']);

            if (array_key_exists('time_end', $record) && $record['time_end']) {

                $operation->setDateTimeEnd($record['time_end']);
            }
        }

        return $operation;
    }
}
