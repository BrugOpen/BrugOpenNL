<?php

namespace BrugOpen\Datex\Model;

class Payload
{

    /**
     * @var \DateTime
     */
    private $publicationTime;

    /**
     * @var InternationalIdentifier
     */
    private $publicationCreator;

    /**
     * @var Situation[]
     */
    private $situations;

    /**
     * Get the value of publicationTime
     *
     * @return \DateTime
     */
    public function getPublicationTime()
    {
        return $this->publicationTime;
    }

    /**
     * Set the value of publicationTime
     *
     * @param \DateTime $publicationTime
     * @return void
     */
    public function setPublicationTime($publicationTime)
    {
        $this->publicationTime = $publicationTime;
    }

    /**
     * Get the value of publicationCreator
     *
     * @return InternationalIdentifier
     */
    public function getPublicationCreator()
    {
        return $this->publicationCreator;
    }

    /**
     * Set the value of publicationCreator
     *
     * @param InternationalIdentifier $publicationCreator
     * @return void
     */
    public function setPublicationCreator($publicationCreator)
    {
        $this->publicationCreator = $publicationCreator;
    }

    /**
     * Get the value of situations
     * @return \BrugOpen\Datex\Model\Situation[]
     */
    public function getSituations()
    {
        return $this->situations;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\Situation[] $situations
     */
    public function setSituations($situations)
    {
        $this->situations = $situations;
    }
}
