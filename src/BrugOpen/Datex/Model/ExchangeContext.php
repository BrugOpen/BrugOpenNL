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
     * @var InternationalIdentifier
     */
    private $internationalIdentifier;

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
     * Get the value of internationalIdentifier
     *
     * @return InternationalIdentifier
     */
    public function getInternationalIdentifier()
    {
        return $this->internationalIdentifier;
    }

    /**
     * Set the value of internationalIdentifier
     *
     * @param InternationalIdentifier $internationalIdentifier
     * @return void
     */
    public function setInternationalIdentifier($internationalIdentifier)
    {
        $this->internationalIdentifier = $internationalIdentifier;
    }
}
