<?php

namespace BrugOpen\Tracking\Model;

class Vessel
{

    /**
     *
     * @var string
     */
    private $mmsi;

    /**
     *
     * @var string
     */
    private $name;

    /**
     *
     * @var string
     */
    private $callsign;

    /**
     *
     * @var string
     */
    private $dimensions;

    /**
     *
     * @var int
     */
    private $vesselType;

    /**
     * @return string
     */
    public function getMmsi()
    {
        return $this->mmsi;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCallsign()
    {
        return $this->callsign;
    }

    /**
     * @return VesselDim
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @return int the $vesselType
     */
    public function getVesselType()
    {
        return $this->vesselType;
    }

    /**
     * @param string $mmsi
     */
    public function setMmsi($mmsi)
    {
        $this->mmsi = $mmsi;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $callsign
     */
    public function setCallsign($callsign)
    {
        $this->callsign = $callsign;
    }

    /**
     * @param string $dimensions
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }

    /**
     * @param int $vesselType
     */
    public function setVesselType($vesselType)
    {
        $this->vesselType = $vesselType;
    }

}
