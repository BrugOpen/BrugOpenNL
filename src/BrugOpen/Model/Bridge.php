<?php

namespace BrugOpen\Model;

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
     * @return \BrugOpen\Model\LatLng
     */
    public function getLatLng()
    {
        return $this->latLng;
    }

    /**
     * @param \BrugOpen\Model\LatLng $latLng
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

}
