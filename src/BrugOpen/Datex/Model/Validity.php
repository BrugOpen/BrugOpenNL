<?php
namespace BrugOpen\Datex\Model;

class Validity
{

    /**
     *
     * @var string
     */
    private $validityStatus;

    /**
     *
     * @var boolean
     */
    private $overrunning;

    /**
     *
     * @var OverallPeriod
     */
    private $validityTimeSpecification;

    /**
     *
     * @return string
     */
    public function getValidityStatus()
    {
        return $this->validityStatus;
    }

    /**
     *
     * @param string $validityStatus
     */
    public function setValidityStatus($validityStatus)
    {
        $this->validityStatus = $validityStatus;
    }

    /**
     *
     * @return boolean
     */
    public function isOverrunning()
    {
        return $this->overrunning;
    }

    /**
     *
     * @param boolean $overrunning
     */
    public function setOverrunning($overrunning)
    {
        $this->overrunning = $overrunning;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\OverallPeriod
     */
    public function getValidityTimeSpecification()
    {
        return $this->validityTimeSpecification;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\OverallPeriod $validityTimeSpecification
     */
    public function setValidityTimeSpecification($validityTimeSpecification)
    {
        $this->validityTimeSpecification = $validityTimeSpecification;
    }
}
