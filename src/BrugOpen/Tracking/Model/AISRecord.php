<?php

namespace BrugOpen\Tracking\Model;

use BrugOpen\Geo\Model\LatLng;

class AISRecord
{

    /**
     * @var string
     */
    private $mmsi;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var LatLng
     */
    private $location;

    /**
     * @var int
     */
    private $heading;

    /**
     * @var float
     */
    private $speed;

    /**
     * @var int
     */
    private $course;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $callsign;

    /**
     * @var int[]
     */
    private $dimensions;

    /**
     * @var float
     */
    private $draught;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var int
     */
    private $eta;

    /**
     * @return string
     */
    public function getMmsi()
    {
        return $this->mmsi;
    }

    /**
     * @param string $mmsi
     */
    public function setMmsi($mmsi)
    {
        $this->mmsi = $mmsi;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return LatLng
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param LatLng $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return int
     */
    public function getHeading()
    {
        return $this->heading;
    }

    /**
     * @param int $heading
     */
    public function setHeading($heading)
    {
        $this->heading = $heading;
    }

    /**
     * @return float
     */
    public function getSpeed()
    {
        return $this->speed;
    }

    /**
     * @param float $speed
     */
    public function setSpeed($speed)
    {
        $this->speed = $speed;
    }

    /**
     * @return int
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param int $course
     */
    public function setCourse($course)
    {
        $this->course = $course;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCallsign()
    {
        return $this->callsign;
    }

    /**
     * @param string $callsign
     */
    public function setCallsign($callsign)
    {
        $this->callsign = $callsign;
    }

    /**
     * @return int[]
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @param int[] $dimensions
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }

    /**
     * @return float
     */
    public function getDraught()
    {
        return $this->draught;
    }

    /**
     * @param float $draught
     */
    public function setDraught($draught)
    {
        $this->draught = $draught;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    /**
     * @return int
     */
    public function getEta()
    {
        return $this->eta;
    }

    /**
     * @param int $eta
     */
    public function setEta($eta)
    {
        $this->eta = $eta;
    }

}
