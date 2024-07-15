<?php

namespace BrugOpen\Model;

class VesselJourney
{

    /**
     *
     * @var int
     */
    private $id;

    /**
     *
     * @var string
     */
    private $mmsi;

    /**
     * @var VesselLocation
     */
    private $firstLocation;

    /**
     *
     * @var VesselLocation
     */
    private $lastLocation;

    /**
     * @var Vessel
     */
    private $vessel;

    /**
     *
     * @var VesselSegment[]
     */
    private $vesselSegments;

    /**
     *
     * @var string
     */
    private $voyageDestination;

    /**
     *
     * @var int
     */
    private $voyageEta;

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
     * @return VesselLocation the $firstLocation
     */
    public function getFirstLocation()
    {
        return $this->firstLocation;
    }

    /**
     * @return VesselLocation the $lastLocation
     */
    public function getLastLocation()
    {
        return $this->lastLocation;
    }

    /**
     * @return Vessel the $vessel
     */
    public function getVessel()
    {
        return $this->vessel;
    }

    /**
     *
     * @return \BrugOpen\Model\VesselSegment[]
     */
    public function getVesselSegments()
    {
        return $this->vesselSegments;
    }

    /**
     *
     * @param VesselSegment[] $vesselSegments
     */
    public function setVesselSegments($vesselSegments)
    {
        $this->vesselSegments = $vesselSegments;
    }

    /**
     * @return string the $voyageDestination
     */
    public function getVoyageDestination()
    {
        return $this->voyageDestination;
    }

    /**
     * @return int the $voyageEta
     */
    public function getVoyageEta()
    {
        return $this->voyageEta;
    }

    /**
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $mmsi
     */
    public function setMmsi($mmsi)
    {
        $this->mmsi = $mmsi;
    }

    /**
     * @param \BrugOpen\Model\VesselLocation $firstLocation
     */
    public function setFirstLocation($firstLocation)
    {
        $this->firstLocation = $firstLocation;
    }

    /**
     * @param \BrugOpen\Model\VesselLocation $lastLocation
     */
    public function setLastLocation($lastLocation)
    {
        $this->lastLocation = $lastLocation;
    }

    /**
     * @param \BrugOpen\Model\Vessel $vessel
     */
    public function setVessel($vessel)
    {
        $this->vessel = $vessel;
    }

    /**
     * @param string $voyageDestination
     */
    public function setVoyageDestination($voyageDestination)
    {
        $this->voyageDestination = $voyageDestination;
    }

    /**
     * @param number $voyageEta
     */
    public function setVoyageEta($voyageEta)
    {
        $this->voyageEta = $voyageEta;
    }

}
