<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Geo\Model\LineSegment;
use PHPUnit\Framework\TestCase;

class LineTest extends TestCase
{

    public function testGetIntersectionPoint()
    {

        $segment1 = new LineSegment(new LatLng(52.142148, 4.490243), new LatLng(52.143346, 4.494084));
        $segment2 = new LineSegment(new LatLng(52.145084, 4.492207), new LatLng(52.140672, 4.493108));

        $line1 = $segment1->getLine();
        $line2 = $segment2->getLine();

        $intersectionPoint = $line1->getIntersectionPoint($line2);

        $this->assertNotNull($intersectionPoint);

        $this->assertEquals('52.1428997', number_format($intersectionPoint->getLat(), 7, '.', ''));
        $this->assertEquals('4.492653', number_format($intersectionPoint->getLng(), 6, '.', ''));
    }
}
