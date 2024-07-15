<?php

namespace BrugOpen\Tracking\Model;

class VesselDimensions
{

    /**
     * Dimension (meters) from AIS GPS antenna to the Bow of the vessel
     * @var int
     */
    private $a;

    /**
     * Dimension (meters) from AIS GPS antenna to the Stern of the vessel (Vessel Length = A + B)
     * @var int
     */
    private $b;

    /**
     * Dimension (meters) from AIS GPS antenna to the Port of the vessel
     * @var int
     */
    private $c;

    /**
     * Dimension (meters) from AIS GPS antenna to the Starboard of the vessel (Vessel Width = C + D)
     * @var int
     */
    private $d;

    /**
     *
     * @param int|string $a
     * @param int $b
     * @param int $c
     * @param int $d
     */
    public function __construct($a, $b = null, $c = null, $d = null)
    {

        if (($b !== null) && ($c !== null) && ($d !== null)) {

            $this->a = $a;
            $this->b = $b;
            $this->c = $c;
            $this->d = $d;

        } else if (is_string($a)) {

            $parts = explode(',', $a);

            if (count($parts) == 4) {

                $a = $parts[0];
                $b = $parts[1];
                $c = $parts[2];
                $d = $parts[3];

            }

        }

    }

    /**
     * @return int
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * @param int $a
     */
    public function setA($a)
    {
        $this->a = $a;
    }

    /**
     * @return int
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * @param int $b
     */
    public function setB($b)
    {
        $this->b = $b;
    }

    /**
     * @return int
     */
    public function getC()
    {
        return $this->c;
    }

    /**
     * @param int $c
     */
    public function setC($c)
    {
        $this->c = $c;
    }

    /**
     * @return int
     */
    public function getD()
    {
        return $this->d;
    }

    /**
     * @param int $d
     */
    public function setD($d)
    {
        $this->d = $d;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->c + $this->d;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->a + $this->b;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->a . ',' . $this->b . ',' . $this->c . ',' . $this->d;
    }

}
