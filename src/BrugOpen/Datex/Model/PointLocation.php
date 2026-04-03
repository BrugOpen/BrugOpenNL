<?php

namespace BrugOpen\Datex\Model;

class PointLocation
{

    /**
     *
     * @var ExternalReferencing
     */
    private $externalReferencing;

    /**
     *
     * @var PointCoordinates
     */
    private $coordinatesForDisplay;

    /**
     *
     * @var string
     */
    private $supplementaryPositionalDescription;

    /**
     *
     * @var PointByCoordinates
     */
    private $pointByCoordinates;

    /**
     *
     * @var AlertCPoint
     */
    private $alertCPoint;

    /**
     * Get the value of externalReferencing
     *
     * @return ExternalReferencing
     */
    public function getExternalReferencing()
    {
        return $this->externalReferencing;
    }

    /**
     * Set the value of externalReferencing
     *
     * @param ExternalReferencing $externalReferencing
     * @return void
     */
    public function setExternalReferencing($externalReferencing)
    {
        $this->externalReferencing = $externalReferencing;
    }

    /**
     * Get the value of coordinatesForDisplay
     *
     * @return PointCoordinates
     */
    public function getCoordinatesForDisplay()
    {
        return $this->coordinatesForDisplay;
    }

    /**
     * Set the value of coordinatesForDisplay
     *
     * @param PointCoordinates $coordinatesForDisplay
     * @return void
     */
    public function setCoordinatesForDisplay($coordinatesForDisplay)
    {
        $this->coordinatesForDisplay = $coordinatesForDisplay;
    }

    /**
     * Get the value of supplementaryPositionalDescription
     *
     * @return string
     */
    public function getSupplementaryPositionalDescription()
    {
        return $this->supplementaryPositionalDescription;
    }

    /**
     * Set the value of supplementaryPositionalDescription
     *
     * @param string $supplementaryPositionalDescription
     * @return void
     */
    public function setSupplementaryPositionalDescription($supplementaryPositionalDescription)
    {
        $this->supplementaryPositionalDescription = $supplementaryPositionalDescription;
    }

    /**
     * Get the value of pointByCoordinates
     *
     * @return PointByCoordinates
     */
    public function getPointByCoordinates()
    {
        return $this->pointByCoordinates;
    }

    /**
     * Set the value of pointByCoordinates
     *
     * @param PointByCoordinates $pointByCoordinates
     * @return void
     */
    public function setPointByCoordinates($pointByCoordinates)
    {
        $this->pointByCoordinates = $pointByCoordinates;
    }

    /**
     * Get the value of alertCPoint
     *
     * @return AlertCPoint
     */
    public function getAlertCPoint()
    {
        return $this->alertCPoint;
    }

    /**
     * Set the value of alertCPoint
     *
     * @param AlertCPoint $alertCPoint
     * @return void
     */
    public function setAlertCPoint($alertCPoint)
    {
        $this->alertCPoint = $alertCPoint;
    }
}
