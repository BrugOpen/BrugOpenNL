<?php
namespace BrugOpen\Datex\Model;

class Point
{

    /**
     *
     * @var AlertCPoint
     */
    private $alertCPoint;

    /**
     *
     * @var PointCoordinates
     */
    private $locationForDisplay;

    /**
     *
     * @var PointByCoordinates
     */
    private $pointByCoordinates;

    /**
     *
     * @return \BrugOpen\Datex\Model\AlertCPoint
     */
    public function getAlertCPoint()
    {
        return $this->alertCPoint;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\AlertCPoint $alertCPoint
     */
    public function setAlertCPoint($alertCPoint)
    {
        $this->alertCPoint = $alertCPoint;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\PointByCoordinates
     */
    public function getLocationForDisplay()
    {
        return $this->locationForDisplay;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\PointCoordinates $locationForDisplay
     */
    public function setLocationForDisplay($locationForDisplay)
    {
        $this->locationForDisplay = $locationForDisplay;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\PointByCoordinates
     */
    public function getPointByCoordinates()
    {
        return $this->pointByCoordinates;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\PointCoordinates $pointByCoordinates
     */
    public function setPointByCoordinates($pointByCoordinates)
    {
        $this->pointByCoordinates = $pointByCoordinates;
    }
}
