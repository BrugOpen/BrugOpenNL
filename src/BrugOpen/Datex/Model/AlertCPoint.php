<?php
namespace BrugOpen\Datex\Model;

class AlertCPoint
{

    /**
     *
     * @var string
     */
    private $alertCLocationCountryCode;

    /**
     *
     * @var string
     */
    private $alertCLocationTableNumber;

    /**
     *
     * @var string
     */
    private $alertCLocationTableVersion;

    /**
     *
     * @var AlertCDirection
     */
    private $alertCDirection;

    /**
     *
     * @var AlertCMethod2PrimaryPointLocation
     */
    private $alertCMethod2PrimaryPointLocation;

    /**
     *
     * @return string
     */
    public function getAlertCLocationCountryCode()
    {
        return $this->alertCLocationCountryCode;
    }

    /**
     *
     * @param string $alertCLocationCountryCode
     */
    public function setAlertCLocationCountryCode($alertCLocationCountryCode)
    {
        $this->alertCLocationCountryCode = $alertCLocationCountryCode;
    }

    /**
     *
     * @return string
     */
    public function getAlertCLocationTableNumber()
    {
        return $this->alertCLocationTableNumber;
    }

    /**
     *
     * @param string $alertCLocationTableNumber
     */
    public function setAlertCLocationTableNumber($alertCLocationTableNumber)
    {
        $this->alertCLocationTableNumber = $alertCLocationTableNumber;
    }

    /**
     *
     * @return string
     */
    public function getAlertCLocationTableVersion()
    {
        return $this->alertCLocationTableVersion;
    }

    /**
     *
     * @param string $alertCLocationTableVersion
     */
    public function setAlertCLocationTableVersion($alertCLocationTableVersion)
    {
        $this->alertCLocationTableVersion = $alertCLocationTableVersion;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\AlertCDirection
     */
    public function getAlertCDirection()
    {
        return $this->alertCDirection;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\AlertCDirection $alertCDirection
     */
    public function setAlertCDirection($alertCDirection)
    {
        $this->alertCDirection = $alertCDirection;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\AlertCMethod2PrimaryPointLocation
     */
    public function getAlertCMethod2PrimaryPointLocation()
    {
        return $this->alertCMethod2PrimaryPointLocation;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\AlertCMethod2PrimaryPointLocation $alertCMethod2PrimaryPointLocation
     */
    public function setAlertCMethod2PrimaryPointLocation($alertCMethod2PrimaryPointLocation)
    {
        $this->alertCMethod2PrimaryPointLocation = $alertCMethod2PrimaryPointLocation;
    }
}
