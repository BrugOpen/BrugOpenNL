<?php
namespace BrugOpen\Datex\Model;

class AlertCMethod2PrimaryPointLocation
{

    /**
     *
     * @var AlertCLocation
     */
    private $alertCLocation;

    // <xs:element name="alertCLocation" type="D2LogicalModel:AlertCLocation" />

    /**
     *
     * @return \BrugOpen\Datex\Model\AlertCLocation
     */
    public function getAlertCLocation()
    {
        return $this->alertCLocation;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\AlertCLocation $alertCLocation
     */
    public function setAlertCLocation($alertCLocation)
    {
        $this->alertCLocation = $alertCLocation;
    }
}
