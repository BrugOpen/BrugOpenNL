<?php
namespace BrugOpen\Geo\Model;

class Polygon extends Polyline
{

    /**
     * @param LatLng[]|float[][]|string
     */
    public function __construct($path)
    {

        parent::__construct($path);

        $parsedPath = $this->path;

        if (count($parsedPath) >= 3) {

            if (!$parsedPath[0]->equals($parsedPath[count($parsedPath) - 1])) {

                // add first point to end
                $parsedPath[] = $parsedPath[0];

            }

        }

        $this->path = $parsedPath;

    }

    /**
     *
     * @param LatLng $point
     * @return boolean
     */
    function isPointInPolygon($point)
    {

        $pointLon = $point->getLng();
        $pointLat = $point->getLat();

        // Transform string coordinates into arrays with x and y values
        $vertices = array();
        foreach ($this->path as $tmpPoint) {
            $vertices[] = array($tmpPoint->getLng(), $tmpPoint->getLat());
        }

        // Check if the point sits exactly on a vertex
        foreach($vertices as $vertex) {
            if (($pointLon == $vertex[0]) && ($pointLat == $vertex[1])) {
                return true;
            }
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;
        $vertices_count = count($vertices);

        for ($i=1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i-1];
            $vertex2 = $vertices[$i];
            if ($vertex1[0] == $vertex2[0] and $vertex1[0] == $pointLon and $pointLat > min($vertex1[1], $vertex2[1]) and $pointLat < max($vertex1[1], $vertex2[1])) {
                // Check if point is on an horizontal polygon boundary
                return true;
            }
            if ($pointLon > min($vertex1[0], $vertex2[0]) and $pointLon <= max($vertex1[0], $vertex2[0]) and $pointLat <= max($vertex1[1], $vertex2[1]) and $vertex1[0] != $vertex2[0]) {
                $xinters = ($pointLon - $vertex1[0]) * ($vertex2[1] - $vertex1[1]) / ($vertex2[0] - $vertex1[0]) + $vertex1[1];
                if ($xinters == $pointLat) {
                    // Check if point is on the polygon boundary (other than horizontal)
                    return true;
                }
                if ($vertex1[1] == $vertex2[1] || $pointLat <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is odd, then it's in the polygon.
        if ($intersections % 2 != 0) {
            return true;
        } else {
            return false;
        }

    }

}
