<?php
namespace BrugOpen\Datex\Model;

class PointByCoordinates

{

    /**
     *
     * @var int
     */
    private $bearing;

    /**
     *
     * @var PointCoordinates
     */
    private $pointCoordinates;

    /**
     *
     * @return number
     */
    public function getBearing()
    {
        return $this->bearing;
    }

    /**
     *
     * @param number $bearing
     */
    public function setBearing($bearing)
    {
        $this->bearing = $bearing;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\PointCoordinates
     */
    public function getPointCoordinates()
    {
        return $this->pointCoordinates;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\PointCoordinates $pointCoordinates
     */
    public function setPointCoordinates($pointCoordinates)
    {
        $this->pointCoordinates = $pointCoordinates;
    }
}
