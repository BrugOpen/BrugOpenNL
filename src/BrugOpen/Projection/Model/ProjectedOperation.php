<?php

namespace BrugOpen\Projection\Model;

class ProjectedOperation
{

    const EVENT_PREFIX = 'BONL01';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $eventId;

    /**
     * @var int
     */
    private $version;

    /**
     * @var int
     */
    private $bridgeId;

    /**
     * @var int
     */
    private $certainty;

    /**
     * @var \DateTime
     */
    private $timeStart;

    /**
     * @var \DateTime
     */
    private $timeEnd;

    /**
     * @var ProjectedBridgePassage[]
     */
    private $projectedPassages;

    /**
     * @var \DateTime
     */
    private $datetimeProjection;

    /**
     * Get the value of id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @param int $id
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the value of eventId
     *
     * @return string
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * Set the value of eventId
     *
     * @param string $eventId
     * @return void
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * Get the value of version
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the value of version
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get the value of bridgeId
     *
     * @return int
     */
    public function getBridgeId()
    {
        return $this->bridgeId;
    }

    /**
     * Set the value of bridgeId
     *
     * @param int $bridgeId
     * @return void
     */
    public function setBridgeId($bridgeId)
    {
        $this->bridgeId = $bridgeId;
    }

    /**
     * Get the value of certainty
     *
     * @return int
     */
    public function getCertainty()
    {
        return $this->certainty;
    }

    /**
     * Set the value of certainty
     *
     * @param int $certainty
     * @return void
     */
    public function setCertainty($certainty)
    {
        $this->certainty = $certainty;
    }

    /**
     * Get the value of timeStart
     *
     * @return \DateTime
     */
    public function getTimeStart()
    {
        return $this->timeStart;
    }

    /**
     * Set the value of timeStart
     *
     * @param \DateTime $timeStart
     * @return void
     */
    public function setTimeStart($timeStart)
    {
        $this->timeStart = $timeStart;
    }

    /**
     * Get the value of timeEnd
     *
     * @return \DateTime
     */
    public function getTimeEnd()
    {
        return $this->timeEnd;
    }

    /**
     * Set the value of timeEnd
     *
     * @param \DateTime $timeEnd
     * @return void
     */
    public function setTimeEnd($timeEnd)
    {
        $this->timeEnd = $timeEnd;
    }

    /**
     * Get the value of projectedPassages
     * @return ProjectedBridgePassage[]
     */
    public function getProjectedPassages()
    {
        return $this->projectedPassages;
    }

    /**
     * Set the value of projectedPassages
     *
     * @param ProjectedBridgePassage[] $projectedPassages
     * @return void
     */
    public function setProjectedPassages($projectedPassages)
    {
        $this->projectedPassages = $projectedPassages;
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
