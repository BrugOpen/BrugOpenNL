<?php
namespace BrugOpen\Datex\Model;

class HeaderInformation
{

    /**
     *
     * @var string
     */
    private $areaOfInterest;

    /**
     *
     * @var string
     */
    private $confidentiality;

    /**
     *
     * @var string
     */
    private $informationStatus;

    /**
     *
     * @var string
     */
    private $urgency;

    /**
     *
     * @return string
     */
    public function getAreaOfInterest()
    {
        return $this->areaOfInterest;
    }

    /**
     *
     * @param string $areaOfInterest
     */
    public function setAreaOfInterest($areaOfInterest)
    {
        $this->areaOfInterest = $areaOfInterest;
    }

    /**
     *
     * @return string
     */
    public function getConfidentiality()
    {
        return $this->confidentiality;
    }

    /**
     *
     * @param string $confidentiality
     */
    public function setConfidentiality($confidentiality)
    {
        $this->confidentiality = $confidentiality;
    }

    /**
     *
     * @return string
     */
    public function getInformationStatus()
    {
        return $this->informationStatus;
    }

    /**
     *
     * @param string $informationStatus
     */
    public function setInformationStatus($informationStatus)
    {
        $this->informationStatus = $informationStatus;
    }

    /**
     *
     * @return string
     */
    public function getUrgency()
    {
        return $this->urgency;
    }

    /**
     *
     * @param string $urgency
     */
    public function setUrgency($urgency)
    {
        $this->urgency = $urgency;
    }
}
