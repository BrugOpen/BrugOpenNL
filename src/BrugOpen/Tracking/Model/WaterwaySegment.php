<?php

namespace BrugOpen\Tracking\Model;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Geo\Model\LatLngBounds;
use BrugOpen\Geo\Model\Polygon;

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
     * @var Polygon
     */
    private $polygon;

    /**
     * @var LatLngBounds
     */
    private $bounds;

    /**
     * @var int[]
     */
    private $connectedSegmentIds;

    /**
     * @var LatLng[]
     */
    private $routePoints;

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
     * @return Polygon
     */
    public function getPolygon()
    {
        return $this->polygon;
    }

    /**
     * @param Polygon
     */
    public function setPolygon($polygon)
    {
        $this->polygon = $polygon;
    }

    /**
     * @return LatLngBounds
     */
    public function getBounds()
    {

        if ($this->bounds == null) {

            $this->bounds = new LatLngBounds($this->polygon->getPath());

        }

        return $this->bounds;

    }

    /**
     * @param LatLngBounds $bounds
     */
    public function setBounds($bounds)
    {
        $this->bounds = $bounds;
    }

    /**
     * @return int[]
     */
    public function getConnectedSegmentIds()
    {
        return $this->connectedSegmentIds;
    }

    /**
     * @param int[] $connectedSegmentIds
     */
    public function setConnectedSegmentIds($connectedSegmentIds)
    {
        $this->connectedSegmentIds = $connectedSegmentIds;
    }

    /**
     * @return LatLng[]
     */
    public function getRoutePoints()
    {
        return $this->routePoints;
    }

    /**
     * @param LatLng[] $routePoints
     */
    public function setRoutePoints($routePoints)
    {
        $this->routePoints = $routePoints;
    }

}
