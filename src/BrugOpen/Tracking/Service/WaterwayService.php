<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Core\Context;
use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Geo\Model\LatLngBounds;
use BrugOpen\Geo\Model\LineSegment;
use BrugOpen\Geo\Model\Polygon;
use BrugOpen\Tracking\Model\WaterwaySegment;

class WaterwayService
{

    /**
     * @var Context
     */
    private $context;

    /**
     *
     * @var TableManager
     */
    private $tableManager;

    /**
     * @param Context $context
     */
    public function initialize(Context $context)
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
     * @param \BrugOpen\Db\Service\TableManager $tableManager
     */
    public function setTableManager($tableManager)
    {
        $this->tableManager = $tableManager;
    }

    /**
     *
     * @return WaterwaySegment[]
     */
    public function loadWaterwaySegments()
    {

        /**
         * @var WaterwaySegment[]
         */
        $segments = array();

        $keys = array();

        if ($records = $this->context->getDataStore()->findRecords('bo_waterway_segment', $keys)) {

            $connectedSegments = array();

            foreach ($records as $record) {

                $segmentId = (int)$record['id'];

                $segment = new WaterwaySegment();
                $segment->setId($segmentId);
                $segment->setTitle($record['title']);

                $path = array();

                $coordLines = explode("\n", $record['coordinates']);

                foreach ($coordLines as $coordLine) {

                    $lineParts = explode(',', $coordLine);

                    if (sizeof($lineParts) == 2) {

                        $lat = (float)trim($lineParts[0]);
                        $lon = (float)trim($lineParts[1]);
                        $path[] = new LatLng($lat, $lon);
                    }
                }

                $routePoints = array();

                if ($record['route_points'] != '') {

                    $lines = explode("\n", $record['route_points']);

                    foreach ($lines as $line) {

                        $lineParts = explode(',', $line);

                        if (sizeof($lineParts) == 2) {

                            $lat = (float)trim($lineParts[0]);
                            $lon = (float)trim($lineParts[1]);
                            $routePoints[] = new LatLng($lat, $lon);
                        }
                    }
                }

                $segment->setRoutePoints($routePoints);

                if ($record['connected_segments'] != '') {

                    $connectedSegments[$segmentId] = explode(',', $record['connected_segments']);
                }

                $polygon = new Polygon($path);

                $segment->setPolygon($polygon);

                $segments[$segmentId] = $segment;
            }

            foreach (array_keys($connectedSegments) as $segmentId) {

                $connectedSegmentIds = array();

                foreach ($connectedSegments[$segmentId] as $connectedSegmentId) {

                    if (array_key_exists($connectedSegmentId, $segments)) {

                        $connectedSegmentIds[] = $connectedSegmentId;
                    }
                }

                $segments[$segmentId]->setConnectedSegmentIds($connectedSegmentIds);
            }
        }

        return $segments;
    }

    /**
     * @param WaterwaySegment $waterwaySegment
     * @param WaterwaySegment[] $waterwaySegments
     * @return int[]
     */
    public function determineConnectedWaterwaySegments($waterwaySegment, $waterwaySegments)
    {

        $thisSegmentId = $waterwaySegment->getId();
        $thisSegment = $waterwaySegment;

        $thisSegmentBounds = $thisSegment->getBounds();

        foreach ($waterwaySegments as $otherSegment) {

            $otherSegmentId = $otherSegment->getId();

            if ($otherSegmentId == $thisSegmentId) {

                continue;
            }

            $boundsMatch = false;

            $otherSegmentBounds = $otherSegment->getBounds();

            if ($thisSegmentBounds->overlaps($otherSegmentBounds)) {

                $boundsMatch = true;
            }

            if ($boundsMatch) {

                $hasMatchingLineSegment = false;

                foreach ($thisSegment->getPolygon()->getLineSegments() as $thisLineSegment) {

                    $thisLineSegmentPoints = $thisLineSegment->getEndpoints();

                    foreach ($otherSegment->getPolygon()->getLineSegments() as $otherLineSegment) {

                        if ($otherLineSegment->isEndPoint($thisLineSegmentPoints[0])) {

                            if ($otherLineSegment->isEndPoint($thisLineSegmentPoints[1])) {

                                $hasMatchingLineSegment = true;
                                break;
                            }
                        }
                    }
                }

                if ($hasMatchingLineSegment) {

                    $segmentConnections[] = $otherSegmentId;
                }
            }

            sort($segmentConnections);
        }

        return $segmentConnections;
    }

    /**
     * @param int[] $onlySegmentIds
     */
    public function updateConnectedWaterwaySegments($onlySegmentIds = null)
    {

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $segments = $this->loadWaterwaySegments();

            $segmentConnections = array();

            foreach ($segments as $segment) {

                $segmentId = $segment->getId();

                if ($onlySegmentIds !== null) {

                    if (!in_array($segmentId, $onlySegmentIds)) {

                        continue;
                    }
                }

                $connections = $this->determineConnectedWaterwaySegments($segment, $segments);

                $segmentConnections[$segmentId] = $connections;
            }

            if ($segmentConnections) {

                foreach ($segments as $segmentId => $segment) {

                    $connectedSegmentIds = array();

                    if (array_key_exists($segmentId, $segmentConnections)) {

                        $connectedSegmentIds = $segmentConnections[$segmentId];
                    }

                    $existingSegmentIds = array();

                    if ($segment->getConnectedSegmentIds()) {

                        $existingSegmentIds = $segment->getConnectedSegmentIds();
                    }

                    $needsUpdate = false;

                    foreach ($connectedSegmentIds as $connectedSegmentId) {

                        if (!in_array($connectedSegmentId, $existingSegmentIds)) {

                            $needsUpdate = true;
                        }
                    }

                    if ($existingSegmentIds) {

                        foreach ($existingSegmentIds as $connectedSegmentId) {

                            if (!in_array($connectedSegmentId, $connectedSegmentIds)) {

                                $needsUpdate = true;
                            }
                        }
                    }

                    if ($needsUpdate) {

                        sort($connectedSegmentIds);

                        $keys = array('id' => $segmentId);

                        $values = array('connected_segments' => implode(',', $connectedSegmentIds));

                        $tableManager->updateRecords('bo_waterway_segment', $values, $keys);
                    }
                }
            }
        }
    }

    /**
     * @param WaterwaySegment $segment
     * @return LatLng[]
     */
    public function determineSegmentsRoutePoints($segment)
    {

        $routePoints = array();

        $lineSegments = $segment->getPolygon()->getLineSegments();

        /**
         * @var LatLng[]
         */
        $segmentCenters = array();

        // collect segment centers by segment index

        foreach ($lineSegments as $i => $lineSegment) {

            $segmentCenters[$i] = $lineSegment->getCenter();
        }

        $longestDistance = null;
        $longestDistanceSegments = array();

        foreach ($segmentCenters as $i => $segmentCenter) {

            $distances = array();

            foreach ($segmentCenters as $j => $otherSegmentCenter) {

                if ($i == $j) {
                    continue;
                }

                $distance = $segmentCenter->getDistance($otherSegmentCenter);

                if (($longestDistance == null) || ($distance > $longestDistance)) {

                    $longestDistance = $distance;
                    $longestDistanceSegments = array($i, $j);
                }
            }
        }

        if ($longestDistanceSegments) {

            $center1 = $segmentCenters[$longestDistanceSegments[0]];
            $center2 = $segmentCenters[$longestDistanceSegments[1]];

            $centerLineSegment = new LineSegment($center1, $center2);
            $centerLine = $centerLineSegment->getLine();

            $centerLineCenter = $centerLineSegment->getCenter();

            // return array($centerLineCenter);

            $perpendicularLine = $centerLine->getPerpendicularLine($centerLineCenter);

            $crossPoints = array();

            if ($perpendicularLine) {

                // collect line segments the perpendicular line crosses

                foreach ($lineSegments as $lineSegment) {

                    $line = $lineSegment->getLine();

                    if ($line) {

                        $crossPoint = $line->getIntersectionPoint($perpendicularLine);

                        if ($crossPoint) {

                            // check if crosspoint is inside line segment

                            $endpoints = $lineSegment->getEndpoints();
                            $boundingBox = new LatLngBounds($endpoints);

                            if ($boundingBox->isInBounds($crossPoint)) {

                                $crossPoints[] = $crossPoint;
                            }
                        }
                    }
                }
            }

            if (count($crossPoints) == 2) {

                // create lineSegment between crossPoints

                $lineSegment = new LineSegment($crossPoints[0], $crossPoints[1]);

                $routePoint = $lineSegment->getCenter();

                $routePoints[] = $routePoint;
            }
        }

        return $routePoints;
    }

    public function updateWaterwaySegmentsRoutePoints()
    {

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $segments = $this->loadWaterwaySegments(false);

            foreach ($segments as $segment) {

                // if ($segment->getId() != 211) {
                //     continue;
                // }

                $routePointsText = '';

                $routePoints = $this->determineSegmentsRoutePoints($segment);

                if ($routePoints) {

                    $routePointsParts = array();

                    foreach ($routePoints as $routePoint) {

                        $routePointsParts[] = $routePoint->toString();
                    }

                    $routePointsText = implode("\n", $routePointsParts);
                }

                $values = array('route_points' => $routePointsText);
                $keys = array('id' => $segment->getId());

                $tableManager->updateRecords('bo_waterway_segment', $values, $keys);
            }
        }
    }
}
