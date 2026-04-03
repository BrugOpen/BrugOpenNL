<?php

namespace BrugOpen\Datex\Model;

class ExchangeContext
{

    /**
     *
     * @var string
     */
    private $codedExchangeProtoccol;

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
     * Get the value of codedExchangeProtoccol
     *
     * @return string
     */
    public function getCodedExchangeProtoccol()
    {
        return $this->codedExchangeProtoccol;
    }

    /**
     * Set the value of codedExchangeProtoccol
     *
     * @param string $codedExchangeProtoccol
     * @return void
     */
    public function setCodedExchangeProtoccol($codedExchangeProtoccol)
    {
        $this->codedExchangeProtoccol = $codedExchangeProtoccol;
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
