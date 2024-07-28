<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Geo\Model\Polygon;
use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\VesselJourney;
use BrugOpen\Tracking\Model\WaterwaySegment;
use PHPUnit\Framework\TestCase;

class JourneyReconstructorTest extends TestCase
{

    public function createWaterwaySegments()
    {

        // create test segments
        $segments = array();

        $polygon = new Polygon('52.144764,4.526434
52.144518,4.5257
52.143243,4.527091
52.142274,4.5287
52.141733,4.530394
52.142096,4.530724
52.142725,4.529379
52.143646,4.527909');

        $segment = new WaterwaySegment();
        $segment->setId(120);
        $segment->setPolygon($polygon);
        $segment->setRoutePoints(array(new LatLng('52.14303726529,4.5281670334902')));
        $segment->setConnectedSegmentIds(array(612));

        $segments[120] = $segment;

        $polygon = new Polygon('52.141459,4.541411
52.141283,4.543694
52.140693,4.546668
52.139739,4.548907
52.139091,4.548009
52.140313,4.544621
52.1406,4.541514
52.1405,4.534859
52.141128,4.532348
52.141568,4.531009
52.141899,4.531373
52.141632,4.533413
52.141342,4.536342');

        $segment = new WaterwaySegment();
        $segment->setId(121);
        $segment->setPolygon($polygon);
        $segment->setRoutePoints(array(new LatLng('52.140999815886,4.539881642208')));
        $segment->setConnectedSegmentIds(array(612));

        $segments[121] = $segment;

        $polygon = new Polygon('52.141568,4.531009
        52.141733,4.530394
        52.142096,4.530724
        52.141899,4.531373');

        $segment = new WaterwaySegment();
        $segment->setId(612);
        $segment->setPolygon($polygon);
        $segment->setRoutePoints(array(new LatLng('52.14182586445,4.5308755339642')));
        $segment->setConnectedSegmentIds(array(120, 121));

        $segments[612] = $segment;

        return $segments;
    }

    public function createJourneyReconstructor($waterwaySegments = null)
    {
        $journeyReconstructor = new JourneyReconstructor();
        if ($waterwaySegments == null) {
            $waterwaySegments = $this->createWaterwaySegments();
        }
        $waterwayService = new WaterwayService();
        $waterwayService->initalizeWaterwaySegments($waterwaySegments);

        $journeyReconstructor->initalizeWaterwaySegments($waterwaySegments);
        $journeyReconstructor->setWaterwayService($waterwayService);

        return $journeyReconstructor;
    }

    public function testReconstructJourneySegmentsAlreadyConnected()
    {

        $journeySegments = array();

        // point in segment 121
        $point1 = new LatLng(52.141041, 4.535386);

        $journeySegment1 = new JourneySegment();
        $journeySegment1->setSegmentId(121);
        $journeySegment1->setFirstLocation($point1);
        $journeySegment1->setFirstTimestamp(1722176898);
        $journeySegment1->setLastLocation($point1);
        $journeySegment1->setLastTimestamp(1722176898);
        $journeySegments[] = $journeySegment1;

        // point in segment 612
        $point2 = new LatLng(52.14182586445, 4.5308755339642);

        $journeySegment2 = new JourneySegment();
        $journeySegment2->setSegmentId(612);
        $journeySegment2->setFirstLocation($point2);
        $journeySegment2->setFirstTimestamp(1722176940);
        $journeySegment2->setLastLocation($point2);
        $journeySegment2->setLastTimestamp(1722176940);
        $journeySegments[] = $journeySegment2;

        $journey = new VesselJourney();
        $journey->setJourneySegments($journeySegments);

        $journeyReconstructor = $this->createJourneyReconstructor();
        $reconstructredJourneySegments = $journeyReconstructor->reconstructJourneySegments($journey);

        $this->assertCount(2, $reconstructredJourneySegments);

        $this->assertEquals(121, $reconstructredJourneySegments[0]->getSegmentId());
        $this->assertEquals(612, $reconstructredJourneySegments[1]->getSegmentId());
    }

    public function testReconstructJourneySegmentsOneSegmentSkipped()
    {

        $journeySegments = array();

        // point in segment 121
        $point1 = new LatLng(52.141041, 4.535386);

        $journeySegment1 = new JourneySegment();
        $journeySegment1->setSegmentId(121);
        $journeySegment1->setFirstLocation($point1);
        $journeySegment1->setFirstTimestamp(1722176898);
        $journeySegment1->setLastLocation($point1);
        $journeySegment1->setLastTimestamp(1722176898);
        $journeySegments[] = $journeySegment1;

        // point in segment 120
        $point2 = new LatLng(52.143465, 4.527502);

        $journeySegment2 = new JourneySegment();
        $journeySegment2->setSegmentId(120);
        $journeySegment2->setFirstLocation($point2);
        $journeySegment2->setFirstTimestamp(1722176940);
        $journeySegment2->setLastLocation($point2);
        $journeySegment2->setLastTimestamp(1722176940);
        $journeySegments[] = $journeySegment2;

        $journey = new VesselJourney();
        $journey->setJourneySegments($journeySegments);

        $journeyReconstructor = $this->createJourneyReconstructor();
        $reconstructredJourneySegments = $journeyReconstructor->reconstructJourneySegments($journey);

        $this->assertCount(3, $reconstructredJourneySegments);

        $this->assertEquals(121, $reconstructredJourneySegments[0]->getSegmentId());
        $this->assertEquals(612, $reconstructredJourneySegments[1]->getSegmentId());
        $this->assertEquals(120, $reconstructredJourneySegments[2]->getSegmentId());

        // assert route point in second segment
        $waterwaySegments = $this->createWaterwaySegments();
        $routepoints612 = $waterwaySegments[612]->getRoutePoints();
        $routepoint612 = $routepoints612[0];

        $this->assertEquals($routepoint612->toString(), $reconstructredJourneySegments[1]->getFirstLocation()->toString());
        $this->assertEquals($routepoint612->toString(), $reconstructredJourneySegments[1]->getLastLocation()->toString());
    }

    public function testReconstructJourneySegmentsOneNewSegmentInsertedOnExistingLocation()
    {

        $journeySegments = array();

        // first point in segment 121
        $point1 = new LatLng(52.141041, 4.535386);

        // last point in segment 121 (now in segment 612)
        $point2 = new LatLng(52.141742, 4.531132);

        $journeySegment1 = new JourneySegment();
        $journeySegment1->setSegmentId(121);
        $journeySegment1->setFirstLocation($point1);
        $journeySegment1->setFirstTimestamp(1722176898);
        $journeySegment1->setLastLocation($point2);
        $journeySegment1->setLastTimestamp(1722176940);
        $journeySegments[] = $journeySegment1;

        // point in segment 120
        $point3 = new LatLng(52.143465, 4.527502);

        $journeySegment2 = new JourneySegment();
        $journeySegment2->setSegmentId(120);
        $journeySegment2->setFirstLocation($point3);
        $journeySegment2->setFirstTimestamp(1722177040);
        $journeySegment2->setLastLocation($point3);
        $journeySegment2->setLastTimestamp(1722177700);
        $journeySegments[] = $journeySegment2;

        $journey = new VesselJourney();
        $journey->setJourneySegments($journeySegments);

        $journeyReconstructor = $this->createJourneyReconstructor();
        $reconstructredJourneySegments = $journeyReconstructor->reconstructJourneySegments($journey);

        $this->assertCount(3, $reconstructredJourneySegments);

        $this->assertEquals(121, $reconstructredJourneySegments[0]->getSegmentId());
        $this->assertEquals(612, $reconstructredJourneySegments[1]->getSegmentId());
        $this->assertEquals(120, $reconstructredJourneySegments[2]->getSegmentId());

        // assert route point in second segment
        $this->assertEquals($point1->toString(), $reconstructredJourneySegments[0]->getFirstLocation()->toString());
        $this->assertEquals($point1->toString(), $reconstructredJourneySegments[0]->getLastLocation()->toString());

        $this->assertEquals($point2->toString(), $reconstructredJourneySegments[1]->getFirstLocation()->toString());
        $this->assertEquals($point2->toString(), $reconstructredJourneySegments[1]->getLastLocation()->toString());

        $this->assertEquals($point3->toString(), $reconstructredJourneySegments[2]->getFirstLocation()->toString());
        $this->assertEquals($point3->toString(), $reconstructredJourneySegments[2]->getLastLocation()->toString());
    }
}
