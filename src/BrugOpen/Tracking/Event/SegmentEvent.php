<?php

namespace BrugOpen\Tracking\Event;

use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\WaterwaySegment;
use BrugOpen\Tracking\Model\Vessel;
use BrugOpen\Tracking\Model\VesselJourney;

class SegmentEvent
{

    const EVENT_TYPE_ENTER = 'enter';
    const EVENT_TYPE_UPDATE = 'update';
    const EVENT_TYPE_EXIT = 'exit';
    const EVENT_TYPE_EXPIRE = 'expire';

    /**
     *
     * @var string
     */
    private $type;

    /**
     *
     * @var int
     */
    private $mmsi;

    /**
     *
     * @var JourneySegment
     */
    private $journeySegment;

    /**
     * @var Vessel
     */
    private $vessel;

    /**
     * @var VesselJourney
     */
    private $journey;

    /**
     *
     * @var WaterwaySegment
     */
    private $segment;

    /**
     *
     * @var LatLng
     */
    private $currentLocation;

    /**
     * @var int
     */
    private $currentTimestamp;

    /**
     *
     * @var LatLng
     */
    private $previousLocation;

    /**
     * @var int
     */
    private $previousTimestamp;

    /**
     * @return string the $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int the $mmsi
     */
    public function getMmsi()
    {
        return $this->mmsi;
    }

    /**
     * @param int $mmsi
     */
    public function setMmsi($mmsi)
    {
        $this->mmsi = $mmsi;
    }

    /**
     *
     * @return JourneySegment
     */
    public function getJourneySegment()
    {
        return $this->journeySegment;
    }

    /**
     * @param JourneySegment
     */
    public function setVesselSegment($journeySegment)
    {
        $this->journeySegment = $journeySegment;
    }

    /**
     *
     * @return \BrugOpen\Tracking\Model\Vessel
     */
    public function getVessel()
    {
        return $this->vessel;
    }

    /**
     *
     * @param Vessel $vessel
     */
    public function setVessel($vessel)
    {
        $this->vessel = $vessel;
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
     *
     * @param VesselJourney $journey
     */
    public function setJourney($journey)
    {
        $this->journey = $journey;
    }

    /**
     * @return WaterwaySegment the $segment
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * @param \BrugOpen\Model\WaterwaySegment $segment
     */
    public function setSegment($segment)
    {
        $this->segment = $segment;
    }

    /**
     * @return LatLng
     */
    public function getCurrentLocation()
    {
        return $this->currentLocation;
    }

    /**
     * @param LatLng $currentLocation
     */
    public function setCurrentLocation($currentLocation)
    {
        $this->currentLocation = $currentLocation;
    }

    /**
     * @return int
     */
    public function getCurrentTimestamp()
    {
        return $this->currentTimestamp;
    }

    /**
     * @param int
     */
    public function setCurrentTimestamp($currentTimestamp)
    {
        $this->currentTimestamp = $currentTimestamp;
    }

    /**
     * @return LatLng
     */
    public function getPreviousLocation()
    {
        return $this->previousLocation;
    }

    /**
     * @param LatLng $previousLocation
     */
    public function setPreviousLocation($previousLocation)
    {
        $this->previousLocation = $previousLocation;
    }

    /**
     * @return int
     */
    public function getPreviousTimestamp()
    {
        return $this->previousTimestamp;
    }

    /**
     * @param int
     */
    public function setPreviousTimestamp($previousTimestamp)
    {
        $this->previousTimestamp = $previousTimestamp;
    }

}
