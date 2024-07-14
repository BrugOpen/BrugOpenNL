<?php
namespace BrugOpen\Geo\Model;

class LineSegment
{

    /**
     * @var LatLng
     */
    private $point1;

    /**
     * @var LatLng
     */
    private $point2;

    /**
     * @param LatLng $point1
     * @param LatLng $point2
     */
    public function __construct($point1, $point2)
    {
        $this->point1 = $point1;
        $this->point2 = $point2;
    }

    /**
     * @param LatLng $point
     * @return boolean
     */
    public function isEndPoint($point)
    {

        if ($point->equals($this->point1)) {

            return true;

        }

        if ($point->equals($this->point2)) {

            return true;

        }

        return false;

    }

    /**
     * @return LatLng[]
     */
    public function getEndpoints()
    {
        return array($this->point1, $this->point2);
    }

    /**
     * Calculates shortest distance in meters between given point and any point on this segment
     * @param LatLng
     */
    public function getDistance($latlng)
    {

        $shortestDistance = null;

        $center = $this->getCenter();

        $distanceToPoint1 = $this->point1->getDistance($latlng);
        $distanceToPoint2 = $this->point2->getDistance($latlng);
        $distanceToCenter = $center->getDistance($latlng);

        if (($distanceToCenter < $distanceToPoint1) || ($distanceToCenter < $distanceToPoint2)) {



        } else {

            if ($distanceToPoint1 < $distanceToPoint2) {

                $shortestDistance = $distanceToPoint1;

            } else {

                $shortestDistance = $distanceToPoint2;

            }

        }

        return $shortestDistance;


        $a=1;
    $b=2;
    $c=3;
    $d=4;

    $m=($d-$b)/($c-$a);
    //echo $m."\n";

    $x=10;
    $y=20;
    //echo $y-($m*$x)-$b+($m*$a)."\n";
    $distance=abs($y-($m*$x)-$b+($m*$a))/sqrt(1+($m*$m));


    }

    /**
     * @param LatLng $point
     */
    public function getPerpendicularLineIntersection($point)
    {

        if ($this->point1->getLat() == $this->point2->getLat()) {

            if ($this->point1->getLng() == $this->point2->getLng()) {

                return $this->point1;

            } else {

                $intersectionPoint = new LatLng($this->point1->getLat(), $point->getLng());
                return $intersectionPoint;

            }

        }

        $dX = ($this->point2->getLng() - $this->point1->getLng()) / 2;
        $dY = ($this->point2->getLat() - $this->point1->getLat()) / 2;

        $m1 = $dX - $dY;

        if ($m1 == 0) {

            $m2 = 1;

        } else {

            $m2 = -1 / $m1;

        }


    }

    /**
     *
     */
    public function getPerpendicularLine($pointOnPerpendicularLine)
    {

        if ($this->point1->getLat() == $this->point2->getLat()) {

            if ($this->point1->getLng() == $this->point2->getLng()) {



            } else {

                $intersectionPoint = new LatLng($this->point1->getLat(), $pointOnPerpendicularLine->getLng());
                return $intersectionPoint;

            }

        }

        $dX = ($this->point2->getLng() - $this->point1->getLng()) / 2;
        $dY = ($this->point2->getLat() - $this->point1->getLat()) / 2;

        $m1 = $dX - $dY;

        if ($m1 == 0) {

            $m2 = 1;

        } else {

            $m2 = -1 / $m1;

        }


    }

    /**
     * @return LatLng
     */
    public function getCenter()
    {

        $lat = ($this->point1->getLat() + $this->point2->getLat()) / 2;
        $lng = ($this->point1->getLng() + $this->point2->getLng()) / 2;

        return new LatLng($lat, $lng);

    }

    /**
     * Get distance in meters between point1 and point2
     * @param LatLng $wayPoint
     * @return float
     */
    public function getLength()
    {
        return $this->point1->getDistance($this->point2);
    }

    /**
     * @return Line
     */
    public function getLine()
    {

        $dX = ($this->point2->getLng() - $this->point1->getLng());
        $dY = ($this->point2->getLat() - $this->point1->getLat());

        if ($dY == 0) {

            if ($dX == 0) {

                // both points are the same, not a line
                return null;

            } else {

                // horizontal line
                $a = 0;
                $b = $this->point1->getLat();

                return new Line($a, $b);

            }

        } elseif ($dX == 0) {

            // vertical line which crosses X-axis on (b,0)

            $a = null;
            $b = $this->point1->getLng();

            return new Line($a, $b);

        } else {

            $a = $dY / $dX;
            $b = $this->point1->getLat() + ((0 - $this->point1->getLng()) * $a);

            return new Line($a, $b);

        }

    }

}
