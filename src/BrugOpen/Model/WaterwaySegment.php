<?php

namespace BrugOpen\Model;

class WaterwaySegment
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
    private $title;

    /**
     *
     * @var float[][]
     */
    private $coordinates;

    /**
     * @return int the $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string the $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return float[][] the $coordinates
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param float[][] $coordinates
     */
    public function setCoordinates($coordinates)
    {
        $this->coordinates = $coordinates;
    }

}
