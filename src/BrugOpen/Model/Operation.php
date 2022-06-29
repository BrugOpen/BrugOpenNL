<?php
namespace BrugOpen\Model;

class Operation
{

    /**
     *
     * @var int
     */
    private $id;

    /**
     *
     * @var string
     */
    private $eventId;

    /**
     *
     * @var int
     */
    private $bridgeId;

    /**
     *
     * @var int
     */
    private $certainty;

    /**
     *
     * @var \DateTime
     */
    private $dateTimeStart;

    /**
     *
     * @var \DateTime
     */
    private $dateTimeEnd;

    /**
     *
     * @var boolean
     */
    private $finished;

    /**
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param string $eventId
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * @return number
     */
    public function getBridgeId()
    {
        return $this->bridgeId;
    }

    /**
     * @param number $bridgeId
     */
    public function setBridgeId($bridgeId)
    {
        $this->bridgeId = $bridgeId;
    }

    /**
     * @return number
     */
    public function getCertainty()
    {
        return $this->certainty;
    }

    /**
     * @param number $certainty
     */
    public function setCertainty($certainty)
    {
        $this->certainty = $certainty;
    }

    /**
     * @return \DateTime
     */
    public function getDateTimeStart()
    {
        return $this->dateTimeStart;
    }

    /**
     * @param \DateTime $dateTimeStart
     */
    public function setDateTimeStart($dateTimeStart)
    {
        $this->dateTimeStart = $dateTimeStart;
    }

    /**
     * @return \DateTime
     */
    public function getDateTimeEnd()
    {
        return $this->dateTimeEnd;
    }

    /**
     * @param \DateTime $dateTimeEnd
     */
    public function setDateTimeEnd($dateTimeEnd)
    {
        $this->dateTimeEnd = $dateTimeEnd;
    }

    /**
     * @return boolean
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * @param boolean $finished
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;
    }

}