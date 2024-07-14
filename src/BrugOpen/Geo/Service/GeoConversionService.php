<?php

namespace BrugOpen\Geo\Service;

use BrugOpen\Geo\Model\LatLng;

class GeoConversionService
{

    /**
     * Converts RD (Rijksdriehoek) coordinates to WGS84
     * This only works within the Netherlands area
     * @access public
     * @param int $rdx The RD X-coordinate in meters (value must be between 0 and 300.000)
     * @param int $rdy The RD Y-coordinate in meters (value must be between 290.000 and 630.000)
     * @return LatLng A LatLng instance if successful, false otherwise
     */
    public static function rd2wgs($rdx, $rdy)
    {
        if ($rdx<1000) $rdx*=1000;
        if ($rdy<1000) $rdy*=1000;
        if ($rdx<0 || $rdx>300000) {
            return false;
        } else {
            if ($rdy < 290000 || $rdy > 630000) {
                return false;
            } else{
                $wgslat = self::rd2wgs84_lat($rdx,$rdy);
                $wgslon = self::rd2wgs84_lon($rdx,$rdy);

                return new LatLng($wgslat, $wgslon);
            }
        }
    }


    public static function bessel_x($lat1, $lon1, $h1 = null)
    {
        $bessela = 6377397.155;
        $besselb = 6356078.963;
        $bessele2 = ($bessela * $bessela - $besselb * $besselb ) / ($bessela * $bessela);
        $besseln = $bessela / sqrt(1 - $bessele2 * pow(sin(self::rad($lat1)),2));
        if ($h1 === null) $h1 = 0;

        return ($besseln + $h1) * cos(self::rad($lat1)) * cos(self::rad($lon1));
    }

    public static function bessel_y($lat1, $lon1, $h1 = null)
    {
        $bessela = 6377397.155;
        $besselb = 6356078.963;
        $bessele2 = ($bessela * $bessela - $besselb * $besselb ) / ($bessela * $bessela);
        $besseln = $bessela / sqrt(1 - $bessele2 * pow(sin(self::rad($lat1)),2));
        if ($h1 === null) $h1 = 0;

        return ($besseln + $h1) * cos(self::rad($lat1)) * sin(self::rad($lon1));
    }


    public static function bessel_z($lat1, $lon1, $h1 = null)
    {
        $bessela = 6377397.155;
        $besselb = 6356078.963;
        $bessele2 = ($bessela * $bessela - $besselb * $besselb ) / ($bessela * $bessela);
        $besseln = $bessela / sqrt(1 - $bessele2 * pow(sin(self::rad($lat1)),2));
        if ($h1 === null) $h1 = 0;

        return ($besseln * (1 - $bessele2) + $h1) * sin(self::rad($lat1));
    }

    public static function bessel_lat($x1, $y1, $z1)
    {
        $bessela = 6377397.155;
        $besselb = 6356078.963;
        $bessele2 = ($bessela * $bessela - $besselb * $besselb) / ($bessela * $bessela);
        $besseleps2 = ($bessela * $bessela - $besselb * $besselb) / ($besselb * $besselb);

        $r1 = sqrt($x1 * $x1 + $y1 * $y1);
        $theta1 = atan(($z1 * $bessela) / ($r1 * $besselb));

        $tanlat = ($z1 + $besseleps2 * $besselb * pow(sin($theta1),3)) / ($r1 - $bessele2 * $bessela * pow(cos($theta1),3));
        return self::deg(atan($tanlat));
    }


    public static function bessel_lon($x1, $y1, $z1)
    {
        return self::deg(atan($y1 / $x1));
    }


    public static function bessel_h($x1, $y1, $z1)
    {
        $bessela = 6377397.155;
        $besselb = 6356078.963;
        $bessele2 = ($bessela * $bessela - $besselb * $besselb) / ($bessela * $bessela);
        $besseleps2 = ($bessela * $bessela - $besselb * $besselb) / ($besselb * $besselb);

        $r1 = sqrt($x1 * $x1 + $y1 * $y1);
        $theta1 = atan(($z1 * $bessela) / ($r1 * $besselb));

        $tanlat = ($z1 + $besseleps2 * $besselb * pow(sin($theta1),3)) / ($r1 - $bessele2 * $bessela * pow(cos($theta1),3));

        $coslat = 1 / sqrt(1 + $tanlat * $tanlat);
        $sinlat = $tanlat / sqrt(1 + $tanlat * $tanlat);

        $besseln = $bessela / sqrt(1 - $bessele2 * $sinlat * $sinlat);

        return $r1 / $coslat - $besseln;
    }


    public static function wgs84_x($lat1, $lon1, $h1 = null)
    {
        $wgs84a = 6378137;
        $wgs84b = 6356752.314;
        $wgs84e2 = ($wgs84a * $wgs84a - $wgs84b * $wgs84b) / ($wgs84a * $wgs84a);
        $wgs84n = $wgs84a / sqrt(1 - $wgs84e2 * pow(sin(self::rad($lat1)),2));
        if ($h1 === null) $h1 = 0;

        return ($wgs84n + $h1) * cos(self::rad($lat1)) * cos(self::rad($lon1));
    }


    public static function wgs84_y($lat1, $lon1, $h1 = null)
    {
        $wgs84a = 6378137;
        $wgs84b = 6356752.314;
        $wgs84e2 = ($wgs84a * $wgs84a - $wgs84b * $wgs84b) / ($wgs84a * $wgs84a);
        $wgs84n = $wgs84a / sqrt(1 - $wgs84e2 * pow(sin(self::rad($lat1)),2));
        if ($h1 === null) $h1 = 0;

        return ($wgs84n + $h1) * cos(self::rad($lat1)) * sin(self::rad($lon1));
    }

    public static function wgs84_z($lat1, $lon1, $h1 = null)
    {
        $wgs84a = 6378137;
        $wgs84b = 6356752.314;
        $wgs84e2 = ($wgs84a * $wgs84a - $wgs84b * $wgs84b) / ($wgs84a * $wgs84a);
        $wgs84n = $wgs84a / sqrt(1 - $wgs84e2 * pow(sin(self::rad($lat1)),2));
        if ($h1 === null) $h1 = 0;

        return ($wgs84n * (1 - $wgs84e2) + $h1) * sin(self::rad($lat1));
    }

    public static function wgs84_lat($x1, $y1, $z1)
    {
        $wgs84a = 6378137;
        $wgs84b = 6356752.314;
        $wgs84e2 = ($wgs84a * $wgs84a - $wgs84b * $wgs84b) / ($wgs84a * $wgs84a);
        $wgs84eps2 = ($wgs84a * $wgs84a - $wgs84b * $wgs84b) / ($wgs84b * $wgs84b);

        $r1 = sqrt($x1 * $x1 + $y1 * $y1);
        $theta1 = atan(($z1 * $wgs84a) / ($r1 * $wgs84b));

        $tanlat = ($z1 + $wgs84eps2 * $wgs84b * pow(sin($theta1) ,3)) / ($r1 - $wgs84e2 * $wgs84a * pow(cos($theta1),3));
        return self::deg(atan($tanlat));
    }


    public static function wgs84_lon($x1, $y1, $z1)
    {
        return self::deg(atan($y1 / $x1));
    }


    public static function wgs84_h($x1, $y1, $z1)
    {
        $wgs84a = 6378137;
        $wgs84b = 6356752.314;
        $wgs84e2 = ($wgs84a * $wgs84a - $wgs84b * $wgs84b) / ($wgs84a * $wgs84a);
        $wgs84eps2 = ($wgs84a * $wgs84a - $wgs84b * $wgs84b) / ($wgs84b * $wgs84b);

        $r1 = sqrt($x1 * $x1 + $y1 * $y1);
        $theta1 = atan(($z1 * $wgs84a) / ($r1 * $wgs84b));

        $tanlat = ($z1 + $wgs84eps2 * $wgs84b * pow(sin($theta1),3)) / ($r1 - $wgs84e2 * $wgs84a * pow(cos($theta1),3));

        $coslat = 1 / sqrt(1 + $tanlat * $tanlat);
        $sinlat = $tanlat / sqrt(1 + $tanlat * $tanlat);

        $wgs84n = $wgs84a / sqrt(1 - $wgs84e2 * $sinlat * $sinlat);

        return $r1 / $coslat - $wgs84n;
    }


    public static function bessel2wgs84_x($x1, $y1, $z1)
    {
        $tx = 565.04;
//         $ty = 49.91;
//         $tz = 465.84;
//         $ra = -0.0000019848;
        $rb = 0.0000017439;
        $rc = -0.0000090587;
        $sd = 0.0000040772;

        return $x1 + $tx + $sd * $x1 - $rc * $y1 + $rb * $z1;
    }

    public static function bessel2wgs84_y($x1, $y1, $z1)
    {
//         $tx = 565.04;
        $ty = 49.91;
//         $tz = 465.84;
        $ra = -0.0000019848;
//         $rb = 0.0000017439;
        $rc = -0.0000090587;
        $sd = 0.0000040772;

        return $y1 + $ty + $rc * $x1 + $sd * $y1 - $ra * $z1;
    }

    public static function bessel2wgs84_z($x1, $y1, $z1)
    {
//         $tx = 565.04;
//         $ty = 49.91;
        $tz = 465.84;
        $ra = -0.0000019848;
        $rb = 0.0000017439;
//         $rc = -0.0000090587;
        $sd = 0.0000040772;

        return $z1 + $tz - $rb * $x1 + $ra * $y1 + $sd * $z1;
    }


    public static function bessel2wgs84_lat($lat1, $lon1, $h1)
    {
        $x1 = self::bessel_x($lat1, $lon1, $h1);
        $y1 = self::bessel_y($lat1, $lon1, $h1);
        $z1 = self::bessel_z($lat1, $lon1, $h1);

        $x2 = self::bessel2wgs84_x($x1, $y1, $z1);
        $y2 = self::bessel2wgs84_y($x1, $y1, $z1);
        $z2 = self::bessel2wgs84_z($x1, $y1, $z1);

        return self::wgs84_lat($x2, $y2, $z2);
    }

    public static function bessel2wgs84_lon($lat1, $lon1, $h1)
    {
        $x1 = self::bessel_x($lat1, $lon1, $h1);
        $y1 = self::bessel_y($lat1, $lon1, $h1);
        $z1 = self::bessel_z($lat1, $lon1, $h1);

        $x2 = self::bessel2wgs84_x($x1, $y1, $z1);
        $y2 = self::bessel2wgs84_y($x1, $y1, $z1);
        $z2 = self::bessel2wgs84_z($x1, $y1, $z1);

        return self::wgs84_lon($x2, $y2, $z2);
    }

    public static function bessel2wgs84_h($lat1, $lon1, $h1)
    {
        $x1 = self::bessel_x($lat1, $lon1, $h1);
        $y1 = self::bessel_y($lat1, $lon1, $h1);
        $z1 = self::bessel_z($lat1, $lon1, $h1);

        $x2 = self::bessel2wgs84_x($x1, $y1, $z1);
        $y2 = self::bessel2wgs84_y($x1, $y1, $z1);
        $z2 = self::bessel2wgs84_z($x1, $y1, $z1);

        return self::wgs84_h($x2, $y2, $z2);
    }


    public static function wgs842bessel_x($x1, $y1, $z1)
    {
        $tx = -565.04;
//         $ty = -49.91;
//         $tz = -465.84;
//         $ra = 0.0000019848;
        $rb = -0.0000017439;
        $rc = 0.0000090587;
        $sd = -0.0000040772;

        return $x1 + $tx + $sd * $x1 - $rc * $y1 + $rb * $z1;
    }

    public static function wgs842bessel_y($x1, $y1, $z1)
    {
//         $tx = -565.04;
        $ty = -49.91;
//         $tz = -465.84;
        $ra = 0.0000019848;
//         $rb = -0.0000017439;
        $rc = 0.0000090587;
        $sd = -0.0000040772;

        return $y1 + $ty + $rc * $x1 + $sd * $y1 - $ra * $z1;
    }

    public static function wgs842bessel_z($x1, $y1, $z1)
    {
//         $tx = -565.04;
//         $ty = -49.91;
        $tz = -465.84;
        $ra = 0.0000019848;
        $rb = -0.0000017439;
//         $rc = 0.0000090587;
        $sd = -0.0000040772;

        return $z1 + $tz - $rb * $x1 + $ra * $y1 + $sd * $z1;
    }

    public static function wgs842bessel_lat($lat1, $lon1, $h1)
    {
        $x1 = self::wgs84_x($lat1, $lon1, $h1);
        $y1 = self::wgs84_y($lat1, $lon1, $h1);
        $z1 = self::wgs84_z($lat1, $lon1, $h1);

        $x2 = self::wgs842bessel_x($x1, $y1, $z1);
        $y2 = self::wgs842bessel_y($x1, $y1, $z1);
        $z2 = self::wgs842bessel_z($x1, $y1, $z1);

        return self::bessel_lat($x2, $y2, $z2);
    }

    public static function wgs842bessel_lon($lat1, $lon1, $h1)
    {
        $x1 = self::wgs84_x($lat1, $lon1, $h1);
        $y1 = self::wgs84_y($lat1, $lon1, $h1);
        $z1 = self::wgs84_z($lat1, $lon1, $h1);

        $x2 = self::wgs842bessel_x($x1, $y1, $z1);
        $y2 = self::wgs842bessel_y($x1, $y1, $z1);
        $z2 = self::wgs842bessel_z($x1, $y1, $z1);

        return self::bessel_lon($x2, $y2, $z2);
    }

    public static function wgs842bessel_h($lat1, $lon1, $h1)
    {
        $x1 = self::wgs84_x($lat1, $lon1, $h1);
        $y1 = self::wgs84_y($lat1, $lon1, $h1);
        $z1 = self::wgs84_z($lat1, $lon1, $h1);

        $x2 = self::wgs842bessel_x($x1, $y1, $z1);
        $y2 = self::wgs842bessel_y($x1, $y1, $z1);
        $z2 = self::wgs842bessel_z($x1, $y1, $z1);

        return self::bessel_h($x2, $y2, $z2);
    }

    public static function bessel2rd_x($lat1, $lon1)
    {
        $dlat = ($lat1 * 3600 - 187762.178) * 0.0001;
        $dlon = ($lon1 * 3600 - 19395.5) * 0.0001;

        $c01 = 190066.98903;
        $c11 = -11830.85831;
        $c21 = -114.19754;
        $c03 = -32.3836;
        $c31 = -2.34078;
        $c13 = -0.60639;
        $c23 = 0.15774;
        $c41 = -0.04158;
        $c05 = -0.00661;

        $dx = $c01 * $dlon + $c11 * $dlat * $dlon + $c21 * $dlat * $dlat * $dlon;
        $dx = $dx + $c03 * pow($dlon,3) + $c31 * pow($dlat,3) * $dlon + $c13 * $dlat * pow($dlon,3);
        $dx = $dx + $c23 * pow($dlat,2) * pow($dlon,3) + $c41 * pow($dlat,4) * $dlon + $c05 * pow($dlon,5);

        return 155000 + $dx;
    }

    public static function bessel2rd_y($lat1, $lon1)
    {
        $dlat = ($lat1 * 3600 - 187762.178) * 0.0001;
        $dlon = ($lon1 * 3600 - 19395.5) * 0.0001;

        $d10 = 309020.3181;
        $d02 = 3638.36193;
        $d12 = -157.95222;
        $d20 = 72.97141;
        $d30 = 59.79734;
        $d22 = -6.43481;
        $d04 = 0.09351;
        $d32 = -0.07379;
        $d14 = -0.05419;
        $d40 = -0.03444;

        $dy = $d10 * $dlat + $d02 * pow($dlon,2) + $d12 * $dlat * pow($dlon,2);
        $dy = $dy + $d20 * pow($dlat,2) + $d30 * pow($dlat,3) + $d22 * pow($dlat,2) * pow($dlon,2);
        $dy = $dy + $d04 * pow($dlon,4) + $d32 * pow($dlat,3) * pow($dlon,2) + $d14 * $dlat * pow($dlon,4);
        $dy = $dy + $d40 * pow($dlat,4);

        return 463000 + $dy;
    }

    public static function rd2bessel_lat($x1, $y1)
    {

        $dx = ($x1 - 155000) * 0.00001;
        $dy = ($y1 - 463000) * 0.00001;

        $a01 = 3236.0331637;
        $a20 = -32.5915821;
        $a02 = -0.2472814;
        $a21 = -0.8501341;
        $a03 = -0.0655238;
        $a22 = -0.0171137;
        $a40 = 0.0052771;
        $a23 = -0.0003859;
        $a41 = 0.0003314;
        $a04 = 0.0000371;
        $a42 = 0.0000143;
        $a24 = -0.000009;

        $dlat = $a01 * $dy + $a20 * pow($dx,2) + $a02 * pow($dy,2);
        $dlat = $dlat + $a21 * pow($dx,2) * $dy + $a03 * pow($dy,3) + $a22 * pow($dx,2)* pow($dy,2);
        $dlat = $dlat + $a40 * pow($dx,4) + $a23 * pow($dx,2) * pow($dy,3) + $a41 * pow($dx,4) * $dy;
        $dlat = $dlat + $a04 * pow($dy,4) + $a42 * pow($dx,4) * pow($dy,2) + $a24 * pow($dx,2) * pow($dy,4);

        return (187762.178 + $dlat) / 3600;
    }

    public static function rd2bessel_lon($x1, $y1)
    {
        $dx = ($x1 - 155000) * 0.00001;
        $dy = ($y1 - 463000) * 0.00001;

        $b10 = 5261.3028966;
        $b11 = 105.9780241;
        $b12 = 2.4576469;
        $b30 = -0.8192156;
        $b31 = -0.0560092;
        $b13 = 0.0560089;
        $b32 = -0.0025614;
        $b14 = 0.001277;
        $b50 = 0.0002574;
        $b33 = -0.0000973;
        $b51 = 0.0000293;
        $b15 = 0.0000291;

        $dlon = $b10 * $dx + $b11 * $dx * $dy + $b12 * $dx * pow($dy,2);
        $dlon = $dlon + $b30 * pow($dx,3) + $b31 * pow($dx,3) * $dy + $b13 * $dx * pow($dy,3);
        $dlon = $dlon + $b32 * pow($dx,3) * pow($dy,2) + $b14 * $dx * pow($dy,4) + $b50 * pow($dx,5);
        $dlon = $dlon + $b33 * pow($dx,3) * pow($dy,3) + $b51 * pow($dx,5) * $dy + $b15 * $dx * pow($dy,5);

        return (19395.5 + $dlon) / 3600;
    }


    public static function wgs842rd_x($lat1, $lon1)
    {
        $lat2 = self::wgs842bessel_lat($lat1, $lon1, 0);
        $lon2 = self::wgs842bessel_lon($lat1, $lon1, 0);

        return self::bessel2rd_x($lat2, $lon2);
    }

    public static function wgs842rd_y($lat1, $lon1)
    {
        $lat2 = self::wgs842bessel_lat($lat1, $lon1, 0);
        $lon2 = self::wgs842bessel_lon($lat1, $lon1, 0);

        return self::bessel2rd_y($lat2, $lon2);
    }

    public static function rd2wgs84_lat($x1, $y1)
    {
        $lat1 = self::rd2bessel_lat($x1, $y1);
        $lon1 = self::rd2bessel_lon($x1, $y1);

        return self::bessel2wgs84_lat($lat1, $lon1, 0);
    }

    public static function rd2wgs84_lon($x1, $y1)
    {
        $lat1 = self::rd2bessel_lat($x1, $y1);
        $lon1 = self::rd2bessel_lon($x1, $y1);

        return self::bessel2wgs84_lon($lat1, $lon1, 0);
    }


    public static function rad($x)
    {
        return ($x * pi()) / 180;
    }

    public static function deg($x)
    {
        return ($x / pi()) * 180;
    }

}
