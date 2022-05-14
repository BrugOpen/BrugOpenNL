<?php
namespace BrugOpen\Datex\Model;

class AlertCLocation
{

    /**
     *
     * @var MultiLingualString
     */
    private $alertCLocationName;

    /**
     *
     * @var int
     */
    private $specificLocation;

    /**
     *
     * @return MultiLingualString
     */
    public function getAlertCLocationName()
    {
        return $this->alertCLocationName;
    }

    /**
     *
     * @param MultiLingualString $alertCLocationName
     */
    public function setAlertCLocationName($alertCLocationName)
    {
        $this->alertCLocationName = $alertCLocationName;
    }

    /**
     *
     * @return number
     */
    public function getSpecificLocation()
    {
        return $this->specificLocation;
    }

    /**
     *
     * @param number $specificLocation
     */
    public function setSpecificLocation($specificLocation)
    {
        $this->specificLocation = $specificLocation;
    }
}
