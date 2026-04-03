<?php

namespace BrugOpen\Datex\Model;

class ExternalReferencing
{

    /**
     *
     * @var string
     */
    private $externalLocationCode;

    /**
     *
     * @var string
     */
    private $externalReferencingSystem;

    /**
     *
     * @return string
     */
    public function getExternalLocationCode()
    {
        return $this->externalLocationCode;
    }

    /**
     * Set the value of externalLocationCode
     *
     * @param string $externalLocationCode
     */
    public function setExternalLocationCode($externalLocationCode)
    {
        $this->externalLocationCode = $externalLocationCode;
    }

    /**
     * Get the value of externalReferencingSystem
     *
     * @return string
     */
    public function getExternalReferencingSystem()
    {
        return $this->externalReferencingSystem;
    }

    /**
     * Set the value of externalReferencingSystem
     *
     * @param string $externalReferencingSystem
     */
    public function setExternalReferencingSystem($externalReferencingSystem)
    {
        $this->externalReferencingSystem = $externalReferencingSystem;
    }
}
