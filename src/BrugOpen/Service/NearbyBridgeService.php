<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Model\Bridge;
use BrugOpen\Model\LatLng;
use BrugOpen\Model\NearbyBridge;

class NearbyBridgeService
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var Bridge[]
     */
    private $allBridges;

    /**
     *
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
    }


    /**
     * @return Bridge[]
     */
    public function getAllBridges()
    {

        if (!is_array($this->allBridges)) {

            $allBridges = array();

            $sql = 'SELECT * FROM bo_bridge';

            if ($results = $this->context->getDataStore()->executeQuery($sql)) {

                while ($row = $results->fetch_assoc()) {

                    $bridgeId = (int)$row['id'];

                    $bridge = new Bridge();
                    $bridge->setId($bridgeId);
                    $bridge->setName($row['name']);
                    $bridge->setTitle($row['title']);
                    $bridge->setCity($row['city']);
                    $bridge->setCity2($row['city2']);

                    if ($row['last_started_operation_id'] > 0) {
                        $bridge->setLastStartedOperationId((int)$row['last_started_operation_id']);
                    }

                    $lat = $row['ndw_lat'];
                    $lng = $row['ndw_lng'];

                    if (($lat != '') && ($lng != '')) {

                        $latLng = new LatLng((float)$lat, (float)$lng);
                        $bridge->setLatLng($latLng);

                    }

                    $allBridges[$bridgeId] = $bridge;

                }

            }

            $this->allBridges = $allBridges;

        }

        return $this->allBridges;

    }

    /**
     *
     * @param Bridge[] $bridges
     * @return Bridge[]
     */
    public function getActiveBridges($bridges)
    {

        $activeBridges = array();

        $lastOperationIdByBridge = array();

        $bridgesById = array();

        foreach ($bridges as $bridge) {

            $bridgeId = $bridge->getId();

            if ($bridge->getLastStartedOperationId() > 0) {

                $lastOperationIdByBridge[$bridgeId] = $bridge->getLastStartedOperationId();

            }

            $bridgesById[$bridgeId] = $bridge;

        }

        $operationsById = array();

        if (sizeof($lastOperationIdByBridge)) {

            $operationService = new OperationService();
            $operationService->initialize($this->context);

            $operationsById = $operationService->loadOperationsById($lastOperationIdByBridge);

        }

        $now = time();

        $maxAge = 3600 * 24 * 30;

        foreach ($lastOperationIdByBridge as $bridgeId => $operationId) {

            $bridgeActive = false;

            if (array_key_exists($operationId, $operationsById)) {

                $lastOperation = $operationsById[$operationId];

                if (!$lastOperation->isFinished()) {

                    $bridgeActive = true;

                }

                if ($lastOperation->getDateTimeStart() != null) {

                    $age = $now - $lastOperation->getDateTimeStart()->getTimestamp();

                    if ($age < $maxAge) {

                        $bridgeActive = true;

                    }

                }

            }

            if ($bridgeActive) {

                $activeBridges[$bridgeId] = $bridgesById[$bridgeId];

            }

        }

        return $activeBridges;

    }

    /**
     *
     * @param int $bridgeId
     * @return NearbyBridge[]
     */
    public function getNearbyBridges($bridgeId)
    {

        $nearbyBridges = array();

        if ($bridgeId > 0) {

            $allBridges = $this->getAllBridges();

            if ($distanceByBridge = $this->loadExistingNearbyBridges($bridgeId)) {

                foreach ($distanceByBridge as $otherBridgeId => $distance) {

                    if (array_key_exists($otherBridgeId, $allBridges)) {

                        $nearbyBridge = new NearbyBridge();
                        $nearbyBridge->setBridgeId($bridgeId);
                        $nearbyBridge->setNearbyBridgeId($otherBridgeId);
                        $nearbyBridge->setDistance($distance);
                        $nearbyBridge->setNearbyBridge($allBridges[$otherBridgeId]);

                        $nearbyBridges[] = $nearbyBridge;

                    }

                }

            }

        }

        return $nearbyBridges;

    }

    /**
     *
     */
    public function updateNearbyBridges()
    {

        $allBridges = $this->getAllBridges();
        $activeBridges = $this->getActiveBridges($allBridges);

        $geoService = new GeoService();

        $existingNearbyBridges = $this->loadExistingNearbyBridges();

        foreach ($allBridges as $bridge) {

            if ($bridge->getLatLng() == null) {
                continue;
            }

            $bridgeId = $bridge->getId();

            $bridgePoint = $bridge->getLatLng();

            $bridgesByDistance = array();

            foreach ($activeBridges as $activeBridge) {

                if ($activeBridge->getId() == $bridge->getId()) {
                    continue;
                }

                if ($activeBridge->getLatLng() == null) {
                    continue;
                }

                $otherBridgePoint = $activeBridge->getLatLng();

                $distance = (int)round($geoService->getDistance($bridgePoint, $otherBridgePoint));

                if ($distance > 0) {

                    if (!array_key_exists($distance, $bridgesByDistance)) {
                        $bridgesByDistance[$distance] = array();
                    }

                    $bridgesByDistance[$distance][] = $activeBridge;

                }

            }

            $nearestBridges = array();

            $maxDistance = 10 * 1000; // 10 km

            if (sizeof($bridgesByDistance) > 0) {

                ksort($bridgesByDistance);

                foreach (array_keys($bridgesByDistance) as $distance) {

                    if ($distance > $maxDistance) {
                        break;
                    }

                    foreach ($bridgesByDistance[$distance] as $otherBridge) {

                        $nearestBridges[$otherBridge->getId()] = number_format($distance / 1000, 2, '.', '');

                    }

                }

            }

            foreach ($nearestBridges as $otherBridgeId => $distance) {

                if (array_key_exists($bridgeId, $existingNearbyBridges)) {

                    if (array_key_exists($otherBridgeId, $existingNearbyBridges[$bridgeId])) {

                        $existingDistance = $existingNearbyBridges[$bridgeId][$otherBridgeId];

                        if ($existingDistance != $distance) {

                            $this->updateNearbyBridge($bridgeId, $otherBridgeId, $distance);

                        }

                    } else {

                        $this->insertNearbyBridge($bridgeId, $otherBridgeId, $distance);

                    }

                } else {

                    $this->insertNearbyBridge($bridgeId, $otherBridgeId, $distance);

                }

            }

            if (array_key_exists($bridgeId, $existingNearbyBridges)) {

                $deleteExistingOtherBridges = array();

                foreach ($existingNearbyBridges[$bridgeId] as $otherBridgeId => $distance) {

                    if (!array_key_exists($otherBridgeId, $nearestBridges)) {

                        $deleteExistingOtherBridges[] = $otherBridgeId;

                    }

                }

                if (sizeof($deleteExistingOtherBridges) > 0) {

                    $this->deleteNearbyBridges($bridgeId, $deleteExistingOtherBridges);

                }

            }

        }

    }

    public function loadExistingNearbyBridges($onlyBridgeId = null)
    {

        $nearbyBridges = array();

        $sql = 'SELECT * FROM bo_bridge_nearby';

        if ($onlyBridgeId > 0) {
            $sql .= ' WHERE bridge_id = ' . (int) $onlyBridgeId;
        }

        $sql .= ' ORDER BY bridge_id, distance';

        if ($results = $this->context->getDataStore()->executeQuery($sql)) {

            while ($row = $results->fetch_assoc()) {

                $bridgeId = (int)$row['bridge_id'];
                $otherBridgeId = (int)$row['nearby_bridge_id'];
                $distance = number_format($row['distance'], 2, '.', '');

                if ($onlyBridgeId > 0) {

                    $nearbyBridges[$otherBridgeId] = $distance;

                } else {

                    $nearbyBridges[$bridgeId][$otherBridgeId] = $distance;

                }

            }

        }

        return $nearbyBridges;

    }

    public function updateNearbyBridge($bridgeId, $nearbyBridgeId, $distance)
    {

        if (($bridgeId > 0) && ($nearbyBridgeId > 0) && ($distance > 0)) {

            $sql = 'UPDATE bo_bridge_nearby SET distance = ' . number_format($distance, 2, '.', '') .
                ' WHERE bridge_id = ' . ((int)$bridgeId) . ' AND nearby_bridge_id = ' . ((int)$nearbyBridgeId);

            $this->context->getDataStore()->executeQuery($sql);

        }

    }

    public function insertNearbyBridge($bridgeId, $nearbyBridgeId, $distance)
    {

        if (($bridgeId > 0) && ($nearbyBridgeId > 0) && ($distance > 0)) {

            $sql = 'INSERT INTO bo_bridge_nearby (bridge_id, nearby_bridge_id, distance) VALUES (' . ((int)$bridgeId) .
                ', ' . ((int)$nearbyBridgeId) . ', ' . number_format($distance, 2, '.', '') . ')';

            $this->context->getDataStore()->executeQuery($sql);

        }

    }

    public function deleteNearbyBridges($bridgeId, $nearbyBridgeIds)
    {

        if ($bridgeId > 0) {

            $cleanNearbyBridgeIds = array();

            foreach ($nearbyBridgeIds as $nearbyBridgeId) {

                if ($nearbyBridgeId > 0) {

                    $cleanNearbyBridgeIds[] = (int) $nearbyBridgeId;

                }

            }

            if (sizeof($cleanNearbyBridgeIds) > 0) {

                $sql = 'DELETE FROM bo_bridge_nearby WHERE bridge_id = ' . ((int)$bridgeId) .
                ' AND nearby_bridge_id IN (' . implode(',', $cleanNearbyBridgeIds) . ')';

                $this->context->getDataStore()->executeQuery($sql);

            }

        }

    }

}
