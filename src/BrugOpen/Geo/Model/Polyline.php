<?php
namespace BrugOpen\Geo\Model;

class Polyline
{

    /**
     * @var LatLng[]
     */
    protected $path;

    /**
     * @var LineSegment[]
     */
    protected $lineSegments;

    /**
     * @param LatLng[]|float[][]|string
     */
    public function __construct($path)
    {

        $parsedPath = array();

        if (is_string($path)) {

            $coordLines = explode("\n", $path);

            foreach ($coordLines as $coordLine) {

                $lineParts = explode(',', $coordLine);

                if (sizeof($lineParts) == 2) {

                    $lat = (float)trim($lineParts[0]);
                    $lon = (float)trim($lineParts[1]);
                    $parsedPath[] = new LatLng($lat, $lon);

                }

            }

        } else if (is_array($path)) {

            foreach ($path as $pathItem) {

                if (is_object($pathItem)) {

                    if (is_a($pathItem, 'BrugOpen\\Geo\\Model\\LatLng')) {

                        $parsedPath[] = $pathItem;

                    }

                } else if (is_array($pathItem)) {

                    if (count($pathItem) == 2) {

                        $lat = $pathItem[0];
                        $lon = $pathItem[1];

                        $parsedPath[] = new LatLng($lat, $lon);

                    }

                }

            }

        }

        $this->path = $parsedPath;

    }

    /**
     * @return LatLng[]
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return LineSegment[]
     */
    public function getLineSegments()
    {

        if ($this->lineSegments == null) {

            $lineSegments = array();

            if (count($this->path) > 1) {

                for ($i = 0; $i < count($this->path) - 1; $i++) {

                    $point1 = $this->path[$i];
                    $point2 = $this->path[$i + 1];

                    $lineSegments[] = new LineSegment($point1, $point2);

                }

            }

            $this->lineSegments = $lineSegments;

        }

        return $this->lineSegments;

    }

    /**
     * Return line length in meters
     * @return float
     */
    public function getLineLength()
    {

        $lineLength = 0;

        $lineSegments = $this->getLineSegments();

        if ($lineSegments) {

            foreach ($lineSegments as $lineSegment) {

                $length = $lineSegment->getLength();

                if ($length !== 0) {

                    $lineLength += $length;

                }

            }

        }

        return $lineLength;

    }

}