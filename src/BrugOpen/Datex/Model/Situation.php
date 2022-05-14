<?php
namespace BrugOpen\Datex\Model;

use DateTime;

class Situation
{

    /**
     *
     * @var string
     */
    private $id;

    /**
     *
     * @var string
     */
    private $version;

    /**
     *
     * @var string
     */
    private $overallSeverity;

    /**
     *
     * @var DateTime
     */
    private $situationVersionTime;

    /**
     *
     * @var HeaderInformation
     */
    private $headerInformation;

    /**
     *
     * @var SituationRecord
     */
    private $situationRecord;

    /**
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     *
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     *
     * @return string
     */
    public function getOverallSeverity()
    {
        return $this->overallSeverity;
    }

    /**
     *
     * @param string $overallSeverity
     */
    public function setOverallSeverity($overallSeverity)
    {
        $this->overallSeverity = $overallSeverity;
    }

    /**
     *
     * @return DateTime
     */
    public function getSituationVersionTime()
    {
        return $this->situationVersionTime;
    }

    /**
     *
     * @param DateTime $situationVersionTime
     */
    public function setSituationVersionTime($situationVersionTime)
    {
        $this->situationVersionTime = $situationVersionTime;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\HeaderInformation
     */
    public function getHeaderInformation()
    {
        return $this->headerInformation;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\HeaderInformation $headerInformation
     */
    public function setHeaderInformation($headerInformation)
    {
        $this->headerInformation = $headerInformation;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\SituationRecord
     */
    public function getSituationRecord()
    {
        return $this->situationRecord;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\SituationRecord $situationRecord
     */
    public function setSituationRecord($situationRecord)
    {
        $this->situationRecord = $situationRecord;
    }
}
