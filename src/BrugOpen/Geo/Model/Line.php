<?php
namespace BrugOpen\Geo\Model;

class Line
{

    /**
     * @var float
     */
    private $a;

    /**
     * @var float
     */
    private $b;

    /**
     * @param float $a
     * @param float $b
     */
    public function __construct($a, $b)
    {

        $this->a = $a;
        $this->b = $b;

    }

    /**
     * @return float
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * @return float
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * @param LatLng $pointOnPerpendicularLine
     * @return Line
     */
    public function getPerpendicularLine($pointOnPerpendicularLine)
    {

        if ($this->a === null) {

            // this is a vertical line which crosses X axis on (b1,0)

            // perpendicular line is a horizontal line that crosses Y axis on (0,b2)

            $a2 = 0;
            $b2 = $pointOnPerpendicularLine->getLat();

            $perpendicularLine = new Line($a2, $b2);

        } elseif ($this->a === 0) {

            // this is a horizontal line which crosses Y axis on (0,b1)

            // perpendicular line is a vertical line that crosses X axis on (b2, 0)

            $a2 = null;
            $b2 = $pointOnPerpendicularLine->getLng();

            $perpendicularLine = new Line($a2, $b2);

        } else {

            $a1 = $this->a;

            if ($a1 == 0) {

                $a2 = 1;

            } else {

                $a2 = -1 / $a1;

            }

            $b2 = $pointOnPerpendicularLine->getLat() + ((0 - $pointOnPerpendicularLine->getLng()) * $a2);

            $perpendicularLine = new Line($a2, $b2);

        }

        return $perpendicularLine;

    }

    /**
     * @param Line $line2
     * @return LatLng
     */
    public function getIntersectionPoint($line2)
    {

        // this line:  y1 = a1 * x1 + b1
        // other line: y2 = a2 * x2 + b2

        // find x1,y1 where y1 = y2

        if (($this->a === null) && ($line2->getA() === null)) {

            // both lines are vertical
            return null;

        } else if (($this->a === 0) && ($line2->getA() === 0)) {

            // both lines are horizontal
            return null;

        } else if ($this->a === $line2->getA()) {

            // both lines are parallel
            return null;

        } else {

            // calculate at which x the lines intersect
            $x = ($line2->getB() - $this->b) / ($this->a - $line2->getA());
            $y = $this->a * $x + $this->b;

            return new LatLng($y, $x);

        }

    }

}
