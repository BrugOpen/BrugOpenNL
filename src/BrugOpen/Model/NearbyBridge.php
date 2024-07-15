<?php

namespace BrugOpen\Model;

class NearbyBridge
{

    /**
     *
     * @var int
     */
    private $bridgeId;

    /**
     *
     * @var int
     */
    private $nearbyBridgeId;

    /**
     * Distance in km
     * @var float
     */
    private $distance;

    /**
     *
     * @var Bridge
     */
    private $nearbyBridge;

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
     * @return number
     */
    public function getNearbyBridgeId()
    {
        return $this->nearbyBridgeId;
    }

    /**
     * @param number $nearbyBridgeId
     */
    public function setNearbyBridgeId($nearbyBridgeId)
    {
        $this->nearbyBridgeId = $nearbyBridgeId;
    }

    /**
     * @return number
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * @param number $distance
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;
    }

    /**
     * @return \BrugOpen\Model\Bridge
     */
    public function getNearbyBridge()
    {
        return $this->nearbyBridge;
    }

    /**
     * @param \BrugOpen\Model\Bridge $nearbyBridge
     */
    public function setNearbyBridge($nearbyBridge)
    {
        $this->nearbyBridge = $nearbyBridge;
    }

}
