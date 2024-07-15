<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Geo\Model\Polyline;
use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\WaterwaySegment;

class RouteCalculator
{

    /**
     * @var WaterwaySegment[]
     */
    private $waterwaySegments;

    /**
     * @param WaterwaySegment[] $waterwaySegments
     */
    public function initialize($waterwaySegments)
    {
        $this->waterwaySegments = $waterwaySegments;
    }

    /**
     * @param JourneySegment $targetJourneySegment
     * @param JourneySegment[] $previousJourneySegments
     * @return JourneySegment[]
     */
    public function calculateRoute($targetJourneySegment, $previousJourneySegments)
    {

        $calculatedRoute = array();

        $targetSegmentId = $targetJourneySegment->getSegmentId();

        $lastSegmentId = null;

        /**
         * @var JourneySegment
         */
        $lastJourneySegment = null;

        if ($previousJourneySegments) {

            $lastJourneySegment = end($previousJourneySegments);

            if ($lastJourneySegment) {

                $lastSegmentId = $lastJourneySegment->getSegmentId();
            }
        }

        if ($targetSegmentId && $lastSegmentId) {

            // check if segments are connected

            $isConnected = $this->isConnected($lastSegmentId, $targetSegmentId);

            if ($isConnected) {

                $calculatedRoute[] = $targetJourneySegment;
            } else {

                $shortestRoutePath = $this->findShortestRoutePath($lastJourneySegment, $targetJourneySegment);

                if ($shortestRoutePath) {

                    if ($shortestRoutePath[0]->getSegmentId() == $lastSegmentId) {

                        array_shift($shortestRoutePath);
                    }
                }

                $calculatedRoute = $shortestRoutePath;
            }
        } else {

            $calculatedRoute[] = $targetJourneySegment;
        }

        return $calculatedRoute;
    }

    /**
     * @param JourneySegment $startSegment
     * @param JourneySegment $targetSegment
     * @return JourneySegment[]
     */
    public function findShortestRoutePath($startSegment, $targetSegment)
    {

        /**
         * @var JourneySegment[]
         */
        $routePath = array();

        $routePath[] = $startSegment;

        $routeStarted = false;

        $segmentIds = $this->findShortestRoute($startSegment->getSegmentId(), $targetSegment->getSegmentId());

        foreach ($segmentIds as $segmentId) {

            if ($routeStarted) {

                if ($targetSegment->getSegmentId() == $segmentId) {

                    break;
                }

                $journeySegment = new JourneySegment();
                $journeySegment->setSegmentId($segmentId);

                if (array_key_exists($segmentId, $this->waterwaySegments)) {

                    $segment = $this->waterwaySegments[$segmentId];

                    $routePoints = $segment->getRoutePoints();

                    if ($routePoints && array_key_exists(0, $routePoints)) {

                        $routePoint = $routePoints[0];

                        if ($routePoint) {

                            $journeySegment->setFirstLocation($routePoint);
                            $journeySegment->setLastLocation($routePoint);
                        }
                    }
                }

                $routePath[] = $journeySegment;
            } else {

                if ($startSegment->getSegmentId() == $segmentId) {

                    $routeStarted = true;
                }
            }
        }

        $routePath[] = $targetSegment;

        if (count($routePath) > 2) {

            // set timestamps if possible

            if ($routePath[0]->getLastLocation() && $routePath[0]->getLastTimestamp()) {

                $startTimestamp = $routePath[0]->getLastTimestamp();

                $n = count($routePath) - 1;
                if ($routePath[$n]->getFirstLocation() && $routePath[$n]->getFirstTimestamp()) {

                    // calculate total distance and time

                    $totalTime = $routePath[$n]->getFirstTimestamp() - $routePath[0]->getLastTimestamp();

                    if ($totalTime > 0) {

                        $path = array();

                        $path[] = $routePath[0]->getLastLocation();

                        for ($i = 1; $i < $n; $i++) {

                            if ($routePath[$i]->getFirstLocation()) {

                                $path[] = $routePath[$i]->getFirstLocation();
                            }
                        }

                        $path[] = $routePath[$n]->getFirstLocation();

                        $polyLine = new Polyline($path);

                        $totalDistance = $polyLine->getLineLength();

                        if ($totalDistance > 0) {

                            /**
                             * @var LatLng[]
                             */
                            $currentPath = array();

                            $currentPath[] = $routePath[0]->getLastLocation();

                            for ($i = 1; $i < $n; $i++) {

                                if ($routePath[$i]->getFirstLocation()) {

                                    $currentPath[] = $routePath[$i]->getFirstLocation();

                                    $polyLine = new Polyline($currentPath);

                                    $currentDistance = $polyLine->getLineLength();

                                    $currentTime = round(($currentDistance / $totalDistance) * $totalTime);

                                    $routePath[$i]->setFirstTimestamp($startTimestamp + $currentTime);
                                    $routePath[$i]->setLastTimestamp($startTimestamp + $currentTime);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $routePath;
    }

    public function findShortestRoute($startSegmentId, $targetSegmentId)
    {

        $shortestRoute = null;

        $nodes = array();

        $node = array();
        $node['distance'] = 0;
        $node['visited'] = false;

        $nodes[$startSegmentId] = $node;

        do {

            $anyVisited = false;

            $visitSegmentId = null;

            $visitSegmentIds = array();

            // check unvisited nodes

            foreach ($nodes as $segmentId => $node) {

                if (!$node['visited']) {

                    $visitSegmentIds[] = $segmentId;
                }
            }

            if ($visitSegmentIds) {

                // sort by distance, shortest first

                $segmentsByDistance = array();

                foreach ($visitSegmentIds as $visitSegmentId) {

                    $distance = $nodes[$visitSegmentId]['distance'];

                    $segmentsByDistance[$distance][] = $visitSegmentId;
                }

                ksort($segmentsByDistance);

                foreach (array_keys($segmentsByDistance) as $distance) {

                    $visitSegmentId = $segmentsByDistance[$distance][0];

                    break;
                }
            }

            if ($visitSegmentId) {

                $segmentId = $visitSegmentId;

                if (array_key_exists($segmentId, $this->waterwaySegments)) {

                    $segment = $this->waterwaySegments[$segmentId];

                    $connectedSegmentIds = $segment->getConnectedSegmentIds();

                    foreach ($connectedSegmentIds as $connectedSegmentId) {

                        $distance = $nodes[$segmentId]['distance'] + 1;

                        if (array_key_exists($connectedSegmentId, $this->waterwaySegments)) {

                            $segment = $this->waterwaySegments[$connectedSegmentId];

                            if (array_key_exists($connectedSegmentId, $nodes)) {

                                if ($distance < $nodes[$connectedSegmentId]['distance']) {

                                    $nodes[$connectedSegmentId]['distance'] = $distance;
                                    $nodes[$connectedSegmentId]['previous'] = $segmentId;
                                }
                            } else {

                                // create new unvisited node

                                $node = array();
                                $node['distance'] = $distance;
                                $node['visited'] = false;
                                $node['previous'] = $segmentId;

                                $nodes[$connectedSegmentId] = $node;
                            }
                        }
                    }

                    $anyVisited = true;
                }

                // mark node visited
                $nodes[$segmentId]['visited'] = true;

                if ($segmentId == $targetSegmentId) {

                    break;
                }
            }
        } while ($anyVisited);

        if (array_key_exists($targetSegmentId, $nodes)) {

            if ($nodes[$targetSegmentId]['distance'] > 0) {

                // work backwards to start node

                $reversePath = array($targetSegmentId);

                $previousSegmentId = $nodes[$targetSegmentId]['previous'];

                while ($previousSegmentId) {

                    $reversePath[] = $previousSegmentId;

                    $previousNode = $nodes[$previousSegmentId];

                    $previousSegmentId = null;

                    if (array_key_exists('previous', $previousNode)) {

                        $previousSegmentId = $previousNode['previous'];
                    }
                }

                $forwardPath = array_reverse($reversePath);

                if ($forwardPath[0] == $startSegmentId) {

                    if ($forwardPath[count($forwardPath) - 1] == $targetSegmentId) {

                        $shortestRoute = $forwardPath;
                    }
                }
            }
        }

        return $shortestRoute;
    }

    /**
     *
     */
    public function isConnected($segmentId, $otherSegmentId)
    {

        $isConnected = false;

        if ($segmentId && $otherSegmentId) {

            $connectedSegmentIds = $this->getConnectedSegmentIds($segmentId);

            if ($connectedSegmentIds) {

                if (in_array($otherSegmentId, $connectedSegmentIds)) {

                    $isConnected = true;
                }
            }
        }

        return $isConnected;
    }

    /**
     * @param int $segmentId
     * @return int[]
     */
    public function getConnectedSegmentIds($segmentId)
    {

        $connectedSegmentIds = array();

        if ($segmentId && $this->waterwaySegments) {

            $segment = null;

            if (array_key_exists($segmentId, $this->waterwaySegments)) {

                $segment = $this->waterwaySegments[$segmentId];
            }

            if ($segment) {

                if ($segment->getConnectedSegmentIds()) {

                    $connectedSegmentIds = $segment->getConnectedSegmentIds();
                }
            }
        }

        return $connectedSegmentIds;
    }
}
