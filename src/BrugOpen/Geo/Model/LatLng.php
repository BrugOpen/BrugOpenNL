<?php

namespace BrugOpen\Geo\Model;

class LatLng
{

    /**
     *
     * @var float
     */
    private $lat;

    /**
     *
     * @var float
     */
    private $lng;

    /**
     *
     * @param float|string $a
     * @param float|null $b
     */
    public function __construct($a, $b = null)
    {
        if (is_string($a) && is_null($b)) {

            $parts = explode(',', str_replace(' ', '', $a), 2);

            $this->lat = (float)$parts[0];
            $this->lng = (float)$parts[1];

        } else {

            $this->lat = $a;
            $this->lng = $b;

        }

    }

    /**
     *
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     *
     * @return float
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * @param LatLng $latLng
     * @return boolean
     */
    public function equals($latLng)
    {

        if ($this->lat == $latLng->getLat()) {

            if ($this->lng == $latLng->getLng()) {

                return true;

            }

        }

        return false;

    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->lat . ',' . $this->lng;
    }

    /**
     * Calculates distance in meters between this point and another pont
     * @param LatLng $otherPoint
     * @return float
     */
    public function getDistance($otherPoint)
    {

        /*
         var R = 6371000; // metres
         var φ1 = lat1.toRadians();
         var φ2 = lat2.toRadians();
         var Δφ = (lat2-lat1).toRadians();
         var Δλ = (lon2-lon1).toRadians();

         var a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
         Math.cos(φ1) * Math.cos(φ2) *
         Math.sin(Δλ/2) * Math.sin(Δλ/2);
         var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

         var d = R * c;
         */

        $lat1 = $this->getLat();
        $lon1 = $this->getLng();
        $lat2 = $otherPoint->getLat();
        $lon2 = $otherPoint->getLng();

        $R = 6371000; // earth radius in metres
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dPhi = deg2rad($lat2-$lat1);
        $dLambda = deg2rad($lon2-$lon1);

        $a = sin($dPhi/2) * sin($dPhi/2) +
        cos($phi1) * cos($phi2) *
        sin($dLambda/2) * sin($dLambda/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        $distance = $R * $c; // in meters

        return $distance;

    }

    /**
     *
     * @param LatLng $wayPoint
     * @return float
     */
    public function getBearing($wayPoint)
    {

        $lat1 = $this->getLat();
        $lon1 = $this->getLng();
        $lat2 = $wayPoint->getLat();
        $lon2 = $wayPoint->getLng();

        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dLambda = deg2rad($lon2-$lon1);

        // formula θ = atan2( sin Δλ ⋅ cos φ2 , cos φ1 ⋅ sin φ2 − sin φ1 ⋅ cos φ2 ⋅ cos Δλ )
        // $y =   sin Δλ ⋅ cos φ2
        // $x = cos φ1 ⋅ sin φ2 − sin φ1 ⋅ cos φ2 ⋅ cos Δλ

        $y = sin($dLambda) * cos($phi2);
        $x = cos($phi1) * sin($phi2) - sin($phi1) * cos($phi2) * cos($dLambda);

        $b = atan2($y, $x);

        $bearing = (rad2deg($b) + 360) % 360;

        return $bearing;

        //ATAN2(COS(lat1)*SIN(lat2)-SIN(lat1)*COS(lat2)*COS(lon2-lon1),
        //SIN(lon2-lon1)*COS(lat2))

        /*
         $phi1 = deg2rad($lat1);
         $phi2 = deg2rad($lat2);
         $dPhi = deg2rad($lat2-$lat1);
         $dLambda = deg2rad($lon2-$lon1);

         $y = Math.sin(λ2-λ1) * Math.cos(φ2);
         $x = Math.cos(φ1)*Math.sin(φ2) -
         Math.sin(φ1)*Math.cos(φ2)*Math.cos(λ2-λ1);
         var brng = Math.atan2(y, x).toDegrees();
         */

        // traveling from 52.143465,4.4955516666667 to 52.140663,4.4873033 requires a bearing of 241 and a bit
        // traveling from 52.1402417,4.4863917 to 52.140663,4.4873033 requires a bearing of 53 and a bit

    }

}
