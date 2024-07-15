<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Geo\Model\LatLng as GeoLatLng;
use BrugOpen\Geo\Model\LineSegment;
use BrugOpen\Model\Bridge;
use BrugOpen\Model\LatLng;
use BrugOpen\Model\NearbyBridge;
use BrugOpen\Tracking\Model\WaterwaySegment;

class BridgeService
{

    /**
     *
     * @var Context
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
     * @param \BrugOpen\Db\Service\TableManager $tableManager
     */
    public function setTableManager($tableManager)
    {
        $this->tableManager = $tableManager;
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

                        $latLng = new GeoLatLng((float)$lat, (float)$lng);
                        $bridge->setLatLng($latLng);
                    }

                    if ($row['connected_segments']) {

                        $connectedSegmentIds = array();

                        $parts = explode(',', $row['connected_segments']);

                        foreach ($parts as $part) {

                            $partParts = explode(':', $part);

                            if (count($partParts) == 2) {

                                $bearing = (int)$partParts[0];
                                $connectedBridgeId = (int)$partParts[1];

                                $connectedSegmentIds[$bearing] = $connectedBridgeId;
                            }
                        }

                        $bridge->setConnectedSegmentIds($connectedSegmentIds);
                    }

                    if ($row['clearance']) {
                        $bridge->setClearance((float)$row['clearance']);
                    }

                    $bridge->setAnnounceApproaches($row['announce_approach']);

                    $active = null;

                    if ($row['active'] != '') {

                        $active = (bool) $row['active'];
                    }

                    $bridge->setActive($active);

                    $allBridges[$bridgeId] = $bridge;
                }
            }

            $this->allBridges = $allBridges;
        }

        return $this->allBridges;
    }

    /**
     * @param Bridge[] $bridges
     */
    public function setAllBridges($bridges)
    {

        $allBridges = array();

        foreach ($bridges as $bridge) {

            $bridgeId = $bridge->getId();
            $allBridges[$bridgeId] = $bridge;
        }

        $this->allBridges = $allBridges;
    }

    /**
     * @param int $ndwId
     * @param string $isrs
     * @param LatLng $latLng
     * @return Bridge
     */
    public function insertBridgeFromNdwData($ndwId, $isrs, $latLng)
    {

        $bridge = null;

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $record = array();

            if ($ndwId) {

                $record['ndw_id'] = $ndwId;
            }

            if ($isrs) {

                $record['isrs_code'] = $isrs;
            }

            if ($latLng) {

                $record['ndw_lat'] = $latLng->getLat();
                $record['ndw_lng'] = $latLng->getLng();
            }

            $now = date('Y-m-d H:i:s');
            $record['datetime_created'] = $now;
            $record['datetime_modified'] = $now;

            $insertedId = $tableManager->insertRecord('bo_bridge', $record);

            if ($insertedId) {

                // create bridge

                $bridge = new Bridge();
                $bridge->setId((int)$insertedId);

                if ($isrs) {

                    $bridge->setIsrsCode($isrs);
                }

                if ($latLng) {

                    $bridge->setLatLng($latLng);
                }
            }
        }

        return $bridge;
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


    /**
     * @param WaterwaySegment[] $waterwaySegments
     * @param int[][] $segmentConnections
     */
    public function updateConnectedWaterwaySegments($waterwaySegments, $segmentConnections)
    {

        $bridges = $this->getAllBridges();

        $connectedSegmentsByBridge = array();

        foreach ($bridges as $bridgeId => $bridge) {

            $connectedSegmentIds = array();

            $connectedSegments = $this->determineConnectedWaterwaySegments($bridge, $waterwaySegments, $segmentConnections);

            if ($connectedSegments) {

                foreach ($connectedSegments as $bearing => $segment) {

                    $connectedSegmentIds[$bearing] = $segment->getId();
                }
            }

            $connectedSegmentsByBridge[$bridgeId] = $connectedSegmentIds;
        }

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $records = $tableManager->findRecords('bo_bridge');

            if ($records) {

                foreach ($records as $record) {

                    $bridgeId = $record['id'];
                    $existingConnections = $record['connected_segments'];

                    $actualConnections = '';

                    if (array_key_exists($bridgeId, $connectedSegmentsByBridge)) {

                        $parts = array();

                        foreach ($connectedSegmentsByBridge[$bridgeId] as $bearing => $segmentId) {

                            $parts[] = $bearing . ':' . $segmentId;
                        }

                        $actualConnections = implode(',', $parts);
                    }

                    if ($actualConnections != $existingConnections) {

                        $logger = $this->getLog();

                        if ($logger) {
                            $logger->info("Updating connected segments for bridge " . $bridgeId);
                        }

                        $values = array('connected_segments' => $actualConnections);

                        $keys = array('id' => $bridgeId);

                        $tableManager->updateRecords('bo_bridge', $values, $keys);
                    }
                }
            }
        }
    }

    /**
     * @param Bridge $bridge
     * @param WaterwaySegment[] $waterwaySegments
     * @param int[][] $segmentConnections
     * @return WaterwaySegment[]
     */
    public function determineConnectedWaterwaySegments($bridge, $waterwaySegments, $segmentConnections)
    {

        /**
         * WaterwaySegment[]
         */
        $connectedSegmentsByBearing = array();

        $bridgeLocation = $bridge->getLatLng();

        if (!$bridgeLocation) {
            return;
        }

        $connectedSegments = array();

        /**
         * @var WaterwaySegment[]
         */
        $matchingSegmentsByBounds = array();

        /**
         * @var WaterwaySegment[]
         */
        $matchingSegments = array();

        foreach ($waterwaySegments as $waterwaySegment) {

            $bounds = $waterwaySegment->getBounds();

            if ($bounds->isInBounds($bridgeLocation)) {

                $matchingSegmentsByBounds[] = $waterwaySegment;
            }
        }

        if (count($matchingSegmentsByBounds) > 0) {

            foreach ($matchingSegmentsByBounds as $matchingSegmentByBounds) {

                if ($matchingSegmentByBounds->getPolygon()->isPointInPolygon($bridgeLocation)) {

                    $matchingSegments[] = $matchingSegmentByBounds;
                }
            }
        }

        if (count($matchingSegments) > 0) {

            foreach ($matchingSegments as $matchingSegment) {

                $segmentId = $matchingSegment->getId();

                if (!array_key_exists($segmentId, $segmentConnections)) {
                    continue;
                }

                // determine which LineSegment is closes to Bridge

                /**
                 * @var LineSegment
                 */
                $nearestLineSegment = null;

                $lineSegments = $matchingSegment->getPolygon()->getLineSegments();

                if ($lineSegments) {

                    $closestDistanceToBridge = null;

                    foreach ($lineSegments as $lineSegment) {

                        $points = $lineSegment->getEndpoints();
                        $points[] = $lineSegment->getCenter();

                        $distances = array();

                        foreach ($points as $point) {

                            $distance = $bridgeLocation->getDistance($point);

                            if ($distance > 0) {
                                $distances[] = $distance;
                            }
                        }

                        if ($distances) {

                            $closestDistance = min($distances);

                            if (($closestDistanceToBridge == null) || ($closestDistance < $closestDistanceToBridge)) {

                                $nearestLineSegment = $lineSegment;
                                $closestDistanceToBridge = $closestDistance;
                            }
                        }
                    }
                }

                if ($nearestLineSegment) {

                    $nearestLineSegmentEndPoints = $nearestLineSegment->getEndpoints();

                    // check if any connected Segment shares this LineSegment

                    $connectedSegmentIds = $segmentConnections[$segmentId];

                    foreach ($connectedSegmentIds as $connectedSegmentId) {

                        if ($connectedSegmentId == $segmentId) {
                            continue;
                        }

                        $connectedSegment = null;

                        if (array_key_exists($connectedSegmentId, $waterwaySegments)) {

                            $connectedSegment = $waterwaySegments[$connectedSegmentId];
                        }

                        if ($connectedSegment) {

                            $connectedSegmentLineSegments = $connectedSegment->getPolygon()->getLineSegments();

                            foreach ($connectedSegmentLineSegments as $connectedSegmentLineSegment) {

                                if ($connectedSegmentLineSegment->isEndPoint($nearestLineSegmentEndPoints[0])) {

                                    if ($connectedSegmentLineSegment->isEndPoint($nearestLineSegmentEndPoints[1])) {

                                        // it's a match!

                                        $connectedSegments = array();
                                        $connectedSegments[] = $matchingSegment;
                                        $connectedSegments[] = $connectedSegment;

                                        break;
                                    }
                                }
                            }
                        }

                        if ($connectedSegments) {
                            break;
                        }
                    }

                    if ($connectedSegments) {

                        // determine heading into each segment as key

                        $nearestLineSegmentBearing = (int)$nearestLineSegmentEndPoints[0]->getBearing($nearestLineSegmentEndPoints[1]);

                        $perpendicularBearing = ($nearestLineSegmentBearing + 90);

                        while ($perpendicularBearing > 180) {

                            $perpendicularBearing = $perpendicularBearing - 180;
                        }

                        $perpendicularBearing1 = $perpendicularBearing;
                        $perpendicularBearing2 = $perpendicularBearing + 180;

                        $matchingSegmentCenter = $matchingSegment->getBounds()->getCenter();

                        $bearingToCenter = $bridgeLocation->getBearing($matchingSegmentCenter);

                        if (abs($bearingToCenter - $perpendicularBearing1) < abs($bearingToCenter - $perpendicularBearing2)) {

                            // $perpendicularBearing1 points to $matchingSegment

                            $connectedSegmentsByBearing[$perpendicularBearing1] = $matchingSegment;
                            $connectedSegmentsByBearing[$perpendicularBearing2] = $connectedSegments[1];
                        } else {

                            // $perpendicularBearing2 points to $matchingSegment

                            $connectedSegmentsByBearing[$perpendicularBearing1] = $connectedSegments[1];
                            $connectedSegmentsByBearing[$perpendicularBearing2] = $matchingSegment;
                        }
                    }
                }

                if ($connectedSegmentsByBearing) {
                    break;
                }
            }
        }

        return $connectedSegmentsByBearing;
    }
}
