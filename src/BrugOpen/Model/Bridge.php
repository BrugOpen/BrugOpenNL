<?php

namespace BrugOpen\Model;

use BrugOpen\Geo\Model\LatLng;

class Bridge
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
    private $name;

    /**
     *
     * @var string
     */
    private $title;

    /**
     *
     * @var string
     */
    private $city;

    /**
     *
     * @var string
     */
    private $city2;

    /**
     *
     * @var LatLng
     */
    private $latLng;

    /**
     *
     * @var string
     */
    private $isrsCode;

    /**
     * @var int[]
     */
    private $connectedSegmentIds;

    /**
     * @var float
     */
    private $clearance;

    /**
     *
     * @var int
     */
    private $lastStartedOperationId;

    /**
     * @var boolean
     */
    private $announceApproaches;

    /**
     * @var boolean
     */
    private $active;

    /**
     * @var int
     */
    private $minOperationDuration;

    /**
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCity2()
    {
        return $this->city2;
    }

    /**
     * @param string $city2
     */
    public function setCity2($city2)
    {
        $this->city2 = $city2;
    }

    /**
     * @return \BrugOpen\Geo\Model\LatLng
     */
    public function getLatLng()
    {
        return $this->latLng;
    }

    /**
     * @param \BrugOpen\Geo\Model\LatLng $latLng
     */
    public function setLatLng($latLng)
    {
        $this->latLng = $latLng;
    }

    /**
     * @return string
     */
    public function getIsrsCode()
    {
        return $this->isrsCode;
    }

    /**
     * @param string $isrsCode
     */
    public function setIsrsCode($isrsCode)
    {
        $this->isrsCode = $isrsCode;
    }

    /**
     * @return number
     */
    public function getLastStartedOperationId()
    {
        return $this->lastStartedOperationId;
    }

    /**
     * @param number $lastStartedOperationId
     */
    public function setLastStartedOperationId($lastStartedOperationId)
    {
        $this->lastStartedOperationId = $lastStartedOperationId;
    }

    /**
     * @return int[]
     */
    public function getConnectedSegmentIds()
    {
        return $this->connectedSegmentIds;
    }

    /**
     * @param int[] $connectedSegmentIds
     */
    public function setConnectedSegmentIds($connectedSegmentIds)
    {
        $this->connectedSegmentIds = $connectedSegmentIds;
    }

    /**
     * @return float
     */
    public function getClearance()
    {
        return $this->clearance;
    }

    /**
     * @param float $clearance
     */
    public function setClearance($clearance)
    {
        $this->clearance = $clearance;
    }

    /**
     * @return boolean
     */
    public function getAnnounceApproaches()
    {
        return $this->announceApproaches;
    }

    /**
     * @param boolean $announceApproaches
     */
    public function setAnnounceApproaches($announceApproaches)
    {
        $this->announceApproaches = $announceApproaches;
    }

    /**
     * @return boolean|null
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean|null $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return int
     */
    public function getMinOperationDuration()
    {
        return $this->minOperationDuration;
    }

    /**
     * @param int $minOperationDuration
     */
    public function setMinOperationDuration($minOperationDuration)
    {
        $this->minOperationDuration = $minOperationDuration;
    }
}
