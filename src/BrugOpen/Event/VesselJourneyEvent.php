<?php

namespace BrugOpen\Event;

use BrugOpen\Model\Vessel;
use BrugOpen\Model\VesselJourney;
use BrugOpen\Model\VesselLocation;
use BrugOpen\Model\WaterwaySegment;

class VesselJourneyEvent
{

    const EVENT_TYPE_START = 'start';
    const EVENT_TYPE_UPDATE = 'update';
    const EVENT_TYPE_END = 'end';
    const EVENT_TYPE_EXPIRE = 'expire';
    const EVENT_TYPE_FLAP = 'flap';

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
     * @var VesselLocation
     */
    private $currentLocation;

    /**
     *
     * @var VesselLocation
     */
    private $previousLocation;

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
     * @return \BrugOpen\Model\Vessel
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
     * @return \BrugOpen\Model\VesselJourney
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
     * @return VesselLocation the $currentLocation
     */
    public function getCurrentLocation()
    {
        return $this->currentLocation;
    }

    /**
     * @param \BrugOpen\Model\VesselLocation $currentLocation
     */
    public function setCurrentLocation($currentLocation)
    {
        $this->currentLocation = $currentLocation;
    }

    /**
     * @return VesselLocation the $previousLocation
     */
    public function getPreviousLocation()
    {
        return $this->previousLocation;
    }

    /**
     * @param \BrugOpen\Model\VesselLocation $previousLocation
     */
    public function setPreviousLocation($previousLocation)
    {
        $this->previousLocation = $previousLocation;
    }

}
