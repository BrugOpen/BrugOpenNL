<?php

namespace BrugOpen\Datex\Model;

class MessageContainer
{

    /**
     * @var Payload
     */
    private $payload;

    /**
     * @var ExchangeInformation
     */
    private $exchangeInformation;

    /**
     * Get the value of payload
     *
     * @return Payload
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Set the value of payload
     *
     * @param Payload $payload
     * @return void
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Get the value of exchangeInformation
     *
     * @return ExchangeInformation
     */
    public function getExchangeInformation()
    {
        return $this->exchangeInformation;
    }

    /**
     * Set the value of exchangeInformation
     *
     * @param ExchangeInformation $exchangeInformation
     * @return void
     */
    public function setExchangeInformation($exchangeInformation)
    {
        $this->exchangeInformation = $exchangeInformation;
    }
}
