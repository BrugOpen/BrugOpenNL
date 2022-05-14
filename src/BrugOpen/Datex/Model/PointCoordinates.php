<?php
namespace BrugOpen\Datex\Model;

class PointCoordinates
{

    /**
     *
     * @var number
     */
    private $latitude;

    /**
     *
     * @var number
     */
    private $longitude;

    /**
     *
     * @return number
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     *
     * @param number $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     *
     * @return number
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     *
     * @param number $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }
}
