<?php

namespace BrugOpen\Model;

class VesselSegment
{

    /**
     *
     * @var int
     */
    private $id;

    /**
     *
     * @var int
     */
    private $mmsi;

    /**
     *
     * @var int
     */
    private $segmentId;

    /**
     *
     * @var int
     */
    private $journeyId;

    /**
     * @var VesselJourney
     */
    private $journey;

    /**
     *
     * @var int
     */
    private $firstTimestamp;

    /**
     *
     * @var float[]
     */
    private $firstLocation;

    /**
     *
     * @var int
     */
    private $lastTimestamp;

    /**
     *
     * @var float[]
     */
    private $lastLocation;

    /**
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int the $mmsi
     */
    public function getMmsi()
    {
        return $this->mmsi;
    }

    /**
     * @return int the $segmentId
     */
    public function getSegmentId()
    {
        return $this->segmentId;
    }

    /**
     * @return int the $journeyId
     */
    public function getJourneyId()
    {
        return $this->journeyId;
    }

    /**
     *
     * @return VesselJourney
     */
    public function getJourney()
    {
        return $this->journey;
    }

    /**
     * @return int the $firstTimestamp
     */
    public function getFirstTimestamp()
    {
        return $this->firstTimestamp;
    }

    /**
     * @return float[] the $firstLocation
     */
    public function getFirstLocation()
    {
        return $this->firstLocation;
    }

    /**
     * @return int the $lastTimestamp
     */
    public function getLastTimestamp()
    {
        return $this->lastTimestamp;
    }

    /**
     * @return float[] the $lastLocation
     */
    public function getLastLocation()
    {
        return $this->lastLocation;
    }

    /**
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param number $mmsi
     */
    public function setMmsi($mmsi)
    {
        $this->mmsi = $mmsi;
    }

    /**
     * @param number $segmentId
     */
    public function setSegmentId($segmentId)
    {
        $this->segmentId = $segmentId;
    }

    /**
     * @param number $journeyId
     */
    public function setJourneyId($journeyId)
    {
        $this->journeyId = $journeyId;
    }

    /**
     *
     * @param VesselJourney $journey
     */
    public function setJourney($journey)
    {
        $this->journey = $journey;
    }

    /**
     * @param number $firstTimestamp
     */
    public function setFirstTimestamp($firstTimestamp)
    {
        $this->firstTimestamp = $firstTimestamp;
    }

    /**
     * @param float[]  $firstLocation
     */
    public function setFirstLocation($firstLocation)
    {
        $this->firstLocation = $firstLocation;
    }

    /**
     * @param number $lastTimestamp
     */
    public function setLastTimestamp($lastTimestamp)
    {
        $this->lastTimestamp = $lastTimestamp;
    }

    /**
     * @param float[]  $lastLocation
     */
    public function setLastLocation($lastLocation)
    {
        $this->lastLocation = $lastLocation;
    }

}
