<?php

namespace BrugOpen\Datex\Model;

class DynamicInformation
{
    /**
     *
     * @var string
     */
    private $exchangeStatus;

    /**
     *
     * @var \DateTime
     */
    private $messageGenerationTimestamp;

    /**
     * Get the value of exchangeStatus
     *
     * @return string
     */
    public function getExchangeStatus()
    {
        return $this->exchangeStatus;
    }

    /**
     * Set the value of exchangeStatus
     *
     * @param string $exchangeStatus
     * @return void
     */
    public function setExchangeStatus($exchangeStatus)
    {
        $this->exchangeStatus = $exchangeStatus;
    }

    /**
     * Get the value of messageGenerationTimestamp
     *
     * @return \DateTime
     */
    public function getMessageGenerationTimestamp()
    {
        return $this->messageGenerationTimestamp;
    }

    /**
     * Set the value of messageGenerationTimestamp
     *
     * @param \DateTime $messageGenerationTimestamp
     * @return void
     */
    public function setMessageGenerationTimestamp($messageGenerationTimestamp)
    {
        $this->messageGenerationTimestamp = $messageGenerationTimestamp;
    }
}
