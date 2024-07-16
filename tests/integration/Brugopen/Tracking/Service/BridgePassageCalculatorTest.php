<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Geo\Model\Polygon;
use BrugOpen\Model\Bridge;
use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\WaterwaySegment;
use PHPUnit\Framework\TestCase;

class BridgePassageCalculatorTest extends TestCase
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

    public function createBridges()
    {

        $bridges = array();

        $bridge = new Bridge();
        $bridge->setId(90);
        $bridge->setTitle('Hefbrug Boskoop');
        $bridge->setLatLng(new LatLng(52.074272, 4.660209));
        $bridge->setConnectedSegmentIds(array(4 => 147, 184 => 148));

        $bridges[$bridge->getId()] = $bridge;

        $bridge = new Bridge();
        $bridge->setId(77);
        $bridge->setTitle('Hefbrug Gouwsluis');
        $bridge->setLatLng(new LatLng(52.11753, 4.673765));
        $bridge->setConnectedSegmentIds(array(17 => 138, 197 => 139));

        $bridges[$bridge->getId()] = $bridge;

        return $bridges;
    }

    public function testDetectBridgePassagesConnectedSegmentsWithoutConnectingBridge()
    {

        $waterwaySegments = $this->createWaterwaySegments();
        $bridges = $this->createBridges();

        $passageCalculator = new BridgePassageCalculator();
        $passageCalculator->setWaterwaySegments($waterwaySegments);
        $passageCalculator->setBridges($bridges);

        // entering journey segment somewhere in 137:
        $enteringJourneySegment = new JourneySegment();
        $enteringJourneySegment->setSegmentId(137);
        $enteringJourneySegment->setLastLocation(new LatLng(52.118955, 4.674364));

        // exiting journey segment
        $exitingJourneySegment = new JourneySegment();
        $exitingJourneySegment->setSegmentId(136);

        // previous journey segments were 135
        $previousJourneySegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousJourneySegments[] = $previousSegment;

        $bridgePassages = $passageCalculator->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

        $this->assertCount(0, $bridgePassages);
    }

    public function testDetectBridgePassagesConnectedSegmentsWithConnectingBridge()
    {

        $waterwaySegments = $this->createWaterwaySegments();
        $bridges = $this->createBridges();

        $passageCalculator = new BridgePassageCalculator();
        $passageCalculator->setWaterwaySegments($waterwaySegments);
        $passageCalculator->setBridges($bridges);

        // entering journey segment somewhere in 139
        $enteringJourneySegment = new JourneySegment();
        $enteringJourneySegment->setSegmentId(139);

        // exiting journey segment
        $exitingJourneySegment = new JourneySegment();
        $exitingJourneySegment->setSegmentId(138);

        // previous journey segments were 135 - 137
        $previousJourneySegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(137);
        $previousJourneySegments[] = $previousSegment;

        $bridgePassages = $passageCalculator->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

        $this->assertCount(1, $bridgePassages);

        $bridgePassage = $bridgePassages[0];

        $this->assertEquals(77, $bridgePassage->getBridgeId());
        $this->assertEquals(197, $bridgePassage->getDirection());
        $this->assertNull($bridgePassage->getDatetimePassage());
    }

    public function testDetectBridgePassagesConnectedSegmentsWithConnectingBridgeAndTimestamp()
    {

        $waterwaySegments = $this->createWaterwaySegments();
        $bridges = $this->createBridges();

        $passageCalculator = new BridgePassageCalculator();
        $passageCalculator->setWaterwaySegments($waterwaySegments);
        $passageCalculator->setBridges($bridges);

        // exiting journey segment
        $exitingJourneySegment = new JourneySegment();
        $exitingJourneySegment->setSegmentId(138);
        $exitingJourneySegment->setLastTimestamp(1692268500);
        $exitingJourneySegment->setLastLocation(new LatLng(52.118004, 4.673939));

        // entering journey segment somewhere in 139
        $enteringJourneySegment = new JourneySegment();
        $enteringJourneySegment->setSegmentId(139);
        $enteringJourneySegment->setFirstTimestamp(1692268560);
        $enteringJourneySegment->setFirstLocation(new LatLng(52.115088, 4.672732));

        // previous journey segments were 135 - 137
        $previousJourneySegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(137);
        $previousJourneySegments[] = $previousSegment;

        $bridgePassages = $passageCalculator->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

        $this->assertCount(1, $bridgePassages);

        $bridgePassage = $bridgePassages[0];

        $this->assertEquals(77, $bridgePassage->getBridgeId());
        $this->assertEquals(197, $bridgePassage->getDirection());
        $this->assertNotNull($bridgePassage->getDatetimePassage());
        $this->assertEquals(1692268510, $bridgePassage->getDatetimePassage()->getTimestamp());
    }

    public function testDetectBridgePassagesSkippingOneSegmentWithConnectingBridge()
    {

        $waterwaySegments = $this->createWaterwaySegments();
        $bridges = $this->createBridges();

        $passageCalculator = new BridgePassageCalculator();
        $passageCalculator->setWaterwaySegments($waterwaySegments);
        $passageCalculator->setBridges($bridges);

        // entering journey segment somewhere in 139
        $enteringJourneySegment = new JourneySegment();
        $enteringJourneySegment->setSegmentId(139);
        $enteringJourneySegment->setFirstLocation(new LatLng(52.115088, 4.672732));

        // exiting journey segment
        $exitingJourneySegment = new JourneySegment();
        $exitingJourneySegment->setSegmentId(137);
        $exitingJourneySegment->setLastLocation(new LatLng(52.118955, 4.674364));

        // previous journey segments were 135 - 136
        $previousJourneySegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousJourneySegments[] = $previousSegment;

        $bridgePassages = $passageCalculator->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

        $this->assertCount(1, $bridgePassages);

        $bridgePassage = $bridgePassages[0];

        $this->assertEquals(77, $bridgePassage->getBridgeId());
        $this->assertEquals(197, $bridgePassage->getDirection());
        $this->assertNull($bridgePassage->getDatetimePassage());
    }

    public function testDetectBridgePassagesSkippingOneSegmentWithConnectingBridgeWithTimestamp()
    {

        $waterwaySegments = $this->createWaterwaySegments();
        $bridges = $this->createBridges();

        $passageCalculator = new BridgePassageCalculator();
        $passageCalculator->setWaterwaySegments($waterwaySegments);
        $passageCalculator->setBridges($bridges);

        // entering journey segment somewhere in 139
        $enteringJourneySegment = new JourneySegment();
        $enteringJourneySegment->setSegmentId(139);
        $enteringJourneySegment->setFirstTimestamp(1692268560);
        $enteringJourneySegment->setFirstLocation(new LatLng(52.115088, 4.672732));

        // exiting journey segment
        $exitingJourneySegment = new JourneySegment();
        $exitingJourneySegment->setSegmentId(137);
        $exitingJourneySegment->setLastTimestamp(1692268460);
        $exitingJourneySegment->setLastLocation(new LatLng(52.118955, 4.674364));

        // previous journey segments were 135 - 136
        $previousJourneySegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousJourneySegments[] = $previousSegment;

        $bridgePassages = $passageCalculator->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

        $this->assertCount(1, $bridgePassages);

        $bridgePassage = $bridgePassages[0];

        $this->assertEquals(77, $bridgePassage->getBridgeId());
        $this->assertEquals(197, $bridgePassage->getDirection());
        $this->assertNotNull($bridgePassage->getDatetimePassage());
        $this->assertEquals(1692268497, $bridgePassage->getDatetimePassage()->getTimestamp());
    }

    public function testDetectBridgePassagesSkippingTwoSegmentsWithConnectingBridge()
    {

        $waterwaySegments = $this->createWaterwaySegments();
        $bridges = $this->createBridges();

        $passageCalculator = new BridgePassageCalculator();
        $passageCalculator->setWaterwaySegments($waterwaySegments);
        $passageCalculator->setBridges($bridges);

        // entering journey segment somewhere in 140
        $enteringJourneySegment = new JourneySegment();
        $enteringJourneySegment->setSegmentId(140);
        $enteringJourneySegment->setFirstLocation(new LatLng(52.114169, 4.672116));

        // exiting journey segment
        $exitingJourneySegment = new JourneySegment();
        $exitingJourneySegment->setSegmentId(137);
        $exitingJourneySegment->setLastLocation(new LatLng(52.118955, 4.674364));

        // previous journey segments were 135 - 136
        $previousJourneySegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousJourneySegments[] = $previousSegment;

        $bridgePassages = $passageCalculator->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

        $this->assertCount(1, $bridgePassages);

        $bridgePassage = $bridgePassages[0];

        $this->assertEquals(77, $bridgePassage->getBridgeId());
        $this->assertEquals(197, $bridgePassage->getDirection());
        $this->assertNull($bridgePassage->getDatetimePassage());
    }

    public function testDetectBridgePassagesSkippingTwoSegmentsWithConnectingBridgeWithTimestamp()
    {

        $waterwaySegments = $this->createWaterwaySegments();
        $bridges = $this->createBridges();

        $passageCalculator = new BridgePassageCalculator();
        $passageCalculator->setWaterwaySegments($waterwaySegments);
        $passageCalculator->setBridges($bridges);

        // entering journey segment somewhere in 140
        $enteringJourneySegment = new JourneySegment();
        $enteringJourneySegment->setSegmentId(140);
        $enteringJourneySegment->setFirstLocation(new LatLng(52.114169, 4.672116));
        $enteringJourneySegment->setFirstTimestamp(1692268580);

        // exiting journey segment
        $exitingJourneySegment = new JourneySegment();
        $exitingJourneySegment->setSegmentId(137);
        $exitingJourneySegment->setLastTimestamp(1692268460);
        $exitingJourneySegment->setLastLocation(new LatLng(52.118955, 4.674364));

        // previous journey segments were 135 - 136
        $previousJourneySegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousJourneySegments[] = $previousSegment;

        $bridgePassages = $passageCalculator->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

        $this->assertCount(1, $bridgePassages);

        $bridgePassage = $bridgePassages[0];

        $this->assertEquals(77, $bridgePassage->getBridgeId());
        $this->assertEquals(197, $bridgePassage->getDirection());
        $this->assertNotNull($bridgePassage->getDatetimePassage());
        $this->assertEquals(1692268495, $bridgePassage->getDatetimePassage()->getTimestamp());
    }

    public function testDetectBridgePassagesSkippingMultipleSegmentsWithTwoConnectingBridgesWithTimestamps()
    {

        $waterwaySegments = $this->createWaterwaySegments();
        $bridges = $this->createBridges();

        $passageCalculator = new BridgePassageCalculator();
        $passageCalculator->setWaterwaySegments($waterwaySegments);
        $passageCalculator->setBridges($bridges);

        // entering journey segment somewhere in 149
        $enteringJourneySegment = new JourneySegment();
        $enteringJourneySegment->setSegmentId(149);
        $enteringJourneySegment->setFirstLocation(new LatLng(52.068156, 4.659187));
        $enteringJourneySegment->setFirstTimestamp(1692183223); // 12:53:43

        // moving at around 4.2 m/s (15.1km/h) => 1423 seconds for 5975 meter

        // exiting journey segment
        $exitingJourneySegment = new JourneySegment();
        $exitingJourneySegment->setSegmentId(137);
        $exitingJourneySegment->setLastTimestamp(1692181800); // 12:30:00
        $exitingJourneySegment->setLastLocation(new LatLng(52.118955, 4.674364));

        // previous journey segments were 135 - 136
        $previousJourneySegments = array();

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(135);
        $previousJourneySegments[] = $previousSegment;

        $previousSegment = new JourneySegment();
        $previousSegment->setSegmentId(136);
        $previousJourneySegments[] = $previousSegment;

        $bridgePassages = $passageCalculator->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

        $this->assertCount(2, $bridgePassages);

        $bridgePassage = $bridgePassages[0];

        $this->assertEquals(77, $bridgePassage->getBridgeId());
        $this->assertEquals(197, $bridgePassage->getDirection());
        $this->assertNotNull($bridgePassage->getDatetimePassage());
        $this->assertEquals(1692181841, $bridgePassage->getDatetimePassage()->getTimestamp());

        $bridgePassage = $bridgePassages[1];

        $this->assertEquals(90, $bridgePassage->getBridgeId());
        $this->assertEquals(184, $bridgePassage->getDirection());
        $this->assertNotNull($bridgePassage->getDatetimePassage());
        $this->assertEquals(1692183054, $bridgePassage->getDatetimePassage()->getTimestamp());
    }

    public function testCalculatePassageTimeZeroEnteringDistance()
    {

        $bridgeLocation = new LatLng(52.11753, 4.673765);

        $bridge = new Bridge();
        $bridge->setLatLng($bridgeLocation);

        $exitingLocation = new LatLng(52.115578333333, 4.6729816666667);
        $exitingTime = 1689316571;

        $exitingJourneySegment = new JourneySegment();
        $exitingJourneySegment->setLastLocation($exitingLocation);
        $exitingJourneySegment->setLastTimestamp($exitingTime);

        $enteringLocation = new LatLng(52.11753, 4.673765);
        $enteringTime = 1689316712;

        $enteringJourneySegment = new JourneySegment();
        $enteringJourneySegment->setFirstLocation($enteringLocation);
        $enteringJourneySegment->setFirstTimestamp($enteringTime);

        $passageCalculator = new BridgePassageCalculator();

        $passageTime = $passageCalculator->calculatePassageTime($exitingJourneySegment, $enteringJourneySegment, $bridge);

        $this->assertNotNull($passageTime);
        $this->assertEquals(1689316712, $passageTime);
    }
}
