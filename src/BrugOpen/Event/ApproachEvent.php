<?php

namespace BrugOpen\Event;

use BrugOpen\Model\Approach;
use BrugOpen\Model\VesselLocation;

class ApproachEvent
{

    const EVENT_TYPE_START = 'start';
    const EVENT_TYPE_UPDATE = 'update';
    const EVENT_TYPE_END = 'end';
    const EVENT_TYPE_EXPIRE = 'expire';

    /**
     *
     * @var string
     */
    private $type;

    /**
     *
     * @var Approach
     */
    private $approach;

    /**
     *
     * @var int
     */
    private $bridgeId;

    /**
     *
     * @var int
     */
    private $side;

    /**
     *
     * @var int
     */
    private $etaPass;

    /**
     *
     * @var int
     */
    private $actualPass;

    /**
     *
     * @var VesselLocation
     */
    private $vesselLocation;

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return Approach
     */
    public function getApproach()
    {
        return $this->approach;
    }

    /**
     *
     * @param Approach $approach
     */
    public function setApproach($approach)
    {
        $this->approach = $approach;
    }

    public function getBridgeId()
    {
        return $this->bridgeId;
    }

    public function setBridgeId($bridgeId)
    {
        $this->bridgeId = $bridgeId;
    }

    public function getSide()
    {
        return $this->side;
    }

    public function setSide($side)
    {
        $this->side = $side;
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
    public function getVesselLocation()
    {
        return $this->vesselLocation;
    }

    /**
     * @param VesselLocation $vesselLocation
     */
    public function setVesselLocation($vesselLocation)
    {
        $this->vesselLocation = $vesselLocation;
    }

}
