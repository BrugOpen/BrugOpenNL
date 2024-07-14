<?php
namespace BrugOpen\Geo\Model;

class LatLngBounds
{

    /**
     * @var LatLng
     */
    private $northEast;

    /**
     * @var LatLng
     */
    private $southWest;

    /**
     * @param LatLng|LatLng[] $contents
     */
    public function __construct($contents)
    {

        if (is_array($contents)) {

            $latLng = array_shift($contents);

            $this->northEast = $latLng;
            $this->southWest = $latLng;

            foreach ($contents as $latLng) {

                $this->extend($latLng);

            }

        } else {

            $this->northEast = $contents;
            $this->southWest = $contents;

        }

    }

    /**
     * @return LatLng
     */
    public function getNorthEast()
    {
        return $this->northEast;
    }

    /**
     * @return LatLng
     */
    public function getSouthWest()
    {
        return $this->southWest;
    }

    /**
     * @return LatLng
     */
    public function getCenter()
    {

        $lat = ($this->northEast->getLat() + $this->southWest->getLat()) / 2;
        $lon = ($this->northEast->getLng() + $this->southWest->getLng()) / 2;

        return new LatLng($lat, $lon);

    }

    /**
     * @param LatLng $latLng
     * @return boolean
     */
    public function extend($latLng)
    {

        if ($latLng->getLat() > $this->northEast->getLat()) {

            $this->northEast = new LatLng($latLng->getLat(), $this->northEast->getLng());

        }

        if ($latLng->getLng() > $this->northEast->getLng()) {

            $this->northEast = new LatLng($this->northEast->getLat(), $latLng->getLng());

        }

        if ($latLng->getLat() < $this->southWest->getLat()) {

            $this->southWest = new LatLng($latLng->getLat(), $this->southWest->getLng());

        }

        if ($latLng->getLng() < $this->southWest->getLng()) {

            $this->southWest = new LatLng($this->southWest->getLat(), $latLng->getLng());

        }

    }

    /**
     * @param LatLng $latLng
     * @return boolean
     */
    public function isInBounds($latLng)
    {

        if ($latLng->getLat() > $this->northEast->getLat()) {

            return false;

        }

        if ($latLng->getLng() > $this->northEast->getLng()) {

            return false;

        }

        if ($latLng->getLat() < $this->southWest->getLat()) {

            return false;

        }

        if ($latLng->getLng() < $this->southWest->getLng()) {

            return false;

        }

        return true;

    }

    /**
     * @param LatLngBounds $bounds
     * @return boolean
     */
    public function overlaps($bounds)
    {

        if ($bounds->getSouthWest()->getLat() > $this->northEast->getLat()) {

            return false;

        }

        if ($bounds->getSouthWest()->getLng() > $this->northEast->getLng()) {

            return false;

        }

        if ($bounds->getNorthEast()->getLat() < $this->southWest->getLat()) {

            return false;

        }

        if ($bounds->getNorthEast()->getLng() < $this->southWest->getLng()) {

            return false;

        }

        return true;

    }

    /**
     * Return an array holding all 4 corners: northWest, northEast, southSeast, southWest
     * @return LatLng[]
     */
    public function getCorners()
    {

        $corners = array();

        $northEast = $this->northEast;
        $southWest = $this->southWest;
        $northWest = new LatLng($northEast->getLat(), $southWest->getLng());
        $southEast = new LatLng($southWest->getLat(), $northEast->getLng());

        $corners[] = $northWest;
        $corners[] = $northEast;
        $corners[] = $southEast;
        $corners[] = $southWest;

        return $corners;

    }

}
