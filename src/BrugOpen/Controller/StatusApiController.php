<?php

namespace BrugOpen\Controller;

use BrugOpen\Core\Context;
use BrugOpen\Core\ContextAware;
use BrugOpen\Db\Service\TableManager;
use BrugOpen\Model\Operation;
use BrugOpen\Service\BridgeService;
use BrugOpen\Service\OperationService;
use BrugOpen\Service\VesselTypeService;

class StatusApiController implements ContextAware
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
    public function setContext($context)
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
     *
     * @param \Psr\Log\LoggerInterface $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    /**
     * @return TableManager
     */
    public function getTableManager()
    {

        if ($this->tableManager == null) {

            if ($this->context) {

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

    public function execute()
    {

        $res = array();

        $bridgeDataByBridgeName = array();

        $operationService = new OperationService();
        $operationService->initialize($this->context);

        $bridgeService = new BridgeService();
        $bridgeService->initialize($this->context);

        $bridgesById = $bridgeService->getAllBridges();

        $currentOperationsByBridge = $operationService->loadCurrentOperationsByBridge();

        $approachOperationIds = array();

        if ($currentOperationsByBridge) {

            foreach ($currentOperationsByBridge as $bridgeId => $bridgeOperations) {

                if (array_key_exists($bridgeId, $bridgesById)) {

                    $bridge = $bridgesById[$bridgeId];

                    $bridgeName = $bridge->getName();

                    if ($bridgeName == '') {
                        continue;
                    }

                    if (!$bridge->getTitle()) {
                        continue;
                    }

                    if ($bridge->isActive() === false) {
                        continue;
                    }

                    // sort operations by start time
                    $sortedOperations = $operationService->sortOperationsByStartTime($bridgeOperations);

                    $lastWeekOperations = $operationService->collectLastWeekOperations($sortedOperations);

                    $lastWeekStats = $operationService->collectLastWeekStats($lastWeekOperations);

                    $lastStartedOperations = $operationService->collectLastStartedOperations($sortedOperations, 10);

                    if ($lastStartedOperations) {

                        $nextStartingOperation = $operationService->collectNextStartingOperation($sortedOperations, time());

                        $bridgeData = array();

                        // $bridgeData['id'] = $bridge->getId();
                        $bridgeData['name'] = $bridgeName;
                        $bridgeData['title'] = $bridge->getTitle();

                        if ($bridge->getDistinctiveTitle()) {
                            $bridgeData['distinctiveTitle'] = $bridge->getDistinctiveTitle();
                        }

                        $bridgeData['city'] = $bridge->getCity();

                        if ($bridge->getCity2()) {
                            $bridgeData['city2'] = $bridge->getCity2();
                        }

                        if ($bridge->getLatLng()) {
                            $lat = (float)number_format($bridge->getLatLng()->getLat(), 4);
                            $lon = (float)number_format($bridge->getLatLng()->getLng(), 4);
                            $bridgeData['location'] = array($lat, $lon);
                        }

                        $lastStartedOperationsData = array();

                        foreach ($lastStartedOperations as $operation) {

                            $lastStartedOperationsData[] = $this->getOperationData($operation);

                            $operationId = $operation->getId();

                            $approachOperationIds[] = $operationId;
                        }

                        if ($nextStartingOperation) {

                            $nextStartingOperationData = $this->getOperationData($nextStartingOperation);

                            if ($nextStartingOperationData) {

                                array_unshift($lastStartedOperationsData, $nextStartingOperationData);
                            }
                        }

                        $bridgeData['lastOperations'] = $lastStartedOperationsData;

                        if ($lastWeekStats) {

                            $bridgeData['lastWeekStats'] = $lastWeekStats;
                        }

                        $bridgeDataByBridgeName[$bridgeName] = $bridgeData;
                    }
                }
            }
        }

        ksort($bridgeDataByBridgeName);

        // load ship types by operation id

        if ($approachOperationIds) {

            $shipTypesByOperationId = $operationService->loadShipTypesByOperationId($approachOperationIds);

            if ($shipTypesByOperationId) {

                $vesselTypeService = new VesselTypeService();
                $vesselTypeService->initialize($this->context);

                $vesselTypeNameByTypeId = $vesselTypeService->getVesselTypeTitleById();

                foreach (array_keys($bridgeDataByBridgeName) as $bridgeName) {

                    $bridgeData = $bridgeDataByBridgeName[$bridgeName];

                    $lastStartedOperations = $bridgeData['lastOperations'];

                    foreach ($lastStartedOperations as $i => $operationData) {

                        $operationId = $operationData['id'];

                        if (array_key_exists($operationId, $shipTypesByOperationId)) {

                            $operationShipTypes = $shipTypesByOperationId[$operationId];

                            $vesselTypes = array();

                            foreach ($operationShipTypes as $vesselTypeId) {

                                $vesselTypeTitle = null;

                                if (array_key_exists($vesselTypeId, $vesselTypeNameByTypeId)) {

                                    $vesselTypeTitle = $vesselTypeNameByTypeId[$vesselTypeId];
                                }

                                $vesselTypes[] = $vesselTypeTitle;
                            }

                            if ($vesselTypes) {

                                $operationData['vesselTypes'] = $vesselTypes;

                                $bridgeDataByBridgeName[$bridgeName]['lastOperations'][$i] = $operationData;
                            }
                        }
                    }
                }
            }
        }

        // add distances to nearby bridges

        $nearbyBridgesByBridge = $bridgeService->loadExistingNearbyBridges();

        if ($nearbyBridgesByBridge) {

            foreach ($bridgesById as $bridgeId => $bridge) {

                $bridgeName = $bridge->getName();

                if (!array_key_exists($bridgeName, $bridgeDataByBridgeName)) {
                    continue;
                }

                $nearbyBridges = array();

                if (array_key_exists($bridgeId, $nearbyBridgesByBridge)) {

                    foreach ($nearbyBridgesByBridge[$bridgeId] as $otherBridgeId => $distance) {

                        if (!array_key_exists($otherBridgeId, $bridgesById)) {

                            continue;
                        }

                        $otherBridgeName = $bridgesById[$otherBridgeId]->getName();

                        if (!$otherBridgeName) {

                            continue;
                        }

                        if (array_key_exists($otherBridgeId, $currentOperationsByBridge)) {

                            // other bridge is active
                            $nearbyBridges[] = array($otherBridgeName, (float)$distance);
                        }
                    }
                }

                $bridgeDataByBridgeName[$bridgeName]['nearbyBridges'] = $nearbyBridges;
            }
        }

        header("Content-type: application/json");

        $res['lastUpdate'] = time();
        $res['bridges'] = array_values($bridgeDataByBridgeName);

        echo json_encode($res);
    }

    /**
     * @param Operation $operation
     * @return array
     */
    public function getOperationData($operation)
    {

        $operationData = array();

        $operationData['id'] = $operation->getId();
        $operationData['start'] = $operation->getDateTimeStart()->getTimestamp();

        if ($operation->getDateTimeEnd()) {

            $operationData['end'] = $operation->getDateTimeEnd()->getTimestamp();
        }

        if ($operation->getCertainty()) {

            $operationData['certainty'] = $operation->getCertainty();
        }

        return $operationData;
    }
}
