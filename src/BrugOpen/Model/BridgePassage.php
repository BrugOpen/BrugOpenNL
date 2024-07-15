<?php

namespace BrugOpen\Model;

class BridgePassage
{

    /**
     * @var string
     */
    private $mmsi;

    /**
     * @var int
     */
    private $bridgeId;

    /**
     * @var \DateTime
     */
    private $datetimePassage;

    /**
     * @var int
     */
    private $direction;

    /**
     * @var int
     */
    private $vesselType;

    /**
     * @var int
     */
    private $operationId;

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
     * @return number
     */
    public function getBridgeId()
    {
        return $this->bridgeId;
    }

    /**
     * @param number $bridgeId
     */
    public function setBridgeId($bridgeId)
    {
        $this->bridgeId = $bridgeId;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimePassage()
    {
        return $this->datetimePassage;
    }

    /**
     * @param \DateTime $datetimePassage
     */
    public function setDatetimePassage($datetimePassage)
    {
        $this->datetimePassage = $datetimePassage;
    }

    /**
     * @return number
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * @param number $direction
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
    }

    /**
     * @return int
     */
    public function getVesselType()
    {
        return $this->vesselType;
    }

    /**
     * @param int $vesselType
     */
    public function setVesselType($vesselType)
    {
        $this->vesselType = $vesselType;
    }

    /**
     * @return number
     */
    public function getOperationId()
    {
        return $this->operationId;
    }

    /**
     * @param number $operationId
     */
    public function setOperationId($operationId)
    {
        $this->operationId = $operationId;
    }
}
