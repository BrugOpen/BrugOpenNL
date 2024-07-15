<?php

namespace BrugOpen\Tracking\Model;

use BrugOpen\Model\BridgePassage;

class VesselJourney
{

    /**
     *
     * @var string
     */
    private $id;

    /**
     * @var Vessel
     */
    private $vessel;

    /**
     *
     * @var string
     */
    private $voyageDestination;

    /**
     *
     * @var int
     */
    private $voyageEta;

    /**
     *
     * @var JourneySegment[]
     */
    private $journeySegments;

    /**
     * @var BridgePassage[]
     */
    private $passages;

    /**
     *
     * @var int
     */
    private $firstTimestamp;

    /**
     *
     * @var LatLng
     */
    private $firstLocation;

    /**
     *
     * @var int
     */
    private $lastTimestamp;

    /**
     *
     * @var LatLng
     */
    private $lastLocation;

    /**
     * @var int
     */
    private $distance;

    /**
     * @var int
     */
    private $duration;

    /**
     * @return string the $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Vessel the $vessel
     */
    public function getVessel()
    {
        return $this->vessel;
    }

    /**
     * @param Vessel $vessel
     */
    public function setVessel($vessel)
    {
        $this->vessel = $vessel;
    }

    /**
     * @return string the $voyageDestination
     */
    public function getVoyageDestination()
    {
        return $this->voyageDestination;
    }

    /**
     * @param string $voyageDestination
     */
    public function setVoyageDestination($voyageDestination)
    {
        $this->voyageDestination = $voyageDestination;
    }

    /**
     * @return int the $voyageEta
     */
    public function getVoyageEta()
    {
        return $this->voyageEta;
    }

    /**
     * @param int $voyageEta
     */
    public function setVoyageEta($voyageEta)
    {
        $this->voyageEta = $voyageEta;
    }

    /**
     *
     * @return JourneySegment[]
     */
    public function getJourneySegments()
    {
        return $this->journeySegments;
    }

    /**
     *
     * @param JourneySegment[] $journeySegments
     */
    public function setJourneySegments($journeySegments)
    {
        $this->journeySegments = $journeySegments;
    }

    /**
     * @return BridgePassage[]
     */
    public function getPassages()
    {
        return $this->passages;
    }

    /**
     * @param BridgePassage[] $passages
     */
    public function setPassages($passages)
    {
        $this->passages = $passages;
    }

    /**
     * @return int
     */
    public function getFirstTimestamp()
    {
        return $this->firstTimestamp;
    }

    /**
     * @param int $firstTimestamp
     */
    public function setFirstTimestamp($firstTimestamp)
    {
        $this->firstTimestamp = $firstTimestamp;
    }

    /**
     * @return LatLng
     */
    public function getFirstLocation()
    {
        return $this->firstLocation;
    }

    /**
     * @param LatLng $firstLocation
     */
    public function setFirstLocation($firstLocation)
    {
        $this->firstLocation = $firstLocation;
    }

    /**
     * @return int
     */
    public function getLastTimestamp()
    {
        return $this->lastTimestamp;
    }

    /**
     * @param int $lastTimestamp
     */
    public function setLastTimestamp($lastTimestamp)
    {
        $this->lastTimestamp = $lastTimestamp;
    }

    /**
     * @return LatLng
     */
    public function getLastLocation()
    {
        return $this->lastLocation;
    }

    /**
     * @param LatLng $lastLocation
     */
    public function setLastLocation($lastLocation)
    {
        $this->lastLocation = $lastLocation;
    }

    /**
     * @return int
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * @param int $distance
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }
}
