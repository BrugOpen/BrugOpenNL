<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Geo\Model\Polygon;
use BrugOpen\Geo\Model\Polyline;
use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\WaterwaySegment;
use PHPUnit\Framework\TestCase;

class RouteCalculatorTest extends TestCase
{

    public function createWaterwaySegments()
    {

        $segments = array();

        $segments[] = '{"id":135,"polygon":["52.125615,4.663272","52.123328,4.664409","52.121673,4.666823","52.120943,4.666069","52.121636,4.664543","52.123154,4.663014","52.125552,4.662344","52.125615,4.663272"],"connectedSegmentIds":[136],"routePoints":["52.122934903744,4.664026636293"]}';
        $segments[] = '{"id":136,"polygon":["52.121673,4.666823","52.120943,4.666069","52.1204,4.666903","52.120087,4.667979","52.118962,4.673355","52.119743,4.673918","52.120419,4.670276","52.121673,4.666823"],"connectedSegmentIds":[135,137],"routePoints":["52.120090196981,4.6699759661283"]}';
        $segments[] = '{"id":137,"polygon":["52.118962,4.673355","52.119743,4.673918","52.119325,4.675297","52.118491,4.6749","52.118962,4.673355"],"connectedSegmentIds":[136,138,464,468],"routePoints":["52.119170622015,4.674234047728"]}';
        $segments[] = '{"id":138,"polygon":["52.118962,4.673355","52.117622,4.673124","52.117375,4.674439","52.118491,4.6749","52.118962,4.673355"],"connectedSegmentIds":[136,137,139],"routePoints":["52.11834111086,4.6739486600072"]}';
        $segments[] = '{"id":139,"polygon":["52.114706,4.671681","52.117622,4.673124","52.117375,4.674439","52.114413,4.673028","52.114706,4.671681"],"connectedSegmentIds":[138,140],"routePoints":["52.115895753613,4.6730057756653"]}';
        $segments[] = '{"id":140,"polygon":["52.114706,4.671681","52.113326,4.671043","52.113212,4.672014","52.114413,4.673028","52.114706,4.671681"],"connectedSegmentIds":[139,141],"routePoints":["52.113948629478,4.6719710250192"]}';
        $segments[] = '{"id":141,"polygon":["52.112123,4.670447","52.113326,4.671043","52.113212,4.672014","52.112147,4.671515","52.112123,4.670447"],"connectedSegmentIds":[140,142],"routePoints":["52.112521920188,4.6711722118259"]}';
        $segments[] = '{"id":142,"polygon":["52.112123,4.670447","52.107357,4.6676","52.107064,4.669311","52.112147,4.671515","52.112123,4.670447"],"connectedSegmentIds":[141,143],"routePoints":["52.10966514927,4.669733070746"]}';
        $segments[] = '{"id":143,"polygon":["52.099655,4.6632","52.107357,4.6676","52.107064,4.669311","52.099442,4.664692","52.099655,4.6632"],"connectedSegmentIds":[142,144],"routePoints":["52.103235806423,4.6661108833262"]}';
        $segments[] = '{"id":144,"polygon":["52.099655,4.6632","52.0879,4.661785","52.087937,4.662917","52.099442,4.664692","52.099655,4.6632"],"connectedSegmentIds":[143,145],"routePoints":["52.093759513578,4.6631520138642"]}';
        $segments[] = '{"id":145,"polygon":["52.0879,4.661785","52.087937,4.662917","52.083806,4.662825","52.083862,4.661275","52.085525,4.661553","52.085815,4.659739","52.086185,4.659669","52.086369,4.661616","52.0879,4.661785"],"connectedSegmentIds":[144,146],"routePoints":["52.085943994273,4.6612812259733"]}';
        $segments[] = '{"id":146,"polygon":["52.083806,4.662825","52.083862,4.661275","52.079208,4.660523","52.079121,4.661846","52.083806,4.662825"],"connectedSegmentIds":[145,147],"routePoints":["52.081616267804,4.6616340586648"]}';
        $segments[] = '{"id":147,"polygon":["52.074232,4.660808","52.074288,4.659623","52.079208,4.660523","52.079121,4.661846","52.074232,4.660808"],"connectedSegmentIds":[146,148],"routePoints":["52.076712486973,4.6606988005818"]}';
        $segments[] = '{"id":148,"polygon":["52.074232,4.660808","52.074288,4.659623","52.072435,4.659601","52.070319,4.6591","52.070153,4.660473","52.072403,4.661027","52.074232,4.660808"],"connectedSegmentIds":[147,149],"routePoints":["52.072240901805,4.6602702508675"]}';
        $segments[] = '{"id":149,"polygon":["52.070319,4.6591","52.070153,4.660473","52.066257,4.659482","52.064001,4.659719","52.063988,4.658648","52.066362,4.657857","52.070319,4.6591"],"connectedSegmentIds":[148],"routePoints":["52.067153658096,4.6589075613877"]}';
        $segments[] = '{"id":464,"polygon":["52.1182,4.675672","52.1191,4.676128","52.119325,4.675297","52.118491,4.6749","52.1182,4.675672"],"connectedSegmentIds":[137,465],"routePoints":["52.118715156822,4.67546957945"]}';
        $segments[] = '{"id":465,"polygon":["52.1182,4.675672","52.1191,4.676128","52.118495,4.6807","52.118322,4.6852","52.117458,4.685272","52.117621,4.680629","52.1182,4.675672"],"connectedSegmentIds":[464,466],"routePoints":["52.11808422725,4.6804552714813"]}';
        $segments[] = '{"id":466,"polygon":["52.118376,4.688794","52.118322,4.6852","52.117458,4.685272","52.1172,4.689556","52.118262,4.6901","52.118376,4.688794"],"connectedSegmentIds":[465],"routePoints":["52.117840116601,4.6875330254877"]}';
        $segments[] = '{"id":468,"polygon":["52.120866,4.675308","52.119743,4.673918","52.119325,4.675297","52.120494,4.676295","52.120866,4.675308"],"connectedSegmentIds":[137,469],"routePoints":["52.12007838393,4.6751890028271"]}';
        $segments[] = '{"id":469,"polygon":["52.120866,4.675308","52.124874,4.6796","52.124608,4.680447","52.120494,4.676295","52.120866,4.675308"],"connectedSegmentIds":[468],"routePoints":["52.122616079165,4.6778197645763"]}';

        $waterwaySegments = array();

        foreach ($segments as $segmentJson) {

            $segmentData = json_decode($segmentJson, true);

            $segmentId = $segmentData['id'];

            $segment = new WaterwaySegment();
            $segment->setId($segmentId);

            $polygonPoints = array();

            foreach ($segmentData['polygon'] as $point) {

                $polygonPoints[] = new LatLng($point);
            }

            $segment->setPolygon(new Polygon($polygonPoints));

            $routePoints = array();

            foreach ($segmentData['routePoints'] as $point) {

                $routePoints[] = new LatLng($point);
            }

            $segment->setRoutePoints($routePoints);

            $segment->setConnectedSegmentIds($segmentData['connectedSegmentIds']);

            $waterwaySegments[$segmentId] = $segment;
        }

        return $waterwaySegments;
    }

    public function testCalculateRouteConnectingSegments()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        // new journey segment somewhere in 137:
        $journeySegment = new JourneySegment();
        $journeySegment->setSegmentId(137);
        $journeySegment->setLastLocation(new LatLng(52.118955, 4.674364));

        // previous journey segments were 135 and 136
        $previousJourneySegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousJourneySegments[] = $previousSegment;

        $calculatedRoute = $routeCalculator->calculateRoute($journeySegment, $previousJourneySegments);

        $this->assertNotEmpty($calculatedRoute);
        $this->assertCount(1, $calculatedRoute);
        $this->assertEquals(137, $calculatedRoute[0]->getSegmentId());
    }

    public function testCalculateRouteSkippedOneSegment()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        // previous journey segments were 135 and 136
        $previousSegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousSegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousSegment->setLastLocation(new LatLng(52.119647, 4.671767));

        $previousSegments[] = $previousSegment;

        // new journey segment somewhere in 138:
        $journeySegment = new JourneySegment();
        $journeySegment->setSegmentId(138);
        $journeySegment->setFirstLocation(new LatLng(52.117754, 4.673811));

        $calculatedRoute = $routeCalculator->calculateRoute($journeySegment, $previousSegments);

        $this->assertNotEmpty($calculatedRoute);
        $this->assertCount(2, $calculatedRoute);
        $this->assertEquals(137, $calculatedRoute[0]->getSegmentId());
        $this->assertEquals(138, $calculatedRoute[1]->getSegmentId());

        // assert inserted segment has location
        $this->assertNotEmpty($calculatedRoute[0]->getFirstLocation());
        $this->assertNotEmpty($calculatedRoute[0]->getLastLocation());

        // first and last location must be equal
        $this->assertEquals($calculatedRoute[0]->getFirstLocation(), $calculatedRoute[0]->getLastLocation());

        // no time must be set
        $this->assertEmpty($calculatedRoute[0]->getFirstTimestamp());
        $this->assertEmpty($calculatedRoute[0]->getLastTimestamp());
    }

    public function testCalculateRouteSkippedOneSegmentWithTimestamps()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        // previous journey segments were 135 and 136
        /**
         * @var JourneySegment[]
         */
        $previousSegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousSegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousSegment->setLastLocation(new LatLng(52.119647, 4.671767));
        $previousSegment->setLastTimestamp(1692181800); // 12:30:00

        $previousSegments[] = $previousSegment;

        // moving at around 4.2 m/s (15.1km/h) => 80 seconds for 337 meter

        // new journey segment somewhere in 138:
        $journeySegment = new JourneySegment();
        $journeySegment->setSegmentId(138);
        $journeySegment->setFirstLocation(new LatLng(52.117754, 4.673811));
        $journeySegment->setFirstTimestamp(1692181880); // 12:31:20

        $calculatedRoute = $routeCalculator->calculateRoute($journeySegment, $previousSegments);

        $this->assertNotEmpty($calculatedRoute);
        $this->assertCount(2, $calculatedRoute);
        $this->assertEquals(137, $calculatedRoute[0]->getSegmentId());
        $this->assertEquals(138, $calculatedRoute[1]->getSegmentId());

        // assert inserted segment has location
        $this->assertNotEmpty($calculatedRoute[0]->getFirstLocation());
        $this->assertNotEmpty($calculatedRoute[0]->getLastLocation());

        // first and last location must be equal
        $this->assertEquals($calculatedRoute[0]->getFirstLocation(), $calculatedRoute[0]->getLastLocation());

        // time must be set
        $this->assertNotEmpty($calculatedRoute[0]->getFirstTimestamp());
        $this->assertNotEmpty($calculatedRoute[0]->getLastTimestamp());

        // create polyline from assumed route
        $path = array();
        $path[] = $previousSegments[1]->getLastLocation();
        $path[] = $calculatedRoute[0]->getFirstLocation();
        $path[] = $calculatedRoute[1]->getFirstLocation();

        $polyLine = new Polyline($path);

        $lineLength = $polyLine->getLineLength();

        $this->assertEquals('336.72', number_format($lineLength, 2, '.', ''));

        $lineSegments = $polyLine->getLineSegments();

        $this->assertCount(2, $lineSegments);

        // section 136 -> 137 is 176 meter
        $this->assertEquals('176.57', number_format($lineSegments[0]->getLength(), 2, '.', ''));

        // section 137 -> 138 is 160 meter
        $this->assertEquals('160.15', number_format($lineSegments[1]->getLength(), 2, '.', ''));

        // entering and leaving middle section after (176.57 / 336.72) * 80 seconds = after 42 seconds

        $this->assertEquals(1692181842, $calculatedRoute[0]->getFirstTimestamp());
        $this->assertEquals(1692181842, $calculatedRoute[0]->getLastTimestamp());
    }

    public function testCalculateRouteSkippedTwoSegments()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        // new journey segment somewhere in 139:
        $journeySegment = new JourneySegment();
        $journeySegment->setSegmentId(139);

        // previous journey segments were 135 and 136
        $previousSegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousSegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousSegments[] = $previousSegment;

        $calculatedRoute = $routeCalculator->calculateRoute($journeySegment, $previousSegments);

        $this->assertNotEmpty($calculatedRoute);
        $this->assertCount(3, $calculatedRoute);
        $this->assertEquals(137, $calculatedRoute[0]->getSegmentId());
        $this->assertEquals(138, $calculatedRoute[1]->getSegmentId());
        $this->assertEquals(139, $calculatedRoute[2]->getSegmentId());

        // assert inserted segments have location
        $this->assertNotEmpty($calculatedRoute[0]->getFirstLocation());
        $this->assertNotEmpty($calculatedRoute[0]->getLastLocation());

        $this->assertNotEmpty($calculatedRoute[1]->getFirstLocation());
        $this->assertNotEmpty($calculatedRoute[1]->getLastLocation());

        // first and last location must be equal
        $this->assertEquals($calculatedRoute[0]->getFirstLocation(), $calculatedRoute[0]->getLastLocation());
        $this->assertEquals($calculatedRoute[1]->getFirstLocation(), $calculatedRoute[1]->getLastLocation());
    }

    public function testCalculateRouteSkippedTwoSegmentsWithTimestamps()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        // previous journey segments were 135 and 136
        $previousSegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousSegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousSegment->setLastLocation(new LatLng(52.119647, 4.671767));
        $previousSegment->setLastTimestamp(1692181800); // 12:30:00
        $previousSegments[] = $previousSegment;

        // moving at around 4.2 m/s (15.1km/h) => 150 seconds for 630 meter

        // new journey segment somewhere in 139:
        $journeySegment = new JourneySegment();
        $journeySegment->setSegmentId(139);
        $journeySegment->setFirstLocation(new LatLng(52.1152, 4.672738));
        $journeySegment->setFirstTimestamp(1692181950); // 12:32:30

        $calculatedRoute = $routeCalculator->calculateRoute($journeySegment, $previousSegments);

        $this->assertNotEmpty($calculatedRoute);
        $this->assertCount(3, $calculatedRoute);
        $this->assertEquals(137, $calculatedRoute[0]->getSegmentId());
        $this->assertEquals(138, $calculatedRoute[1]->getSegmentId());
        $this->assertEquals(139, $calculatedRoute[2]->getSegmentId());

        // assert inserted segments have location
        $this->assertNotEmpty($calculatedRoute[0]->getFirstLocation());
        $this->assertNotEmpty($calculatedRoute[0]->getLastLocation());

        $this->assertNotEmpty($calculatedRoute[1]->getFirstLocation());
        $this->assertNotEmpty($calculatedRoute[1]->getLastLocation());

        // first and last location must be equal
        $this->assertEquals($calculatedRoute[0]->getFirstLocation(), $calculatedRoute[0]->getLastLocation());
        $this->assertEquals($calculatedRoute[1]->getFirstLocation(), $calculatedRoute[1]->getLastLocation());

        // time must be set
        // $this->assertNotEmpty($calculatedRoute[0]->getFirstTimestamp());
        // $this->assertNotEmpty($calculatedRoute[0]->getLastTimestamp());

        // create polyline from assumed route
        $path = array();
        $path[] = $previousSegments[1]->getLastLocation();
        $path[] = $calculatedRoute[0]->getFirstLocation();
        $path[] = $calculatedRoute[1]->getFirstLocation();
        $path[] = $calculatedRoute[2]->getFirstLocation();

        $polyLine = new Polyline($path);

        $lineLength = $polyLine->getLineLength();

        $this->assertEquals('629.77', number_format($lineLength, 2, '.', ''));

        $lineSegments = $polyLine->getLineSegments();

        $this->assertCount(3, $lineSegments);

        // section 136 -> 137 is 176.57 meter
        $this->assertEquals('176.57', number_format($lineSegments[0]->getLength(), 2, '.', ''));

        // section 137 -> 138 is 160 meter
        $this->assertEquals('94.27', number_format($lineSegments[1]->getLength(), 2, '.', ''));

        // section 138 -> 139 is 999 meter
        $this->assertEquals('358.92', number_format($lineSegments[2]->getLength(), 2, '.', ''));

        // entering and leaving first inserted section after (176.57 / 629.77) * 150 seconds = after 42 seconds

        $this->assertEquals(1692181842, $calculatedRoute[0]->getFirstTimestamp());
        $this->assertEquals(1692181842, $calculatedRoute[0]->getLastTimestamp());

        // entering and leaving first inserted section after ((176.57 + 94.27) / 629.77) * 150 seconds = after 65 seconds

        $this->assertEquals(1692181865, $calculatedRoute[1]->getFirstTimestamp());
        $this->assertEquals(1692181865, $calculatedRoute[1]->getLastTimestamp());
    }

    public function testCalculateRouteSkippedMultipleSegmentsWithTimestamps()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        // previous journey segments were 135 and 136
        $previousSegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousSegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousSegment->setLastLocation(new LatLng(52.119647, 4.671767));
        $previousSegment->setLastTimestamp(1692181800); // 12:30:00
        $previousSegments[] = $previousSegment;

        // moving at around 4.2 m/s (15.1km/h) => 1423 seconds for 5975 meter

        // new journey segment somewhere in 149:
        $journeySegment = new JourneySegment();
        $journeySegment->setSegmentId(149);
        $journeySegment->setFirstLocation(new LatLng(52.068156, 4.659187));
        $journeySegment->setFirstTimestamp(1692183223); // 12:53:43

        $calculatedRoute = $routeCalculator->calculateRoute($journeySegment, $previousSegments);

        $this->assertNotEmpty($calculatedRoute);
        $this->assertCount(13, $calculatedRoute);
        $this->assertEquals(137, $calculatedRoute[0]->getSegmentId());
        $this->assertEquals(138, $calculatedRoute[1]->getSegmentId());
        $this->assertEquals(139, $calculatedRoute[2]->getSegmentId());
        $this->assertEquals(140, $calculatedRoute[3]->getSegmentId());
        $this->assertEquals(141, $calculatedRoute[4]->getSegmentId());
        $this->assertEquals(142, $calculatedRoute[5]->getSegmentId());
        $this->assertEquals(143, $calculatedRoute[6]->getSegmentId());
        $this->assertEquals(144, $calculatedRoute[7]->getSegmentId());
        $this->assertEquals(145, $calculatedRoute[8]->getSegmentId());
        $this->assertEquals(146, $calculatedRoute[9]->getSegmentId());
        $this->assertEquals(147, $calculatedRoute[10]->getSegmentId());
        $this->assertEquals(148, $calculatedRoute[11]->getSegmentId());
        $this->assertEquals(149, $calculatedRoute[12]->getSegmentId());

        // assert inserted segments have location
        $this->assertNotEmpty($calculatedRoute[0]->getFirstLocation());
        $this->assertNotEmpty($calculatedRoute[0]->getLastLocation());

        $this->assertNotEmpty($calculatedRoute[1]->getFirstLocation());
        $this->assertNotEmpty($calculatedRoute[1]->getLastLocation());

        // first and last location must be equal
        $this->assertEquals($calculatedRoute[0]->getFirstLocation(), $calculatedRoute[0]->getLastLocation());
        $this->assertEquals($calculatedRoute[1]->getFirstLocation(), $calculatedRoute[1]->getLastLocation());

        // time must be set in generated segments
        $this->assertNotEmpty($calculatedRoute[0]->getFirstTimestamp());
        $this->assertNotEmpty($calculatedRoute[0]->getLastTimestamp());

        $this->assertNotEmpty($calculatedRoute[1]->getFirstTimestamp());
        $this->assertNotEmpty($calculatedRoute[1]->getLastTimestamp());

        $this->assertNotEmpty($calculatedRoute[11]->getFirstTimestamp());
        $this->assertNotEmpty($calculatedRoute[11]->getLastTimestamp());

        // create polyline from assumed route
        $path = array();
        $path[] = $previousSegments[1]->getLastLocation();

        foreach ($calculatedRoute as $routeSegment) {

            $path[] = $routeSegment->getFirstLocation();
        }

        $polyLine = new Polyline($path);

        $lineLength = $polyLine->getLineLength();

        $this->assertEquals('5975.24', number_format($lineLength, 2, '.', ''));

        $lineSegments = $polyLine->getLineSegments();

        $this->assertCount(13, $lineSegments);

        // section 136 -> 137 is 176.57 meter
        $this->assertEquals('176.57', number_format($lineSegments[0]->getLength(), 2, '.', ''));

        // section 137 -> 138 is 160 meter
        $this->assertEquals('94.27', number_format($lineSegments[1]->getLength(), 2, '.', ''));

        // section 138 -> 139 is 999 meter
        $this->assertEquals('279.43', number_format($lineSegments[2]->getLength(), 2, '.', ''));

        // entering and leaving first inserted section after (176.57 / 629.77) * 150 seconds = after 42 seconds

        $this->assertEquals(1692181842, $calculatedRoute[0]->getFirstTimestamp());
        $this->assertEquals(1692181842, $calculatedRoute[0]->getLastTimestamp());

        // entering and leaving first inserted section after ((176.57 + 94.27) / 629.77) * 150 seconds = after 65 seconds

        $this->assertEquals(1692181865, $calculatedRoute[1]->getFirstTimestamp());
        $this->assertEquals(1692181865, $calculatedRoute[1]->getLastTimestamp());
    }

    public function testFindShortestRouteOneHop()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        $route = $routeCalculator->findShortestRoute(135, 136);

        $this->assertNotNull($route);

        $this->assertCount(2, $route);

        $this->assertEquals(135, $route[0]);
        $this->assertEquals(136, $route[1]);
    }

    public function testFindShortestRouteTwoHops()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        $route = $routeCalculator->findShortestRoute(135, 137);

        $this->assertNotNull($route);

        $this->assertCount(3, $route);

        $this->assertEquals(135, $route[0]);
        $this->assertEquals(136, $route[1]);
        $this->assertEquals(137, $route[2]);
    }

    public function testFindShortestRouteThreeHops()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        $route = $routeCalculator->findShortestRoute(135, 138);

        $this->assertNotNull($route);

        $this->assertCount(4, $route);

        $this->assertEquals(135, $route[0]);
        $this->assertEquals(136, $route[1]);
        $this->assertEquals(137, $route[2]);
        $this->assertEquals(138, $route[3]);
    }

    public function testFindShortestRouteFourHops()
    {

        $waterwaySegments = $this->createWaterwaySegments();

        $routeCalculator = new RouteCalculator();
        $routeCalculator->initialize($waterwaySegments);

        $route = $routeCalculator->findShortestRoute(136, 466);

        $this->assertNotNull($route);

        $this->assertCount(5, $route);

        $this->assertEquals(136, $route[0]);
        $this->assertEquals(137, $route[1]);
        $this->assertEquals(464, $route[2]);
        $this->assertEquals(465, $route[3]);
        $this->assertEquals(466, $route[4]);
    }
}
