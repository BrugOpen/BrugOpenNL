<?php

namespace BrugOpen\Projection\Model;

class ProjectedBridgePassage
{

    /**
     * @var string
     */
    private $journeyId;

    /**
     * @var int
     */
    private $bridgeId;

    /**
     * @var \DateTime
     */
    private $datetimeProjectedPassage;

    /**
     * @var int
     */
    private $standardDeviation;

    /**
     * @var float
     */
    private $operationProbability;

    /**
     * @var string
     */
    private $situationId;

    /**
     * @var \DateTime
     */
    private $datetimeProjection;

    /**
     * @return string
     */
    public function getJourneyId()
    {
        return $this->journeyId;
    }

    /**
     * @param string $journeyId
     */
    public function setJourneyId($journeyId)
    {
        $this->journeyId = $journeyId;
    }

    /**
     * @return int
     */
    public function getBridgeId()
    {
        return $this->bridgeId;
    }

    /**
     * @param int $bridgeId
     */
    public function setBridgeId($bridgeId)
    {
        $this->bridgeId = $bridgeId;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeProjectedPassage()
    {
        return $this->datetimeProjectedPassage;
    }

    /**
     * @param \DateTime $datetimeProjectedPassage
     */
    public function setDatetimeProjectedPassage($datetimeProjectedPassage)
    {
        $this->datetimeProjectedPassage = $datetimeProjectedPassage;
    }

    /**
     * @return int
     */
    public function getStandardDeviation()
    {
        return $this->standardDeviation;
    }

    /**
     * @param int $standardDeviation
     */
    public function setStandardDeviation($standardDeviation)
    {
        $this->standardDeviation = $standardDeviation;
    }

    /**
     * @return float
     */
    public function getOperationProbability()
    {
        return $this->operationProbability;
    }

    /**
     * @param float $operationProbability
     */
    public function setOperationProbability($operationProbability)
    {
        $this->operationProbability = $operationProbability;
    }

    /**
     * @return string
     */
    public function getSituationId()
    {
        return $this->situationId;
    }

    /**
     * @param string $situationId
     */
    public function setSituationId($situationId)
    {
        $this->situationId = $situationId;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeProjection()
    {
        return $this->datetimeProjection;
    }

    /**
     * @param \DateTime $datetimeProjection
     */
    public function setDatetimeProjection($datetimeProjection)
    {
        $this->datetimeProjection = $datetimeProjection;
    }
}
