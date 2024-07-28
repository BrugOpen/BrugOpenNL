<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\VesselJourney;
use BrugOpen\Tracking\Model\WaterwaySegment;

class JourneyReconstructor
{


    /**
     * @var Context
     */
    private $context;

    /**
     * @var WaterwayService
     */
    private $waterwayService;

    /**
     *
     * @var WaterwaySegment[]
     */
    private $waterwaySegments;

    /**
     * @var int[][]
     */
    private $segmentConnections;

    /**
     * @var RouteCalculator
     */
    private $routeCalculator;

    public function initialize($context)
    {
        $this->context = $context;

        $waterwayService = $context->getService('BrugOpen.WaterwayService');

        $this->waterwayService = $waterwayService;

        $segments = $waterwayService->loadWaterwaySegments();
        $this->initalizeWaterwaySegments($segments);
    }

    /**
     *
     * @param WaterwaySegment[] $segments
     */
    public function initalizeWaterwaySegments($segments)
    {

        $this->waterwaySegments = $segments;

        $segmentConnections = $this->collectSegmentConnections($segments);

        $this->segmentConnections = $segmentConnections;
    }

    /**
     * @return WaterwayService
     */
    public function getWaterwayService()
    {
        return $this->waterwayService;
    }

    /**
     * @param WaterwayService $waterwayService
     */
    public function setWaterwayService($waterwayService)
    {
        $this->waterwayService = $waterwayService;
    }

    /**
     * @return RouteCalculator
     */
    public function getRouteCalculator()
    {

        if ($this->routeCalculator == null) {

            $routeCalculator = new RouteCalculator();
            $routeCalculator->initialize($this->waterwaySegments);

            $this->routeCalculator = $routeCalculator;
        }

        return $this->routeCalculator;
    }

    /**
     * @param WaterwaySegment[] $segments
     * @return int[][]
     */
    public function collectSegmentConnections($segments)
    {

        $segmentConnections = array();

        foreach ($segments as $segment) {

            $segmentId = (int)$segment->getId();
            $connectedSegments = $segment->getConnectedSegmentIds();

            if ($segmentId && $connectedSegments) {

                foreach ($connectedSegments as $connectedSegmentId) {

                    $connectedSegmentId = (int)$connectedSegmentId;

                    if (!array_key_exists($segmentId, $segmentConnections)) {
                        $segmentConnections[$segmentId] = array();
                    }

                    if (!array_key_exists($connectedSegmentId, $segmentConnections)) {
                        $segmentConnections[$connectedSegmentId] = array();
                    }

                    $segmentConnections[$segmentId][$connectedSegmentId] = $connectedSegmentId;
                    $segmentConnections[$connectedSegmentId][$segmentId] = $segmentId;
                }
            }
        }

        return $segmentConnections;
    }

    /**
     * @param int $segmentId
     * @param int $otherSegmentId
     * @return bool
     */
    public function waterwaySegmentsConnected($segmentId, $otherSegmentId)
    {

        $waterwaySegmentsConnected = false;

        if ($segmentId && $otherSegmentId) {

            if (array_key_exists($segmentId, $this->segmentConnections)) {

                $waterwaySegmentsConnected = array_key_exists($otherSegmentId, $this->segmentConnections[$segmentId]);
            }
        }

        return $waterwaySegmentsConnected;
    }

    /**
     * @param VesselJourney $journey
     */
    public function reconstructFullJourney($journey)
    {

        $reconstructedJourney = null;

        $allSegmentsConnected = $this->journeySegmentsConnected($journey);

        if (!$allSegmentsConnected) {
            $reconstructedJourneySegments = $this->reconstructJourneySegments($journey);
            $journey->setJourneySegments($reconstructedJourneySegments);
        }

        return $reconstructedJourney;
    }

    /**
     * @param VesselJourney $journey
     * @return boolean
     */
    public function journeySegmentsConnected($journey)
    {

        $allSegmentsConnected = true;

        $journeySegments = $journey->getJourneySegments();

        if (is_array($journeySegments) && (count($journeySegments) > 1)) {

            /**
             * @var JourneySegment
             */
            $previousJourneySegment = null;

            foreach ($journeySegments as $journeySegment) {

                if ($previousJourneySegment == null) {

                    $previousJourneySegment = $journeySegment;
                    continue;
                }

                $segmentsConnected = false;

                $segmentId = $journeySegment->getSegmentId();
                $previousSegmentId = $previousJourneySegment->getSegmentId();

                if ($segmentId && $previousSegmentId) {

                    if ($this->waterwaySegmentsConnected($segmentId, $previousSegmentId)) {
                        $segmentsConnected = true;
                    }
                }

                if (!$segmentsConnected) {

                    $allSegmentsConnected = false;
                    break;
                }

                $previousJourneySegment = $journeySegment;
            }
        }

        return $allSegmentsConnected;
    }

    /**
     * @param VesselJourney $journey
     * @return JourneySegment[]
     */
    public function reconstructCurrentWaterwayJourneySegments($journey)
    {

        $reconstructedJourneySegments = array();

        $journeySegments = $journey->getJourneySegments();

        // collect locations by timestamp
        $locationByTimestamp = array();

        foreach ($journeySegments as $journeySegment) {

            $timestamp = $journeySegment->getFirstTimestamp();
            $location = $journeySegment->getFirstLocation();

            if ($timestamp && $location) {

                $locationByTimestamp[$timestamp] = $location;
            }

            $timestamp = $journeySegment->getLastTimestamp();
            $location = $journeySegment->getLastLocation();

            if ($timestamp && $location) {

                $locationByTimestamp[$timestamp] = $location;
            }
        }

        ksort($locationByTimestamp);

        /**
         * @var JourneySegment
         */
        $currentJourneySegment = null;

        foreach ($locationByTimestamp as $timestamp => $location) {

            $segmentId = null;

            if ($currentJourneySegment) {

                // check if location is still in last segment
                $currentSegmentId = $currentJourneySegment->getSegmentId();
                $currentSegment = $this->waterwaySegments[$currentSegmentId];

                if ($currentSegment->getPolygon()->isPointInPolygon($location)) {

                    // location is still in current segment
                    $segmentId = $currentSegmentId;
                }
            }

            if (!$segmentId) {

                // find matching segment
                $matchingSegments = $this->waterwayService->getWaterwaySegmentsByLocation($location);

                if ($matchingSegments) {
                    foreach ($matchingSegments as $matchingSegment) {
                        $segmentId = $matchingSegment->getId();
                        break;
                    }
                }
            }

            if ($segmentId) {

                $startNewSegment = false;

                if ($currentJourneySegment) {

                    if ($currentJourneySegment->getSegmentId() != $segmentId) {

                        $startNewSegment = true;
                    }
                } else {

                    $startNewSegment = true;
                }

                if ($startNewSegment) {

                    $currentJourneySegment = new JourneySegment();
                    $currentJourneySegment->setSegmentId($segmentId);
                    $currentJourneySegment->setFirstTimestamp($timestamp);
                    $currentJourneySegment->setFirstLocation($location);
                    $currentJourneySegment->setLastTimestamp($timestamp);
                    $currentJourneySegment->setLastLocation($location);

                    $reconstructedJourneySegments[] = $currentJourneySegment;
                } else {

                    if ($currentJourneySegment) {

                        if ($timestamp > $currentJourneySegment->getLastTimestamp()) {

                            $currentJourneySegment->setLastTimestamp($timestamp);
                            $currentJourneySegment->setLastLocation($location);
                        }
                    }
                }
            }
        }

        return $reconstructedJourneySegments;
    }

    /**
     * @param VesselJourney $journey
     * @return JourneySegment[]
     */
    public function reconstructJourneySegments($journey)
    {

        $reconstructedJourneySegments = array();

        // reconstruct to current waterway

        $currentWaterwayJourneySegments = $this->reconstructCurrentWaterwayJourneySegments($journey);

        if (is_array($currentWaterwayJourneySegments) && (count($currentWaterwayJourneySegments) > 1)) {

            /**
             * @var JourneySegment
             */
            $previousJourneySegment = null;

            foreach ($currentWaterwayJourneySegments as $journeySegment) {

                $segmentsConnected = false;

                if ($previousJourneySegment == null) {

                    $reconstructedJourneySegments[] = $journeySegment;
                    $previousJourneySegment = $journeySegment;
                    continue;
                }

                $segmentId = $journeySegment->getSegmentId();
                $previousSegmentId = $previousJourneySegment->getSegmentId();

                if ($segmentId && $previousSegmentId) {

                    if ($this->waterwaySegmentsConnected($segmentId, $previousSegmentId)) {

                        $segmentsConnected = true;
                    }
                }

                if (!$segmentsConnected) {

                    // find shortest path between previous and current segment

                    $routeCalculator = $this->getRouteCalculator();

                    if ($routeCalculator) {

                        $previousJourneySegments = array($previousJourneySegment);
                        $calculatedRoute = $routeCalculator->calculateRoute($journeySegment, $previousJourneySegments);

                        if ($calculatedRoute) {

                            // pop off last element if needed
                            $lastSegment = $calculatedRoute[count($calculatedRoute) - 1];
                            if ($lastSegment->getSegmentId() == $segmentId) {
                                array_pop($calculatedRoute);
                            }

                            foreach ($calculatedRoute as $intermediateSegment) {

                                $reconstructedJourneySegments[] = $intermediateSegment;
                            }
                        }
                    }
                }

                $reconstructedJourneySegments[] = $journeySegment;

                $previousJourneySegment = $journeySegment;
            }
        } else {

            $reconstructedJourneySegments = $currentWaterwayJourneySegments;
        }
        return $reconstructedJourneySegments;
    }
}
