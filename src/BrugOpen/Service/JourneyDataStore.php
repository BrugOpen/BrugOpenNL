<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Model\Vessel;
use BrugOpen\Model\VesselJourney;
use BrugOpen\Model\VesselLocation;
use BrugOpen\Model\VesselSegment;

class JourneyDataStore
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
     * Loads VesselJourney objects (without segments) for journeys that have empty 'last_timestamp'
     * @return VesselJourney[]
     */
    public function loadActiveJourneys()
    {

        $journeys = array();

        $dataStore = $this->context->getDataStore();

        $sql = 'SELECT * FROM bo_vessel_journey WHERE last_timestamp IS NULL';

        if ($result = $dataStore->executeQuery($sql)) {

            while ($row = $result->fetch_assoc()) {

                $journey = new VesselJourney();

                $journey->setId($row['id']);
                $journey->setMmsi($row['mmsi']);
                $journey->setFirstLocation(explode(',', $row['first_location']));

                $journeys[] = $journey;

            }

        }

        return $journeys;

    }

    /**
     *
     * @param int[] $journeyIds
     * @return VesselSegment[]
     */
    public function loadLatestVesselSegments($journeyIds)
    {

        $vesselSegments = array();

        if (sizeof($journeyIds) > 0) {

            $dataStore = $this->context->getDataStore();

            $sql = 'SELECT id, mmsi, segment_id, journey_id, first_location, last_location, UNIX_TIMESTAMP(first_timestamp) AS first_timestamp, UNIX_TIMESTAMP(last_timestamp) AS last_timestamp FROM bo_vessel_segment WHERE journey_id IN (' . implode(',', $journeyIds) . ')';

            $lastSegmentByVessel = array();
            $segmentsByJourney = array();

            if ($result = $dataStore->executeQuery($sql)) {

                while ($row = $result->fetch_assoc()) {

                    $mmsi = $row['mmsi'];
                    //$timestamp = $row['last_timestamp'];

                    $timestamp = $row['last_timestamp'];

                    if (array_key_exists($mmsi, $lastSegmentByVessel)) {

                        if ($timestamp > $lastSegmentByVessel[$mmsi]['last_timestamp']) {

                            $lastSegmentByVessel[$mmsi] = $row;

                        }

                    } else {

                        $lastSegmentByVessel[$mmsi] = $row;

                    }

                    $journeyId = $row['journey_id'];

                    $segmentsByJourney[$journeyId][$timestamp] = $row;

                }

            }

            $lastSegmentByJourney = array();

            $sortedSegmentsByJourney = array();

            foreach (array_keys($segmentsByJourney) as $journeyId) {

                $journeySegments = $segmentsByJourney[$journeyId];

                ksort($journeySegments);

                foreach ($journeySegments as $record) {

                    $vesselSegment = new VesselSegment();
                    $vesselSegment->setId($record['id']);
                    $vesselSegment->setMmsi($record['mmsi']);
                    $vesselSegment->setJourneyId($journeyId);
                    $vesselSegment->setFirstLocation(explode(',', $record['first_location']));
                    $vesselSegment->setFirstTimestamp((int)$record['first_timestamp']);
                    $vesselSegment->setLastLocation(explode(',', $record['last_location']));
                    $vesselSegment->setLastTimestamp((int)$record['last_timestamp']);
                    $vesselSegment->setSegmentId($record['segment_id']);

                    $lastSegmentByJourney[$journeyId] = $vesselSegment;
                    $sortedSegmentsByJourney[$journeyId][] = $vesselSegment;

                }

            }

            $journeyById = array();

            $sql = 'SELECT id, mmsi, UNIX_TIMESTAMP(first_timestamp) AS first_timestamp, first_location, ' .
                'UNIX_TIMESTAMP(last_timestamp) AS last_timestamp, last_location, vessel_name, vessel_callsign, ' .
                'vessel_dimensions, vessel_type, voyage_destination, voyage_eta FROM bo_vessel_journey WHERE id IN (' . implode(',', $journeyIds) . ')';

            if ($result = $dataStore->executeQuery($sql)) {

                while ($row = $result->fetch_assoc()) {

                    $journeyId = (int)$row['id'];

                    $latLon = explode(',', $row['first_location']);

                    $firstLocation = new VesselLocation();
                    $firstLocation->setTime((int)$row['first_timestamp']);
                    $firstLocation->setLat($latLon[0]);
                    $firstLocation->setLon($latLon[1]);

                    $journey = new VesselJourney();
                    $journey->setId($journeyId);
                    $journey->setMmsi($row['mmsi']);
                    $journey->setFirstLocation($firstLocation);
                    $journey->setVoyageDestination($row['voyage_destination']);
                    $journey->setVoyageEta($row['voyage_eta']);

                    $vessel = new Vessel();
                    $vessel->setMmsi($row['mmsi']);
                    $vessel->setCallsign($row['vessel_callsign']);
                    $vessel->setName($row['vessel_name']);
                    $vessel->setDimensions($row['vessel_dimensions']);
                    $vessel->setVesselType($row['vessel_type']);

                    $journey->setVessel($vessel);

                    $journey->setVesselSegments($sortedSegmentsByJourney[$journeyId]);

                    $journeyById[$journeyId] = $journey;

                }

            }

        }

        foreach ($journeyById as $journeyId => $journey) {

            if (array_key_exists($journeyId, $lastSegmentByJourney)) {

                $vessel = $journey->getVessel();

                $lastSegment = $lastSegmentByJourney[$journeyId];

                $latLon = $lastSegment->getLastLocation();

                $lastLocation = new VesselLocation();
                $lastLocation->setMmsi($lastSegment->getMmsi());
                $lastLocation->setTime($lastSegment->getLastTimestamp());
                $lastLocation->setLat($latLon[0]);
                $lastLocation->setLon($latLon[1]);

                $journey->setLastLocation($lastLocation);

                $vesselSegment = $lastSegmentByJourney[$journeyId];
                $vesselSegment->setJourney($journey);

                $mmsi = $journey->getMmsi();

                $vesselSegments[$mmsi] = $vesselSegment;

            }

        }

        return $vesselSegments;

    }

    /**
     *
     * @param VesselJourney $journey
     */
    public function storeJourney($journey)
    {

        $dataStore = $this->context->getDataStore();

        if ($journey->getId()) {

            $lastLocation = $journey->getLastLocation();

            if ($lastLocation && $lastLocation->getTime()) {

                $values = array();

                $values['last_timestamp'] = new \DateTime("@" . $lastLocation->getTime());
                $values['last_location'] = $lastLocation->getLat() . ',' . $lastLocation->getLon();

                $keys = array();
                $keys['id'] = $journey->getId();

                $dataStore->updateTable('bo_vessel_journey', $keys, $values);

            } else {

                echo 'cannot update journey';
                var_dump($journey);exit;

            }

        } else {

            $vessel = $journey->getVessel();

            $values = array();

            $values['mmsi'] = $vessel->getMmsi();

            $firstLocation = $journey->getFirstLocation();

            $values['first_timestamp'] = new \DateTime("@" . $firstLocation->getTime());
            $values['first_location'] = $firstLocation->getLat() . ',' . $firstLocation->getLon();

            // do not set last_timestamp and last_location

            $values['vessel_name'] = $vessel->getName();
            $values['vessel_callsign'] = $vessel->getCallsign();
            $values['vessel_dimensions'] = $vessel->getDimensions();
            $values['vessel_type'] = $vessel->getVesselType();

            $values['voyage_destination'] = $journey->getVoyageDestination();
            $values['voyage_eta'] = $journey->getVoyageEta();

            $journeyId = $dataStore->insertRecord('bo_vessel_journey', $values);

            if ($journeyId) {

                $journey->setId($journeyId);

            }

        }

    }

    /**
     *
     * @param VesselJourney $journey
     */
    public function deleteJourney($journey)
    {

        $journeyId = $journey->getId();

        if ($journeyId) {

            $dataStore = $this->context->getDataStore();

            $dataStore->executeQuery('DELETE FROM bo_vessel_journey WHERE id = ' . (int)$journeyId);
            $dataStore->executeQuery('DELETE FROM bo_vessel_segment WHERE journey_id = ' . (int)$journeyId);

        }

    }

    /**
     *
     * @param VesselSegment $vesselSegment
     */
    public function storeJourneySegment($vesselSegment)
    {

        $dataStore = $this->context->getDataStore();

        $journey = $vesselSegment->getJourney();
        $vessel = $journey->getVessel();

        if ($vesselSegment->getId()) {

            $keys = array();
            $keys['id'] = $vesselSegment->getId();

            $values = array();

            $values['last_timestamp'] = new \DateTime("@" . $vesselSegment->getLastTimestamp());
            $values['last_location'] = implode(',', $vesselSegment->getLastLocation());

            $dataStore->updateTable('bo_vessel_segment', $keys, $values);

        } else {

            $values = array();

            $values['mmsi'] = $vessel->getMmsi();
            $values['segment_id'] = $vesselSegment->getSegmentId();
            $values['journey_id'] = $journey->getId();

            $values['first_timestamp'] = new \DateTime("@" . $vesselSegment->getFirstTimestamp());
            $values['first_location'] = implode(',', $vesselSegment->getFirstLocation());

            $values['last_timestamp'] = new \DateTime("@" . $vesselSegment->getLastTimestamp());
            $values['last_location'] = implode(',', $vesselSegment->getLastLocation());

            $vesselSegmentId = $dataStore->insertRecord('bo_vessel_segment', $values);

            if ($vesselSegmentId) {

                $vesselSegment->setId($vesselSegmentId);

            }

        }

    }

}
