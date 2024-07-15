<?php

namespace BrugOpen\Model;

class VesselLocation
{

    private $id;

    private $mmsi;

    private $time;

    private $lat;

    private $lon;

    private $speed;

    private $heading;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getMmsi()
    {
        return $this->mmsi;
    }

    public function setMmsi($mmsi)
    {
        $this->mmsi = $mmsi;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function setTime($time)
    {
        $this->time = $time;
    }

    public function getLat()
    {
        return $this->lat;
    }

    public function setLat($lat)
    {
        $this->lat = $lat;
    }

    public function getLon()
    {
        return $this->lon;
    }

    public function setLon($lon)
    {
        $this->lon = $lon;
    }

    public function getSpeed()
    {
        return $this->speed;
    }

    public function setSpeed($speed)
    {
        $this->speed = $speed;
    }

    public function getHeading()
    {
        return $this->heading;
    }

    public function setHeading($heading)
    {
        $this->heading = $heading;
    }

}
