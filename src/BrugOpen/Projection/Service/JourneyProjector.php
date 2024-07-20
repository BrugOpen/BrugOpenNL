<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Geo\Model\Polyline;
use BrugOpen\Model\Bridge;
use BrugOpen\Projection\Model\ProjectedBridgePassage;
use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\VesselJourney;
use BrugOpen\Tracking\Model\WaterwaySegment;

class JourneyProjector
{

    /**
     * @var Context
     */
    private $context;

    /**
     *
     * @var WaterwaySegment[]
     */
    private $waterwaySegments = array();

    public function initialize($context)
    {
        $this->context = $context;

        $waterwayService = $context->getService('BrugOpen.WaterwayService');

        $segments = $waterwayService->loadWaterwaySegments();
        $this->initalizeWaterwaySegments($segments);
    }

    /**
     *
     * @param WaterwaySegment[] $segments
     */
    public function initalizeWaterwaySegments($segments)
    {
        $this->waterwaySegments = array();
        if ($segments) {
            $this->waterwaySegments = $segments;
        }
    }

    /**
     * @param int $currentSegmentId
     * @param VesselJourney[] $journeys
     * @param $reliability
     * @return int[]
     */
    public function projectJourneySegments($previousSegmentId, $currentSegmentId, $journeys, $reliability)
    {

        $numJourneys = count($journeys);

        $projectedSegmentIds = array();

        // collect vectors
        $vectors = array();

        foreach ($journeys as $journeyKey => $journey) {

            $journeySegments = $journey->getJourneySegments();

            if ($journeySegments) {

                $previousJourneySegmentId = null;

                foreach ($journeySegments as $j => $journeySegment) {

                    if ($previousJourneySegmentId == null) {
                        $previousJourneySegmentId = $journeySegment->getSegmentId();
                    }

                    if (count($journeySegments) > ($j + 1)) {

                        $nextJourneySegmentId = $journeySegments[$j + 1]->getSegmentId();

                        if ($previousJourneySegmentId && $nextJourneySegmentId) {

                            $vectors[$previousJourneySegmentId][$nextJourneySegmentId][] = $journeyKey;
                        }

                        $previousJourneySegmentId = $nextJourneySegmentId;
                    }
                }
            }
        }

        $matchingJourneyKeys = array();

        foreach (array_keys($journeys) as $journeyKey) {
            $matchingJourneyKeys[$journeyKey] = $journeyKey;
        }

        $segmentId = $currentSegmentId;

        do {

            if (array_key_exists($segmentId, $vectors)) {

                $segmentCandidates = array_keys($vectors[$segmentId]);

                $acceptedNextSegment = null;

                $bestCandidateSegmentId = null;

                $maxMatchingRoutes = null;

                if (count($segmentCandidates) > 0) {

                    foreach ($segmentCandidates as $segmentCandidate) {

                        $jouneyKeys = $vectors[$segmentId][$segmentCandidate];

                        $numMatchingRoutes = 0;

                        foreach ($jouneyKeys as $journeyKey) {

                            if (array_key_exists($journeyKey, $matchingJourneyKeys)) {

                                $numMatchingRoutes++;
                            }
                        }

                        if ($numMatchingRoutes > 0) {

                            if (($maxMatchingRoutes == null) || ($numMatchingRoutes > $maxMatchingRoutes)) {

                                $maxMatchingRoutes = $numMatchingRoutes;
                                $bestCandidateSegmentId = $segmentCandidate;
                            }
                        }
                    }
                }

                if ($bestCandidateSegmentId) {

                    if ($bestCandidateSegmentId != $previousSegmentId) {

                        // calculate reliability

                        if (($maxMatchingRoutes / $numJourneys) >= $reliability) {

                            $acceptedNextSegment = $bestCandidateSegmentId;
                        }
                    }
                }

                if ($acceptedNextSegment) {

                    if (in_array($acceptedNextSegment, $projectedSegmentIds)) {
                        break;
                    }

                    $projectedSegmentIds[] = $acceptedNextSegment;

                    // determine new $matchingJourneyKeys
                    $nextMatchingJourneyKeys = array();

                    foreach ($matchingJourneyKeys as $journeyKey) {

                        if (in_array($journeyKey, $vectors[$segmentId][$acceptedNextSegment])) {

                            $nextMatchingJourneyKeys[$journeyKey] = $journeyKey;
                        }
                    }

                    $matchingJourneyKeys = $nextMatchingJourneyKeys;

                    $segmentId = $acceptedNextSegment;
                } else {
                    break;
                }
            } else {
                break;
            }
        } while (true);

        return $projectedSegmentIds;
    }

    /**
     * @param int[] $segmentIds
     * @param VesselJourney[] $journeys
     * @return VesselJourney[]
     */
    public function findMatchingRouteJourneys($segmentIds, $journeys)
    {

        $matchingRouteJourneys = array();

        if ($segmentIds && $journeys) {

            foreach ($journeys as $journey) {

                if ($this->journeyMatchesRoute($journey, $segmentIds)) {

                    $matchingRouteJourneys[] = $journey;
                }
            }
        }

        return $matchingRouteJourneys;
    }

    /**
     * @param int[] $journeySegmentIds
     * @return int[]
     */
    public function findLongestSingleTrack($journeySegmentIds)
    {

        $longestSingleTrack = array();

        if ($journeySegmentIds) {

            $allJourneySegmentIds = array_values($journeySegmentIds);
            $lastJourneySegmentId = $allJourneySegmentIds[count($allJourneySegmentIds) - 1];

            while ($lastJourneySegmentId !== null) {

                $connectedSegmentIds = array();
                $nextJourneySegmentId = null;

                if (array_key_exists($lastJourneySegmentId, $this->waterwaySegments)) {

                    $connectedSegmentIds = $this->waterwaySegments[$lastJourneySegmentId]->getConnectedSegmentIds();
                }

                $possibleNextSegmentIds = array();

                foreach ($connectedSegmentIds as $connectedSegmentId) {

                    if (in_array($connectedSegmentId, $allJourneySegmentIds)) {
                        continue;
                    }

                    $possibleNextSegmentIds[] = $connectedSegmentId;
                }

                if (count($possibleNextSegmentIds) == 1) {
                    $nextJourneySegmentId = array_shift($possibleNextSegmentIds);
                }

                if ($nextJourneySegmentId) {
                    $allJourneySegmentIds[] = $nextJourneySegmentId;
                    $longestSingleTrack[] = $nextJourneySegmentId;
                }

                $lastJourneySegmentId = $nextJourneySegmentId;
            }
        }

        return $longestSingleTrack;
    }

    /**
     * @param VesselJourney $journey
     * @param int[] $segmentIds
     * @return boolean
     */
    public function journeyMatchesRoute($journey, $segmentIds)
    {

        $matchingRoute = false;

        if (count($segmentIds) > 1) {

            $firstSegmentId = $segmentIds[0];

            $journeySegments = $journey->getJourneySegments();

            $matchingFirstIndex = null;

            foreach ($journeySegments as $i => $journeySegment) {

                if ($journeySegment->getSegmentId() == $firstSegmentId) {

                    $matchingFirstIndex = $i;
                    break;
                }
            }

            if ($matchingFirstIndex !== null) {

                if (count($journeySegments) > ($matchingFirstIndex + count($segmentIds))) {

                    $matchingRoute = true;

                    for ($j = 0; $j < count($segmentIds); $j++) {

                        $journeySegmentId = $journeySegments[$matchingFirstIndex + $j]->getSegmentId();

                        if ($journeySegmentId != $segmentIds[$j]) {
                            $matchingRoute = false;
                            break;
                        }
                    }
                }
            }
        }

        return $matchingRoute;
    }

    /**
     * @param int[] $projectedSegmentIds
     * @param int $timestamp
     * @param \BrugOpen\Geo\Model\LatLng $latLng
     * @param VesselJourney[] $matchingRouteJourneys
     * @return ProjectedBridgePassage[]
     */
    public function projectBridgePassages($projectedSegmentIds, $timestamp, $latLng, $matchingRouteJourneys)
    {

        $projectedBridgePassages = array();

        $projectedBridgePassagesByBridge = array();

        foreach ($matchingRouteJourneys as $journey) {

            $matchingRouteJourneySegments = $this->collectMatchingRouteJourneySegments($journey, $projectedSegmentIds);

            if (count($matchingRouteJourneySegments) > 1) {

                // collect time to find passages in between

                $startTime = $matchingRouteJourneySegments[0]->getLastTimestamp();
                $endTime = $matchingRouteJourneySegments[count($matchingRouteJourneySegments) - 1]->getFirstTimestamp();

                if ($endTime > $startTime) {

                    // collect passages

                    $matchingPassages = array();

                    foreach ($journey->getPassages() as $passage) {

                        $passageTime = $passage->getDatetimePassage()->getTimestamp();

                        if (($passageTime > $startTime) && ($passageTime < $endTime)) {

                            $matchingPassages[] = $passage;
                        }
                    }

                    if ($matchingPassages) {

                        // calculate assumed time on original journey based on current location

                        // calculate distance and time between first two segments
                        $firstLocation = $matchingRouteJourneySegments[0]->getFirstLocation();
                        $secondLocation = $matchingRouteJourneySegments[1]->getFirstLocation();
                        $firstSegmentTime = $matchingRouteJourneySegments[0]->getFirstTimestamp();
                        $secondSegmentTime = $matchingRouteJourneySegments[1]->getFirstTimestamp();

                        $firstTwoSegmentsDistance = $firstLocation->getDistance($secondLocation);
                        $firstTwoSegmentsTime = $secondSegmentTime - $firstSegmentTime;

                        // calculate distance between latLng and second location
                        $nextSegmentDistance = $latLng->getDistance($secondLocation);

                        if ($firstTwoSegmentsDistance && $nextSegmentDistance) {

                            // calculate assumed time
                            $assumedTimestamp = round($secondSegmentTime - (($nextSegmentDistance / $firstTwoSegmentsDistance) * $firstTwoSegmentsTime));

                            foreach ($matchingPassages as $matchingPassage) {

                                $bridgeId = $matchingPassage->getBridgeId();
                                $projectedTimestamp = $matchingPassage->getDatetimePassage()->getTimestamp() - $assumedTimestamp + $timestamp;
                                $operationProbability = $matchingPassage->getOperationId() ? 1 : 0;

                                $datetimeProjectedPassage = new \DateTime('@' . $projectedTimestamp);

                                $projectedBridgePassage = new ProjectedBridgePassage();
                                $projectedBridgePassage->setBridgeId($bridgeId);
                                $projectedBridgePassage->setDatetimeProjectedPassage($datetimeProjectedPassage);
                                $projectedBridgePassage->setOperationProbability($operationProbability);

                                $projectedBridgePassagesByBridge[$bridgeId][] = $projectedBridgePassage;
                            }
                        }
                    }
                }
            }
        }

        if ($projectedBridgePassagesByBridge) {

            // merge passages by bridge

            foreach (array_keys($projectedBridgePassagesByBridge) as $bridgeId) {

                $numOperations = 0;
                $timesFromTimestamp = array();

                foreach ($projectedBridgePassagesByBridge[$bridgeId] as $projectionSource) {

                    $secondsAwayFromTimestamp = $projectionSource->getDatetimeProjectedPassage()->getTimestamp() - $timestamp;
                    $timesFromTimestamp[] = $secondsAwayFromTimestamp;

                    if ($projectionSource->getOperationProbability()) {

                        $numOperations++;
                    }
                }

                $cleanTimesFromTimestamp = $this->removeOutliers($timesFromTimestamp, 3);

                if ($cleanTimesFromTimestamp) {

                    $averageTimeFromTimestamp = round(array_sum($cleanTimesFromTimestamp) / count($cleanTimesFromTimestamp));

                    $operationProbability = $numOperations / count($projectedBridgePassagesByBridge[$bridgeId]);

                    $standardDeviation = null;
                    if (count($cleanTimesFromTimestamp) > 1) {
                        $standardDeviation = round($this->standardDeviaton($cleanTimesFromTimestamp));
                    }

                    $datetimeProjectedPassage = new \DateTime();
                    $datetimeProjectedPassage->setTimestamp($timestamp + $averageTimeFromTimestamp);

                    $projectedBridgePassage = new ProjectedBridgePassage();
                    $projectedBridgePassage->setBridgeId($bridgeId);
                    $projectedBridgePassage->setDatetimeProjectedPassage($datetimeProjectedPassage);
                    $projectedBridgePassage->setStandardDeviation($standardDeviation);
                    $projectedBridgePassage->setOperationProbability($operationProbability);

                    $projectedBridgePassages[] = $projectedBridgePassage;
                }
            }
        }
        return $projectedBridgePassages;
    }

    /**
     * @param Bridge $bridge
     * @param LatLng $startLocation
     * @param int[] $projectedSegmentIds
     * @return Polyline|null
     */
    public function projectRouteToBridge($bridge, $startLocation, $projectedSegmentIds)
    {

        $routeToBridge = null;

        // first check if bridge is between any two of the projected segment ids

        $previousSegmentId = null;
        $segmentsBeforeBridge = array();

        $connectedSegmentIds = $bridge->getConnectedSegmentIds();
        $bridgeInProjectedSegments = false;

        foreach ($projectedSegmentIds as $projectedSegmentId) {

            if ($previousSegmentId == null) {
                $segmentsBeforeBridge[] = $projectedSegmentId;
                $previousSegmentId = $projectedSegmentId;
                continue;
            }

            if (in_array($previousSegmentId, $connectedSegmentIds) && in_array($projectedSegmentId, $connectedSegmentIds)) {
                // $projectedSegmentId is on other side of the bridge
                $bridgeInProjectedSegments = true;
                break;
            }

            $segmentsBeforeBridge[] = $projectedSegmentId;
            $previousSegmentId = $projectedSegmentId;
        }

        if ($bridgeInProjectedSegments && $bridge->getLatLng()) {

            $routePointsToBridge = array();
            $routePointsToBridge[] = $startLocation;

            // in first segment, only add route point if in path

            foreach ($segmentsBeforeBridge as $i => $segmentId) {

                $segment = null;

                if (array_key_exists($segmentId, $this->waterwaySegments)) {

                    $segment = $this->waterwaySegments[$segmentId];
                }

                if ($segment) {

                    $segmentRoutePoints = $segment->getRoutePoints();

                    if ($segmentRoutePoints) {

                        $segmentRoutePoint = $segmentRoutePoints[0];

                        if ($i == 0) {

                            // skip route point if next segment is closer than routepoint or bridge
                            $nextPoint = $bridge->getLatLng();
                            if (count($segmentsBeforeBridge) > 1) {
                                $nextIndex = $i + 1;
                                if (array_key_exists($nextIndex, $segmentsBeforeBridge)) {
                                    $nextSegmentId = $segmentsBeforeBridge[$nextIndex];
                                    if (array_key_exists($nextSegmentId, $this->waterwaySegments)) {
                                        $nextSegment = $this->waterwaySegments[$nextSegmentId];
                                        $nextSegmentRoutePoints = $nextSegment->getRoutePoints();
                                        if ($nextSegmentRoutePoints) {
                                            $nextPoint = $nextSegmentRoutePoints[0];
                                        }
                                    }
                                }
                            }

                            if ($nextPoint) {

                                $distanceToNextPoint = $startLocation->getDistance($nextPoint);
                                $distanceFromRoutePointToNextPoint = $segmentRoutePoint->getDistance($nextPoint);

                                if ($distanceToNextPoint && $distanceFromRoutePointToNextPoint) {

                                    if ($distanceToNextPoint < $distanceFromRoutePointToNextPoint) {
                                        // this would cause a detour, do not add this segment route point
                                        continue;
                                    }
                                }
                            }
                        }

                        $routePointsToBridge[] = $segmentRoutePoint;
                    }
                }
            }

            // finally add bridge
            $routePointsToBridge[] = $bridge->getLatLng();
            $routeToBridge = new Polyline($routePointsToBridge);
        }

        return $routeToBridge;
    }

    /**
     * @param VesselJourney $journey
     * @param int[] $segmentIds
     * @return JourneySegment[]
     */
    public function collectMatchingRouteJourneySegments($journey, $segmentIds)
    {

        $matchingRouteJourneySegments = array();

        $firstSegmentId = $segmentIds[0];

        $journeySegments = $journey->getJourneySegments();

        $matchingFirstIndex = null;

        foreach ($journeySegments as $i => $journeySegment) {

            if ($journeySegment->getSegmentId() == $firstSegmentId) {

                $matchingFirstIndex = $i;
                break;
            }
        }

        if ($matchingFirstIndex !== null) {

            if (count($journeySegments) > ($matchingFirstIndex + count($segmentIds))) {

                for ($j = 0; $j < count($segmentIds); $j++) {

                    $journeySegment = $journeySegments[$matchingFirstIndex + $j];

                    $journeySegmentId = $journeySegment->getSegmentId();

                    if ($journeySegmentId != $segmentIds[$j]) {

                        $matchingRouteJourneySegments = array();
                        break;
                    }

                    $matchingRouteJourneySegments[] = $journeySegment;
                }
            }
        }

        return $matchingRouteJourneySegments;
    }


    protected function standardDeviaton($elements)
    {
        $numElements = count($elements);

        $variance = 0.0;

        // calculating mean using array_sum() method
        $average = array_sum($elements) / $numElements;

        foreach ($elements as $element) {
            // sum of squares of differences between
            // all numbers and means.
            $variance += pow(($element - $average), 2);
        }

        return (float)sqrt($variance / $numElements);
    }

    protected function removeOutliers($elements, $magnitude)
    {

        $count = count($elements);
        $mean = array_sum($elements) / $count;
        $maxDeviation = $this->standardDeviaton($elements) * $magnitude;

        $keepElements = array();

        foreach ($elements as $element) {

            $deviation = abs($element - $mean);

            if ($deviation <= $maxDeviation) {
                $keepElements[] = $element;
            }
        }

        return $keepElements;
    }

    /**
     * @param JourneySegment[] $journeySegments
     * @return float[] an array holding current speed and standard deviation
     */
    public function determineCurrentSpeed($journeySegments)
    {

        $currentSpeedData = null;

        $samples = array();

        if (is_array($journeySegments) && (count($journeySegments) > 1)) {

            $sampleSources = array();

            /**
             * @var JourneySegment
             */
            $previousSegment = null;

            /**
             * @var JourneySegment
             */
            $lastSegment = null;

            foreach ($journeySegments as $journeySegment) {

                if ($lastSegment == null) {
                    $lastSegment = $journeySegment;
                    continue;
                }

                $previousSegment = $lastSegment;
                $lastSegment = $journeySegment;
            }

            if ($previousSegment) {

                if ($previousSegment->getFirstTimestamp() != $previousSegment->getLastTimestamp()) {

                    if ($previousSegment->getFirstLocation()->toString() != $previousSegment->getLastLocation()->toString()) {

                        $thisTimestamp = $previousSegment->getLastTimestamp();
                        $lastTimestamp = $previousSegment->getFirstTimestamp();

                        $thisLocation = $previousSegment->getFirstLocation();
                        $lastLocation = $previousSegment->getLastLocation();

                        if ($thisTimestamp && $lastTimestamp && $thisLocation && $lastLocation) {

                            if ($thisTimestamp > $lastTimestamp) {

                                if ($thisLocation->toString() != $lastLocation->toString()) {

                                    $sampleSources[] = array($thisTimestamp, $lastTimestamp, $thisLocation, $lastLocation);
                                }
                            }
                        }
                    }
                }
            }

            if ($previousSegment && $lastSegment) {

                $thisTimestamp = $lastSegment->getFirstTimestamp();
                $lastTimestamp = $previousSegment->getLastTimestamp();

                $thisLocation = $lastSegment->getFirstLocation();
                $lastLocation = $previousSegment->getLastLocation();

                if ($thisTimestamp && $lastTimestamp && $thisLocation && $lastLocation) {

                    if ($thisTimestamp > $lastTimestamp) {

                        if ($thisLocation->toString() != $lastLocation->toString()) {

                            $sampleSources[] = array($thisTimestamp, $lastTimestamp, $thisLocation, $lastLocation);
                        }
                    }
                }
            }

            if ($lastSegment) {

                if ($lastSegment->getFirstTimestamp() != $lastSegment->getLastTimestamp()) {

                    if ($lastSegment->getFirstLocation()->toString() != $lastSegment->getLastLocation()->toString()) {

                        $thisTimestamp = $lastSegment->getLastTimestamp();
                        $lastTimestamp = $lastSegment->getFirstTimestamp();

                        $thisLocation = $lastSegment->getLastLocation();
                        $lastLocation = $lastSegment->getFirstLocation();

                        if ($thisTimestamp && $lastTimestamp && $thisLocation && $lastLocation) {

                            if ($thisTimestamp > $lastTimestamp) {

                                if ($thisLocation->toString() != $lastLocation->toString()) {

                                    $sampleSources[] = array($thisTimestamp, $lastTimestamp, $thisLocation, $lastLocation);
                                }
                            }
                        }
                    }
                }
            }

            foreach ($sampleSources as $sampleSource) {

                $time = $sampleSource[0] - $sampleSource[1];
                $thisLocation = $sampleSource[2];
                $lastLocation = $sampleSource[3];

                $distance = $thisLocation->getDistance($lastLocation);
                if (($time > 0) && ($distance > 0)) {

                    $speedMeterPerSecond = $distance / $time;
                    $speed = $speedMeterPerSecond * 3600 / 1000;

                    $samples[] = $speed;
                }
            }
        }

        if (count($samples)) {
            $standardDeviation = $this->standardDeviaton($samples);
            $averageSpeed = array_sum($samples) / count($samples);

            $currentSpeedData = array($averageSpeed, $standardDeviation);
        }
        return $currentSpeedData;
    }

    /**
     * @param JourneySegment[] $journeySegments
     * @return float[] an array holding current speed and standard deviation
     */
    public function determineCruiseSpeed($journeySegments)
    {

        $currentSpeedData = null;

        $samples = array();

        if (is_array($journeySegments) && (count($journeySegments) > 1)) {

            $sampleSources = array();

            /**
             * @var JourneySegment
             */
            $previousSegment = null;

            foreach ($journeySegments as $journeySegment) {

                if ($previousSegment) {

                    $thisTimestamp = $journeySegment->getFirstTimestamp();
                    $thisLocation = $journeySegment->getFirstLocation();

                    $lastTimestamp = $previousSegment->getLastTimestamp();
                    $lastLocation = $previousSegment->getLastLocation();

                    if ($thisTimestamp && $lastTimestamp && $thisLocation && $lastLocation) {

                        if ($thisTimestamp > $lastTimestamp) {

                            if ($thisLocation->toString() != $lastLocation->toString()) {

                                $sampleSources[] = array($thisTimestamp, $lastTimestamp, $thisLocation, $lastLocation);
                            }
                        }
                    }
                }

                if ($journeySegment->getFirstTimestamp() != $journeySegment->getLastTimestamp()) {

                    if ($journeySegment->getFirstLocation()->toString() != $journeySegment->getLastLocation()->toString()) {

                        $thisTimestamp = $journeySegment->getLastTimestamp();
                        $thisLocation = $journeySegment->getLastLocation();

                        $lastTimestamp = $journeySegment->getFirstTimestamp();
                        $lastLocation = $journeySegment->getFirstLocation();

                        if ($thisTimestamp && $lastTimestamp && $thisLocation && $lastLocation) {

                            if ($thisTimestamp > $lastTimestamp) {

                                if ($thisLocation->toString() != $lastLocation->toString()) {

                                    $sampleSources[] = array($thisTimestamp, $lastTimestamp, $thisLocation, $lastLocation);
                                }
                            }
                        }
                    }
                }

                $previousSegment = $journeySegment;
            }

            foreach ($sampleSources as $sampleSource) {

                $time = $sampleSource[0] - $sampleSource[1];
                $thisLocation = $sampleSource[2];
                $lastLocation = $sampleSource[3];

                $distance = $thisLocation->getDistance($lastLocation);
                if (($time > 0) && ($distance > 0)) {

                    $speedMeterPerSecond = $distance / $time;

                    $speed = $speedMeterPerSecond * 3600 / 1000;

                    $samples[] = $speed;
                }
            }
        }

        $cleanSamples = $this->removeOutliers($samples, 1);

        if (count($cleanSamples)) {
            $standardDeviation = $this->standardDeviaton($cleanSamples);
            $averageSpeed = array_sum($cleanSamples) / count($cleanSamples);

            $currentSpeedData = array($averageSpeed, $standardDeviation);
        }
        return $currentSpeedData;
    }
}
