<?php

namespace BrugOpen\Geo\Model;

class LatLng
{

    /**
     *
     * @var float
     */
    private $lat;

    /**
     *
     * @var float
     */
    private $lng;

    /**
     *
     * @param float|string $a
     * @param float|null $b
     */
    public function __construct($a, $b = null)
    {
        if (is_string($a) && is_null($b)) {

            $parts = explode(',', $a, 2);

            $this->lat = $parts[0];
            $this->lng = $parts[1];

        } else {

            $this->lat = $a;
            $this->lng = $b;

        }

    }

    /**
     *
     * @return number
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     *
     * @param number $lat
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
    }

    /**
     *
     * @return number
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     *
     * @param number $lng
     */
    public function setLng($lng)
    {
        $this->lng = $lng;
    }

}
