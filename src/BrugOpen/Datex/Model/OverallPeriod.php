<?php
namespace BrugOpen\Datex\Model;

class OverallPeriod
{

    /**
     *
     * @var \DateTime
     */
    private $overallStartTime;

    /**
     *
     * @var \DateTime
     */
    private $overallEndTime;

    /**
     *
     * @return \DateTime
     */
    public function getOverallStartTime()
    {
        return $this->overallStartTime;
    }

    /**
     *
     * @param \DateTime $overallStartTime
     */
    public function setOverallStartTime($overallStartTime)
    {
        $this->overallStartTime = $overallStartTime;
    }

    /**
     *
     * @return \DateTime
     */
    public function getOverallEndTime()
    {
        return $this->overallEndTime;
    }

    /**
     *
     * @param \DateTime $overallEndTime
     */
    public function setOverallEndTime($overallEndTime)
    {
        $this->overallEndTime = $overallEndTime;
    }
}
