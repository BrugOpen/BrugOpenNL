<?php
namespace BrugOpen\Datex\Model;

class InternationalIdentifier
{

    /**
     *
     * @var string
     */
    private $country;

    /**
     *
     * @var string
     */
    private $nationalIdentifier;

    /**
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     *
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     *
     * @return string
     */
    public function getNationalIdentifier()
    {
        return $this->nationalIdentifier;
    }

    /**
     *
     * @param string $nationalIdentifier
     */
    public function setNationalIdentifier($nationalIdentifier)
    {
        $this->nationalIdentifier = $nationalIdentifier;
    }
}
