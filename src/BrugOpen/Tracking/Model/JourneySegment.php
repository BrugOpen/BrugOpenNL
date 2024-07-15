<?php

namespace BrugOpen\Tracking\Model;

use BrugOpen\Geo\Model\LatLng;

class JourneySegment
{

    /**
     * @var VesselJourney
     */
    private $journey;

    /**
     *
     * @var int
     */
    private $segmentId;

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
     *
     * @return VesselJourney
     */
    public function getJourney()
    {
        return $this->journey;
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
     * @return int the $segmentId
     */
    public function getSegmentId()
    {
        return $this->segmentId;
    }

    /**
     * @param int $segmentId
     */
    public function setSegmentId($segmentId)
    {
        $this->segmentId = $segmentId;
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

}
