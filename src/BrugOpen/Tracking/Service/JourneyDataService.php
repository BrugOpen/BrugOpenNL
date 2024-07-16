<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Model\BridgePassage;
use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\Vessel;
use BrugOpen\Tracking\Model\VesselDimensions;
use BrugOpen\Tracking\Model\VesselJourney;

class JourneyDataService
{

    /**
     * @return \BrugOpen\Tracking\Model\VesselJourney
     */
    public function parseJourney($jsonData)
    {

        $journey = new VesselJourney();
        $journey->setId($jsonData['id']);

        $vessel = new Vessel();
        $vessel->setMmsi($jsonData['mmsi']);
        $vessel->setName(isset($jsonData['vesselName']) ? $jsonData['vesselName'] : null);
        $vessel->setCallsign(isset($jsonData['vesselCallsign']) ? $jsonData['vesselCallsign'] : null);

        if (array_key_exists('vesselDimensions', $jsonData)) {

            $vessel->setDimensions(new VesselDimensions($jsonData['vesselDimensions']));
        }

        $vessel->setVesselType(isset($jsonData['vesselType']) ? $jsonData['vesselType'] : null);

        $journey->setVessel($vessel);

        $segments = array();

        if (array_key_exists('segments', $jsonData)) {

            foreach ($jsonData['segments'] as $segmentData) {

                $segment = new JourneySegment();

                $segment->setSegmentId($segmentData['segmentId']);
                $segment->setFirstTimestamp($segmentData['firstTimestamp']);
                $segment->setFirstLocation(new LatLng($segmentData['firstLocation']));

                if (array_key_exists('lastTimestamp', $segmentData)) {

                    $segment->setLastTimestamp($segmentData['lastTimestamp']);
                }

                if (array_key_exists('lastLocation', $segmentData)) {

                    $segment->setLastLocation(new LatLng($segmentData['lastLocation']));
                }

                $segments[] = $segment;
            }
        }

        $passages = array();

        if (array_key_exists('passages', $jsonData)) {

            foreach ($jsonData['passages'] as $passageData) {

                $passage = new BridgePassage();

                $passage->setBridgeId($passageData['bridgeId']);

                if (array_key_exists('timestamp', $passageData)) {

                    $datetimePassage = new \DateTime('@' . $passageData['timestamp']);
                    $passage->setDatetimePassage($datetimePassage);
                }

                if (array_key_exists('direction', $passageData)) {

                    $passage->setDirection($passageData['direction']);
                }

                $passage->setVesselType(isset($jsonData['vesselType']) ? $jsonData['vesselType'] : null);

                if (array_key_exists('operationId', $passageData)) {

                    $passage->setOperationId($passageData['operationId']);
                }

                $passages[] = $passage;
            }
        }

        if (array_key_exists('duration', $jsonData)) {

            $journey->setDuration($jsonData['duration']);
        }

        if (array_key_exists('distance', $jsonData)) {

            $journey->setDistance($jsonData['distance']);
        }

        $journey->setJourneySegments($segments);
        $journey->setPassages($passages);

        return $journey;
    }

    /**
     * @param VesselJourney $journey
     * @return array
     */
    public function toJsonData($journey)
    {

        $jsonData = array();

        if ($journey) {

            $vesselDimensions = $journey->getVessel()->getDimensions();

            $jsonData['id'] = $journey->getId();
            $jsonData['mmsi'] = $journey->getVessel()->getMmsi();
            $jsonData['vesselName'] = $journey->getVessel()->getName();
            $jsonData['vesselCallsign'] = $journey->getVessel()->getCallsign();
            $jsonData['vesselDimensions'] = $vesselDimensions ? $vesselDimensions->toString() : null;
            $jsonData['vesselType'] = $journey->getVessel()->getVesselType();

            $segmentsData = array();

            $segments = $journey->getJourneySegments();

            if ($segments) {

                foreach ($segments as $segment) {

                    $segmentData = array();

                    $segmentData['segmentId'] = $segment->getSegmentId();
                    $segmentData['firstTimestamp'] = $segment->getFirstTimestamp();
                    $segmentData['firstLocation'] = $segment->getFirstLocation()->toString();

                    if ($segment->getLastTimestamp()) {
                        $segmentData['lastTimestamp'] = $segment->getLastTimestamp();
                    }

                    if ($segment->getLastLocation()) {
                        $segmentData['lastLocation'] = $segment->getLastLocation()->toString();
                    }

                    $segmentsData[] = $segmentData;
                }

                $jsonData['segments'] = $segmentsData;
            }

            $passagesData = array();

            $passages = $journey->getPassages();

            if ($passages) {

                foreach ($passages as $passage) {

                    $passageData = array();

                    $passageData['bridgeId'] = $passage->getBridgeId();

                    if ($passage->getDatetimePassage()) {

                        $passageData['timestamp'] = $passage->getDatetimePassage()->getTimestamp();
                    }

                    if ($passage->getDirection() !== null) {

                        $passageData['direction'] = $passage->getDirection();
                    }

                    if ($passage->getOperationId() !== null) {

                        $passageData['operationId'] = $passage->getOperationId();
                    }

                    $passagesData[] = $passageData;
                }

                $jsonData['passages'] = $passagesData;
            }

            if ($journey->getDistance()) {
                $jsonData['distance'] = $journey->getDistance();
            }

            if ($journey->getDuration()) {
                $jsonData['duration'] = $journey->getDuration();
            }
        }

        return $jsonData;
    }

    /**
     * @param VesselJourney $journey
     * @return int The journey distance in meters
     */
    public function calculateJourneyDistance($journey)
    {

        $distance = 0;

        $lastLocation = null;

        if ($journey && $journey->getJourneySegments()) {

            $segments = $journey->getJourneySegments();

            foreach ($segments as $segment) {

                if ($lastLocation) {

                    $connectingDistance = $lastLocation->getDistance($segment->getFirstLocation());

                    if ($connectingDistance) {

                        $distance += $connectingDistance;
                    }
                }

                $segmentDistance = $segment->getFirstLocation()->getDistance($segment->getLastLocation());

                if ($segmentDistance > 0) {

                    $distance += $segmentDistance;
                }

                $lastLocation = $segment->getLastLocation();
            }
        }

        return $distance;
    }

    /**
     * @param VesselJourney $journey
     * @return int The duration in seconds
     */
    public function calculateJourneyDuration($journey)
    {
        $duration = 0;

        if ($journey && $journey->getJourneySegments()) {

            $segments = $journey->getJourneySegments();
            $startTime = $segments[0]->getFirstTimestamp();
            $endTime = $segments[count($segments) - 1]->getLastTimestamp();
            $duration = $endTime - $startTime;
        }

        return $duration;
    }
}
