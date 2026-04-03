<?php

namespace BrugOpen\Datex\Model;

class ExchangeInformation
{
    /**
     *
     * @var ExchangeContext
     */
    private $exchangeContext;

    /**
     *
     * @var DynamicInformation
     */
    private $dynamicInformation;

    /**
     * Get the value of exchangeContext
     *
     * @return ExchangeContext
     */
    public function getExchangeContext()
    {
        return $this->exchangeContext;
    }

    /**
     * Set the value of exchangeContext
     *
     * @param ExchangeContext $exchangeContext
     * @return void
     */
    public function setExchangeContext($exchangeContext)
    {
        $this->exchangeContext = $exchangeContext;
    }

    /**
     * Get the value of dynamicInformation
     *
     * @return DynamicInformation
     */
    public function getDynamicInformation()
    {
        return $this->dynamicInformation;
    }

    /**
     * Set the value of dynamicInformation
     *
     * @param DynamicInformation $dynamicInformation
     * @return void
     */
    public function setDynamicInformation($dynamicInformation)
    {
        $this->dynamicInformation = $dynamicInformation;
    }
}
