<?php

namespace BrugOpen\Geo\Service;

use BrugOpen\Model\LatLng;

/**
 * @deprecated
 */
class GeoService
{

    /**
     * Calculates distance in meters between two points
     * @param LatLng $currentPoint
     * @param LatLng $wayPoint
     * @return number
     */
    public function getDistance($currentPoint, $wayPoint)
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

        $lat1 = $currentPoint->getLat();
        $lon1 = $currentPoint->getLng();
        $lat2 = $wayPoint->getLat();
        $lon2 = $wayPoint->getLng();

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
     * @param LatLng $currentPoint
     * @param LatLng $wayPoint
     * @return number
     */
    public function getBearing($currentPoint, $wayPoint)
    {

        $lat1 = $currentPoint->getLat();
        $lon1 = $currentPoint->getLng();
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

        // traveling from 52.143465,4.4955516666667 to 52.140663,4.4873033 requires a heading of 241 and a bit
        // traveling from 52.1402417,4.4863917 to 52.140663,4.4873033 requires a heading of 53 and a bit

    }

}
