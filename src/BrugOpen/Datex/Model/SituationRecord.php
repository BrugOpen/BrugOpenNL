<?php
namespace BrugOpen\Datex\Model;

use DateTime;

class SituationRecord
{

    /**
     *
     * @var string
     */
    private $id;

    /**
     *
     * @var number
     */
    private $version;

    /**
     *
     * @var DateTime
     */
    private $situationRecordCreationTime;

    /**
     *
     * @var DateTime
     */
    private $situationRecordVersionTime;

    /**
     *
     * @var string
     */
    private $probabilityOfOccurrence;

    /**
     *
     * @var MultiLingualString
     */
    private $source;

    /**
     *
     * @var Validity
     */
    private $validity;

    /**
     *
     * @var \BrugOpen\Datex\Model\Point
     */
    private $groupOfLocations;

    /**
     *
     * @var \BrugOpen\Datex\Model\Management
     */
    private $management;

    /**
     *
     * @var string
     */
    private $operatorActionStatus;

    /**
     *
     * @var string
     */
    private $complianceOption;

    /**
     *
     * @var string
     */
    private $generalNetworkManagementType;

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
     * @return number
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     *
     * @param number $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     *
     * @return DateTime
     */
    public function getSituationRecordCreationTime()
    {
        return $this->situationRecordCreationTime;
    }

    /**
     *
     * @param DateTime $situationRecordCreationTime
     */
    public function setSituationRecordCreationTime($situationRecordCreationTime)
    {
        $this->situationRecordCreationTime = $situationRecordCreationTime;
    }

    /**
     *
     * @return DateTime
     */
    public function getSituationRecordVersionTime()
    {
        return $this->situationRecordVersionTime;
    }

    /**
     *
     * @param DateTime $situationRecordVersionTime
     */
    public function setSituationRecordVersionTime($situationRecordVersionTime)
    {
        $this->situationRecordVersionTime = $situationRecordVersionTime;
    }

    /**
     *
     * @return string
     */
    public function getProbabilityOfOccurrence()
    {
        return $this->probabilityOfOccurrence;
    }

    /**
     *
     * @param string $probabilityOfOccurrence
     */
    public function setProbabilityOfOccurrence($probabilityOfOccurrence)
    {
        $this->probabilityOfOccurrence = $probabilityOfOccurrence;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\MultiLingualString
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\MultiLingualString $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\Validity
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\Validity $validity
     */
    public function setValidity($validity)
    {
        $this->validity = $validity;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\Point
     */
    public function getGroupOfLocations()
    {
        return $this->groupOfLocations;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\Point $groupOfLocations
     */
    public function setGroupOfLocations($groupOfLocations)
    {
        $this->groupOfLocations = $groupOfLocations;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\Management
     */
    public function getManagement()
    {
        return $this->management;
    }

    /**
     *
     * @param Management $management
     */
    public function setManagement($management)
    {
        $this->management = $management;
    }

    /**
     *
     * @return string
     */
    public function getOperatorActionStatus()
    {
        return $this->operatorActionStatus;
    }

    /**
     *
     * @param string $operatorActionStatus
     */
    public function setOperatorActionStatus($operatorActionStatus)
    {
        $this->operatorActionStatus = $operatorActionStatus;
    }

    /**
     *
     * @return string
     */
    public function getComplianceOption()
    {
        return $this->complianceOption;
    }

    /**
     *
     * @param string $complianceOption
     */
    public function setComplianceOption($complianceOption)
    {
        $this->complianceOption = $complianceOption;
    }

    /**
     *
     * @return string
     */
    public function getGeneralNetworkManagementType()
    {
        return $this->generalNetworkManagementType;
    }

    /**
     *
     * @param string $generalNetworkManagementType
     */
    public function setGeneralNetworkManagementType($generalNetworkManagementType)
    {
        $this->generalNetworkManagementType = $generalNetworkManagementType;
    }
}
