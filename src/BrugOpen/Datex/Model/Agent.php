<?php

namespace BrugOpen\Datex\Model;

class Agent
{

    /**
     *
     * @var InternationalIdentifier
     */
    private $internationalIdentifier;

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
