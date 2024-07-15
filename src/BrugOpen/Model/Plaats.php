<?php

namespace BrugOpen\Model;

class Plaats
{

    /**
     *
     * @var int
     */
    private $id;

    /**
     *
     * @var string
     */
    private $naam;

    /**
     *
     * @var string
     */
    private $provincie;

    /**
     *
     * @var float[][]
     */
    private $geometrie;

    /**
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getNaam()
    {
        return $this->naam;
    }

    /**
     * @param string $naam
     */
    public function setNaam($naam)
    {
        $this->naam = $naam;
    }

    /**
     * @return string
     */
    public function getProvincie()
    {
        return $this->provincie;
    }

    /**
     * @param string $provincie
     */
    public function setProvincie($provincie)
    {
        $this->provincie = $provincie;
    }

    /**
     * @return multitype:
     */
    public function getGeometrie()
    {
        return $this->geometrie;
    }

    /**
     * @param multitype: $geometrie
     */
    public function setGeometrie($geometrie)
    {
        $this->geometrie = $geometrie;
    }

}
