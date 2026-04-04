<?php

namespace BrugOpen\Datex\Model;

class ExchangeContext
{

    /**
     *
     * @var string
     */
    private $codedExchangeProtocol;

    /**
     *
     * @var string
     */
    private $exchangeSpecificationVersion;

    /**
     *
     * @var Agent
     */
    private $supplierOrCisRequester;

    /**
     * Get the value of codedExchangeProtocol
     *
     * @return string
     */
    public function getCodedExchangeProtocol()
    {
        return $this->codedExchangeProtocol;
    }

    /**
     * Set the value of codedExchangeProtocol
     *
     * @param string $codedExchangeProtocol
     * @return void
     */
    public function setCodedExchangeProtocol($codedExchangeProtocol)
    {
        $this->codedExchangeProtocol = $codedExchangeProtocol;
    }

    /**
     * Get the value of exchangeSpecificationVersion
     *
     * @return string
     */
    public function getExchangeSpecificationVersion()
    {
        return $this->exchangeSpecificationVersion;
    }

    /**
     * Set the value of exchangeSpecificationVersion
     *
     * @param string $exchangeSpecificationVersion
     * @return void
     */
    public function setExchangeSpecificationVersion($exchangeSpecificationVersion)
    {
        $this->exchangeSpecificationVersion = $exchangeSpecificationVersion;
    }

    /**
     * Get the value of supplierOrCisRequester
     *
     * @return Agent
     */
    public function getSupplierOrCisRequester()
    {
        return $this->supplierOrCisRequester;
    }

    /**
     * Set the value of supplierOrCisRequester
     *
     * @param Agent $supplierOrCisRequester
     * @return void
     */
    public function setSupplierOrCisRequester($supplierOrCisRequester)
    {
        $this->supplierOrCisRequester = $supplierOrCisRequester;
    }
}
