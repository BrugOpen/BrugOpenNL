<?php

namespace BrugOpen\Model;

class Vessel
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
     * @return int the $id
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
     * @return string the $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string the $callsign
     */
    public function getCallsign()
    {
        return $this->callsign;
    }

    /**
     * @return string the $dimensions
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
     * @param number $vesselType
     */
    public function setVesselType($vesselType)
    {
        $this->vesselType = $vesselType;
    }




}
