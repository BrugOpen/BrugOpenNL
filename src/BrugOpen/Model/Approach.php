<?php

namespace BrugOpen\Model;

class Approach
{

    /**
     *
     * @var int
     */
    private $bridgeId;

    /**
     *
     * @var string
     */
    private $mmsi;

    /**
     *
     * @var int
     */
    private $entrySide;

    /**
     * @var VesselLocation
     */
    private $entryFirstLocation;

    /**
     * The last known location on entry side. Will be empty if only 1 location is known in approach
     * @var VesselLocation
     */
    private $entryLastLocation;

    /**
     * Estimated time of passing the bridge
     * @var int
     */
    private $etaPass;

    /**
     * The calculated time once exit location is known
     * @var int
     */
    private $actualPass;

    /**
     * The first known location after passing the bridge
     * @var VesselLocation
     */
    private $exitLocation;

    /**
     * @return number
     */
    public function getBridgeId()
    {
        return $this->bridgeId;
    }

    public function setBridgeId($bridgeId)
    {
        $this->bridgeId = $bridgeId;
    }

    public function getMmsi()
    {
        return $this->mmsi;
    }

    public function setMmsi($mmsi)
    {
        $this->mmsi = $mmsi;
    }

    public function getEntrySide()
    {
        return $this->entrySide;
    }

    public function setEntrySide($side)
    {
        $this->entrySide = $side;
    }

    /**
     * @return VesselLocation
     */
    public function getEntryFirstLocation()
    {
        return $this->entryFirstLocation;
    }

    /**
     * @param VesselLocation $location
     */
    public function setEntryFirstLocation($location)
    {
        $this->entryFirstLocation = $location;
    }

    /**
     * @return VesselLocation
     */
    public function getEntryLastLocation()
    {
        return $this->entryLastLocation;
    }

    /**
     * @param VesselLocation $location
     */
    public function setEntryLastLocation($location)
    {
        $this->entryLastLocation = $location;
    }

    public function getEtaPass()
    {
        return $this->etaPass;
    }

    public function setEtaPass($etaPass)
    {
        $this->etaPass = $etaPass;
    }

    public function getActualPass()
    {
        return $this->actualPass;
    }

    public function setActualPass($actualPass)
    {
        $this->actualPass = $actualPass;
    }

    /**
     * @return VesselLocation
     */
    public function getExitLocation()
    {
        return $this->exitLocation;
    }

    /**
     * @param VesselLocation $location
     */
    public function setExitLocation($location)
    {
        $this->exitLocation = $location;
    }

}
