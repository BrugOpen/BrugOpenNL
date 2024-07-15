<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Core\Context;
use BrugOpen\Model\VesselSegment;
use BrugOpen\Tracking\Model\VesselJourney;

class VesselJourneyService
{

    /**
     *
     * @var Context
     */
    private $context;

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
     * @param int[] $journeyIds
     * @return VesselSegment[][]
     */
    public function loadVesselSegmentsByJourney($journeyIds)
    {

        $vesselSegmentsByJourney = array();

        if ($journeyIds) {

            $cleanIds = array();

            foreach ($journeyIds as $journeyId) {
                $cleanIds[] = (int) $journeyId;
            }

            if ($cleanIds) {

                $sql = 'SELECT id, mmsi, segment_id, journey_id, UNIX_TIMESTAMP(first_timestamp) AS first_timestamp, first_location, UNIX_TIMESTAMP(last_timestamp) AS last_timestamp, last_location FROM bo_vessel_segment WHERE journey_id IN (' . implode(',', $cleanIds) . ') ORDER BY journey_id, id';

                if ($results = $this->context->getDataStore()->executeQuery($sql)) {

                    while ($row = $results->fetch_assoc()) {

                        $journeyId = $row['journey_id'];

                        $vesselSegment = new VesselSegment();
                        $vesselSegment->setId($row['id']);
                        $vesselSegment->setMmsi($row['mmsi']);
                        $vesselSegment->setSegmentId($row['segment_id']);
                        $vesselSegment->setJourneyId($journeyId);
                        $vesselSegment->setFirstTimestamp($row['first_timestamp']);
                        $vesselSegment->setFirstLocation(explode(',', $row['first_location']));
                        $vesselSegment->setLastTimestamp($row['last_timestamp']);
                        $vesselSegment->setLastLocation(explode(',', $row['last_location']));

                        $vesselSegmentsByJourney[$journeyId][] = $vesselSegment;

                    }

                }

            }

        }

        return $vesselSegmentsByJourney;

    }

    /**
     *
     * @param VesselSegment[] $vesselSegments
     * @param int[] $segementsSide1
     * @param int[] $segmentsSide2
     * @return int[]
     */
    public function getPassTimes($vesselSegments, $segmentsSide1, $segmentsSide2, $passLocation)
    {

        $passTimes = array();

        $currentSide = null;
        $lastVesselSegmentCurrentSide = null;

        foreach ($vesselSegments as $vesselSegment) {

            $segmentId = $vesselSegment->getSegmentId();

            if (in_array($segmentId, $segmentsSide1) || in_array($segmentId, $segmentsSide2)) {

                $thisSide = (in_array($segmentId, $segmentsSide1)) ? 1 : 2;

                if ($currentSide == null) {

                    // mark this side current
                    $currentSide = $thisSide;

                } else if ($thisSide === $currentSide) {

                    // we're still at the same side

                } else {

                    // we crossed sides, calculate when
                    $lastLocation = $lastVesselSegmentCurrentSide->getLastLocation();
                    $lastTimestamp = $lastVesselSegmentCurrentSide->getLastTimestamp();

                    $thisLocation = $vesselSegment->getFirstLocation();
                    $thisTimestamp = $vesselSegment->getFirstTimestamp();

                    $lastDistanceToPassPoint = $this->getDistance($lastLocation, $passLocation);

                    $currentDistanceFromPassPoint = $this->getDistance($passLocation, $thisLocation);

                    $timeSinceLastLocation = $thisTimestamp - $lastTimestamp;

                    $timeSincePass = $currentDistanceFromPassPoint / ($lastDistanceToPassPoint + $currentDistanceFromPassPoint) * $timeSinceLastLocation;

                    $actualTimePass = round($thisTimestamp - $timeSincePass);

                    $passTimes[$actualTimePass] = $currentSide;

                    // mark this side current
                    $currentSide = $thisSide;

                }

                $lastVesselSegmentCurrentSide = $vesselSegment;

            }

        }

        return $passTimes;

    }

    public function getDistance($currentPoint, $wayPoint)
    {

        $requiredHeading = null;

        /*
         var R = 6371000; // metres
         var φ1 = lat1.toRadians();
         var φ2 = lat2.toRadians();
         var Δφ = (lat2-lat1).toRadians();
         var Δλ = (lon2-lon1).toRadians();

         var a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
         Math.cos(φ1) * Math.cos(φ2) *
         Math.sin(Δλ/2) * Math.sin(Δλ/2);
         var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

         var d = R * c;
         */

        $lat1 = $currentPoint[0];
        $lon1 = $currentPoint[1];
        $lat2 = $wayPoint[0];
        $lon2 = $wayPoint[1];

        $R = 6371000; // earth radius in metres
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dPhi = deg2rad($lat2-$lat1);
        $dLambda = deg2rad($lon2-$lon1);

        $a = sin($dPhi/2) * sin($dPhi/2) +
        cos($phi1) * cos($phi2) *
        sin($dLambda/2) * sin($dLambda/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        $distance = $R * $c; // in meters

        return $distance;

    }

}
