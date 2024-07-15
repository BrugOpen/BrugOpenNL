<?php
namespace BrugOpen\Tracking\Service;

use BrugOpen\Core\Context;
use BrugOpen\Model\Bridge;
use BrugOpen\Model\BridgePassage;
use BrugOpen\Service\BridgeService;
use BrugOpen\Tracking\Model\JourneySegment;

class BridgePassageCalculator
{

    /**
     * @var Context
     */
    private $context;

    /**
     * @var Bridge[]
     */
    private $bridges;

    /**
     * @var WaterwaySegment[]
     */
    private $waterwaySegments;

    /**
     * @var RouteCalculator
     */
    private $routeCalculator;

    /**
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
    }

    /**
     * @param WaterwaySegment[] $waterwaySegments
     */
    public function setWaterwaySegments($waterwaySegments)
    {
        $this->waterwaySegments = $waterwaySegments;
        $this->routeCalculator = null;
    }

    /**
     * @param Bridge[] $bridges
     */
    public function setBridges($bridges)
    {
        $this->bridges = $bridges;
    }

    /**
     * @return Bridge[]
     */
    public function getBridges()
    {

        if ($this->bridges == null) {

            if ($this->context) {

                /**
                 * @var BridgeService
                 */
                $bridgeService = $this->context->getService('BrugOpen.BridgeService');

                $this->bridges = $bridgeService->getAllBridges();

            }

        }

        return $this->bridges;

    }

    /**
     * @param JourneySegment $exitingJourneySegment
     * @param JourneySegment $enteringJourneySegment
     * @param JourneySegment[] $previousJourneySegments
     * @return BridgePassage[]
     */
    public function determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments)
    {

        $bridgePassages = array();

        $exitingSegmentId = null;
        $enteringSegmentId = null;

        if ($exitingJourneySegment) {

            $exitingSegmentId = $exitingJourneySegment->getSegmentId();

        }

        if ($enteringJourneySegment) {

            $enteringSegmentId = $enteringJourneySegment->getSegmentId();

        }

        if ($enteringSegmentId && $exitingSegmentId) {

            if ($enteringSegmentId != $exitingSegmentId) {

                // check if segments are connected

                $isConnected = false;

                $connectedSegmentIds = array();

                $waterwaySegments = $this->getWaterwaySegments();

                if (array_key_exists($enteringSegmentId, $waterwaySegments)) {

                    $enteringSegment = $waterwaySegments[$enteringSegmentId];

                    if ($enteringSegment) {

                        $connectedSegmentIds = $enteringSegment->getConnectedSegmentIds();

                    }

                }

                if ($connectedSegmentIds) {

                    if (in_array($exitingSegmentId, $connectedSegmentIds)) {

                        $isConnected = true;

                    }

                }

                /**
                 * @var JourneySegment[]
                 */
                $connectingJourneySegmentPairs = array();

                if ($isConnected) {

                    $connectingJourneySegmentPairs[] = array($exitingJourneySegment, $enteringJourneySegment);

                } else {

                    // collect intermediate segments

                    $routeCalculator = $this->getRouteCalculator();

                    if ($routeCalculator) {

                        // add exitingsegment to previousjourneysegments if needed

                        $addExitingSegment = true;

                        if ($previousJourneySegments) {

                            $lastJourneySegment = end($previousJourneySegments);

                            if ($lastJourneySegment->getSegmentId() == $exitingSegmentId) {

                                $addExitingSegment = false;

                            }

                        }

                        if ($addExitingSegment) {

                            $previousJourneySegments[] = $exitingJourneySegment;

                        }

                        $calculatedRoute = $routeCalculator->calculateRoute($enteringJourneySegment, $previousJourneySegments);

                        if ($calculatedRoute) {

                            $routeSegments = array_values($calculatedRoute);
                            $n = count($routeSegments) - 1;

                            $connectingJourneySegmentPairs[] = array($exitingJourneySegment, $routeSegments[0]);

                            for ($i = 0; $i < $n; $i++) {

                                $connectingJourneySegmentPairs[] = array($routeSegments[$i], $routeSegments[$i + 1]);

                            }

                        }

                    }

                }

                foreach ($connectingJourneySegmentPairs as $connectingJourneySegmentPair) {

                    $journeySegment1 = $connectingJourneySegmentPair[0];
                    $journeySegment2 = $connectingJourneySegmentPair[1];

                    $journeySegmentId1 = $journeySegment1->getSegmentId();
                    $journeySegmentId2 = $journeySegment2->getSegmentId();

                    $connectedBridge = $this->getConnectedBridge($journeySegmentId1, $journeySegmentId2);

                    if ($connectedBridge) {

                        // determine direction
                        $passageDirection = $this->getPassageDirection($connectedBridge, $journeySegmentId2);

                        // determine timestamp
                        $passageTime = $this->calculatePassageTime($exitingJourneySegment, $enteringJourneySegment, $connectedBridge);

                        $datetimePassage = null;

                        if ($passageTime) {

                            $datetimePassage = new \DateTime();
                            $datetimePassage->setTimestamp($passageTime);

                        }

                        // create Passage
                        $bridgePassage = new BridgePassage();
                        $bridgePassage->setBridgeId($connectedBridge->getId());
                        $bridgePassage->setDirection($passageDirection);
                        $bridgePassage->setDatetimePassage($datetimePassage);

                        $bridgePassages[] = $bridgePassage;

                    }

                }

            }

        }

        return $bridgePassages;

    }

    /**
     * @param int $segmentId1
     * @param int $segmentId2
     * @return Bridge|null
     */
    public function getConnectedBridge($segmentId1, $segmentId2)
    {

        $connectedBridge = null;

        $bridges = $this->getBridges();

        if ($segmentId1 && $segmentId2 && $bridges) {

            foreach ($bridges as $bridge) {

                $connectedSegmentIds = $bridge->getConnectedSegmentIds();

                if ($connectedSegmentIds) {

                    if (in_array($segmentId1, $connectedSegmentIds)) {

                        if (in_array($segmentId2, $connectedSegmentIds)) {

                            $connectedBridge = $bridge;
                            break;

                        }

                    }

                }

            }

        }

        return $connectedBridge;

    }

    /**
     * @param Bridge $bridge
     * @param int $enteringSegmentId
     * @return int|null
     */
    public function getPassageDirection($bridge, $enteringSegmentId)
    {
        $passageDirection = null;

        if ($bridge && $enteringSegmentId) {

            $connectedSegmentIds = $bridge->getConnectedSegmentIds();

            if ($connectedSegmentIds) {

                foreach ($connectedSegmentIds as $direction => $segmentId) {

                    if ($segmentId == $enteringSegmentId) {

                        $passageDirection = $direction;
                        break;

                    }

                }

            }

        }

        return $passageDirection;

    }

    /**
     * @param JourneySegment $exitingJourneySegment
     * @param JourneySegment $enteringJourneySegment
     * @param Bridge $bridge
     * @return int|null
     */
    public function calculatePassageTime($exitingJourneySegment, $enteringJourneySegment, $bridge)
    {

        $passageTime = null;

        $exitingTime = null;
        $exitingLocation = null;

        $enteringTime = null;
        $enteringLocation = null;

        if ($exitingJourneySegment) {

            $exitingTime = $exitingJourneySegment->getLastTimestamp();
            $exitingLocation = $exitingJourneySegment->getLastLocation();

        }

        if ($enteringJourneySegment) {

            $enteringTime = $enteringJourneySegment->getFirstTimestamp();
            $enteringLocation = $enteringJourneySegment->getFirstLocation();

        }

        $bridgeLocation = null;

        if ($bridge) {

            $bridgeLocation = $bridge->getLatLng();

        }

        if ($exitingTime && $exitingLocation) {

            if ($enteringTime && $enteringLocation) {

                $totalTime = $enteringTime - $exitingTime;

                if ($totalTime > 0) {

                    $distance1 = $exitingLocation->getDistance($bridgeLocation);
                    $distance2 = $enteringLocation->getDistance($bridgeLocation);

                    $totalDistance = $distance1 + $distance2;

                    if ($totalDistance) {

                        $passageTime = $exitingTime + round(($distance1 / $totalDistance) * $totalTime);

                    }

                }

            }

        }

        return $passageTime;

    }

    /**
     * @return RouteCalculator
     */
    public function getRouteCalculator()
    {

        if ($this->routeCalculator == null) {

            $waterwaySegments = $this->getWaterwaySegments();

            $routeCalculator = new RouteCalculator();
            $routeCalculator->initialize($waterwaySegments);

            $this->routeCalculator = $routeCalculator;

        }

        return $this->routeCalculator;

    }

    /**
     * @return WaterwaySegment[]
     */
    public function getWaterwaySegments()
    {

        if ($this->waterwaySegments == null) {

            if ($this->context) {

                $waterwayService = $this->context->getService('BrugOpen.WaterwayService');

                if ($waterwayService) {

                    $waterwaySegments = $waterwayService->loadWaterwaySegments();
                    $this->waterwaySegments = $waterwaySegments;

                }

            }

        }

        return $this->waterwaySegments;

    }

}
