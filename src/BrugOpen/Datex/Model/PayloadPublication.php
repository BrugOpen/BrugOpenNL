<?php
namespace BrugOpen\Datex\Model;

use DateTime;

class PayloadPublication
{

    /**
     *
     * @var string
     */
    private $lang;

    /**
     *
     * @var DateTime
     */
    private $publicationTime;

    /**
     *
     * @var InternationalIdentifier
     */
    private $publicationCreator;

    /**
     *
     * @var Situation[]
     */
    private $situations;

    /**
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     *
     * @param string $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     *
     * @return DateTime
     */
    public function getPublicationTime()
    {
        return $this->publicationTime;
    }

    /**
     *
     * @param DateTime $publicationTime
     */
    public function setPublicationTime($publicationTime)
    {
        $this->publicationTime = $publicationTime;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\InternationalIdentifier
     */
    public function getPublicationCreator()
    {
        return $this->publicationCreator;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\InternationalIdentifier $publicationCreator
     */
    public function setPublicationCreator($publicationCreator)
    {
        $this->publicationCreator = $publicationCreator;
    }

    /**
     *
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
