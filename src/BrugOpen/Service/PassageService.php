<?php

namespace BrugOpen\Service;

use BrugOpen\Db\Service\TableManager;
use BrugOpen\Model\BridgePassage;

class PassageService
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
     * @return BridgePassage|null
     */
    public function findVesselPassage($mmsi, $bridgeId, $datetimePassage)
    {

        $passage = null;

        $tableManager = $this->getTableManager();

        if ($tableManager && $mmsi && $bridgeId && $datetimePassage) {

            $criteria = array();
            $criteria['mmsi'] = $mmsi;
            $criteria['bridge_id'] = $bridgeId;
            $criteria['datetime_passage'] = $datetimePassage;

            $record = $tableManager->findRecord('bo_bridge_passage', $criteria);
        }

        if ($record) {

            $passage = $this->getPassageFromRecord($record);
        }

        return $passage;
    }

    /**
     * @param array
     * @return BridgePassage
     */
    protected function getPassageFromRecord($record)
    {

        $passage = new BridgePassage();
        $passage->setMmsi($record['mmsi']);
        $passage->setBridgeId((int)$record['bridge_id']);
        $passage->setDatetimePassage($record['datetime_passage']);

        if (array_key_exists('direction', $record) && $record['direction']) {

            $passage->setDirection((int)$record['direction']);
        }

        if (array_key_exists('vessel_type', $record) && ($record['vessel_type'] != '')) {

            $passage->setVesselType((int)$record['vessel_type']);
        }

        if (array_key_exists('operation_id', $record) && $record['operation_id']) {

            $passage->setOperationId((int)$record['operation_id']);
        }
        return $passage;
    }

    /**
     *
     */
    public function insertPassage($mmsi, $bridgeId, $datetimePassage, $direction = null, $vesselType = null, $operationId = null)
    {

        $tableManager = $this->getTableManager();

        $values = array();
        $values['mmsi'] = $mmsi;
        $values['bridge_id'] = $bridgeId;
        $values['datetime_passage'] = $datetimePassage;

        if ($direction !== null) {

            $values['direction'] = $direction;
        }

        if ($operationId !== null) {

            $values['operation_id'] = $operationId;
        }

        if ($vesselType !== null) {

            $values['vessel_type'] = $vesselType;
        }

        if ($tableManager) {

            $tableManager->insertRecord('bo_bridge_passage', $values);
        }
    }

    /**
     * @param string $mmsi
     * @param int[] $bridgeIds
     * @param \DateTime $since
     * @return BridgePassage[][]
     */
    public function findVesselPassagesByBridge($mmsi, $bridgeIds, $since = null)
    {

        $passagesByBridge = array();

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $params = array();
            $params['mmsi'] = $mmsi;
            $params['bridge_id'] = $bridgeIds;

            $records = $tableManager->findRecords('bo_bridge_passage', $params);

            if ($records) {

                foreach ($records as $record) {

                    $passage = $this->getPassageFromRecord($record);

                    if ($since !== null) {

                        if ($passage->getDatetimePassage() && ($passage->getDatetimePassage()->getTimestamp() < $since->getTimestamp())) {
                            continue;
                        }
                    }

                    $bridgeId = $passage->getBridgeId();

                    $passagesByBridge[$bridgeId][] = $passage;
                }
            }
        }

        return $passagesByBridge;
    }

    /**
     * @param int[] $operationIds
     * @return BridgePassage[][]
     */
    public function findPassagesByOperation($operationIds)
    {
        $passagesByOperation = array();

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $params = array();
            $params['operation_id'] = $operationIds;

            $records = $tableManager->findRecords('bo_bridge_passage', $params);

            if ($records) {

                foreach ($records as $record) {

                    $passage = $this->getPassageFromRecord($record);

                    $operationId = $passage->getOperationId();

                    $passagesByOperation[$operationId][] = $passage;
                }
            }
        }

        return $passagesByOperation;
    }
}
