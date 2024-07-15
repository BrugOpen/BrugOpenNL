<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Core\TestEventDispatcher;
use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Geo\Model\Polygon;
use BrugOpen\Tracking\Event\JourneyEvent;
use BrugOpen\Tracking\Event\SegmentEvent;
use BrugOpen\Tracking\Model\AISRecord;
use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\Vessel;
use BrugOpen\Tracking\Model\VesselJourney;
use BrugOpen\Tracking\Model\WaterwaySegment;
use BrugOpen\Tracking\Service\VesselPositionProcessor;
use PHPUnit\Framework\TestCase;

class VesselPositionProcessorTest extends TestCase
{

    public function testInitializeWaterwaySegments()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create test segments
        $segments = array();

        $polygon = new Polygon('52.139655,4.485818
        52.140251,4.487722
        52.140791,4.487132
        52.140278,4.485742');

        $this->assertCount(5, $polygon->getPath());

        $segment = new WaterwaySegment();
        $segment->setId(1);
        $segment->setPolygon($polygon);

        $bounds = $segment->getBounds();
        $this->assertNotNull($bounds);

        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(2);
        $segment->setPolygon(new Polygon('52.141733,4.490705
        52.140251,4.487722
        52.140791,4.487132
        52.141469,4.488516
        52.142148,4.490243'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        $segmentBounds = $processor->getWaterwaySegmentBounds();

        $this->assertCount(2, $segmentBounds);

    }

    public function testProcessVesselPositionOutsideSegments()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(1);
        $segment->setPolygon(new Polygon('52.139655,4.485818
        52.140251,4.487722
        52.140791,4.487132
        52.140278,4.485742'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(2);
        $segment->setPolygon(new Polygon('52.141733,4.490705
        52.140251,4.487722
        52.140791,4.487132
        52.141469,4.488516
        52.142148,4.490243'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // process AISRecord
        $latLng = new LatLng(52.1463504,4.5043949);

        $aisRecord = new AISRecord();
        $aisRecord->setLocation($latLng);

        $processor->processVesselPosition($aisRecord);

        // assert no events emitted

        $this->assertCount(0, $eventDispatcher->getPostedEvents());

        // assert no active journeys

        $this->assertCount(0, $processor->getCurrentJourneys());

    }

    public function testProcessVesselPositionInSegmentNewJourneyStart()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::INFO));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(1);
        $segment->setPolygon(new Polygon('52.139655,4.485818
        52.140251,4.487722
        52.140791,4.487132
        52.140278,4.485742'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(2);
        $segment->setPolygon(new Polygon('52.141733,4.490705
        52.140251,4.487722
        52.140791,4.487132
        52.141469,4.488516
        52.142148,4.490243'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // first point is in segment 2
        $latLng1 = new LatLng(52.141832,4.490018);

        $this->assertTrue($segment->getPolygon()->isPointInPolygon($latLng1));

        // create AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1691936021);

        $processor->processVesselPosition($aisRecord);

        // assert 2 events emitted

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(2, $recordedEvents);

        // check events
        $this->assertTrue($recordedEvents[0]['params'][0] instanceof JourneyEvent);
        $this->assertTrue($recordedEvents[1]['params'][0] instanceof SegmentEvent);

        /**
         * @var JourneyEvent
         */
        $journeyEvent = $recordedEvents[0]['params'][0];

        /**
         * @var SegmentEvent
         */
        $segmentEvent = $recordedEvents[1]['params'][0];

        // check journey event
        $this->assertEquals(JourneyEvent::EVENT_TYPE_START, $journeyEvent->getType());
        $this->assertEquals('12345678-1691936021', $journeyEvent->getJourney()->getId());
        $this->assertNotNull($journeyEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $journeyEvent->getJourney()->getVessel()->getMmsi());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getFirstLocation());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getLastLocation());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getFirstTimestamp());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getLastTimestamp());

        // check segment event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_ENTER, $segmentEvent->getType());
        $this->assertEquals(1691936021, $segmentEvent->getCurrentTimestamp());
        $this->assertEquals($latLng1, $segmentEvent->getCurrentLocation());
        $this->assertNotNull($segmentEvent->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent->getJourney()->getId());
        $this->assertNotNull($segmentEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent->getJourney()->getVessel()->getMmsi());

        // assert 1 active journey
        $this->assertCount(1, $processor->getCurrentJourneys());

    }

    public function testProcessVesselPositionInSegmentJourneyUpdate()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::INFO));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(1);
        $segment->setPolygon(new Polygon('52.139655,4.485818
        52.140251,4.487722
        52.140791,4.487132
        52.140278,4.485742'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(2);
        $segment->setPolygon(new Polygon('52.141733,4.490705
        52.140251,4.487722
        52.140791,4.487132
        52.141469,4.488516
        52.142148,4.490243'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // first point is in segment 2
        $latLng1 = new LatLng(52.141832,4.490018);

        $this->assertTrue($segment->getPolygon()->isPointInPolygon($latLng1));

        // create first AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1691936021);

        $processor->processVesselPosition($aisRecord);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // second point is in segment 2
        $latLng2 = new LatLng(52.141101,4.4882);

        // create second AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng2);
        $aisRecord->setTimestamp(1691936081);

        $processor->processVesselPosition($aisRecord);

        // assert 1 event emitted

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(1, $recordedEvents);

        // check events
        $this->assertTrue($recordedEvents[0]['params'][0] instanceof SegmentEvent);

        /**
         * @var SegmentEvent
         */
        $segmentEvent = $recordedEvents[0]['params'][0];

        // check segment event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_UPDATE, $segmentEvent->getType());
        $this->assertEquals(1691936081, $segmentEvent->getCurrentTimestamp());
        $this->assertEquals($latLng2, $segmentEvent->getCurrentLocation());
        $this->assertNotNull($segmentEvent->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent->getJourney()->getId());

        $this->assertEquals($latLng1, $segmentEvent->getJourney()->getFirstLocation());
        $this->assertEquals($latLng2, $segmentEvent->getJourney()->getLastLocation());
        $this->assertEquals(1691936021, $segmentEvent->getJourney()->getFirstTimestamp());
        $this->assertEquals(1691936081, $segmentEvent->getJourney()->getLastTimestamp());

        $this->assertNotNull($segmentEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent->getJourney()->getVessel()->getMmsi());

        // assert 1 active journey
        $this->assertCount(1, $processor->getCurrentJourneys());

    }

    public function testProcessVesselPositionInSecondSegmentJourneyUpdate()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::INFO));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(1);
        $segment->setPolygon(new Polygon('52.139655,4.485818
        52.140251,4.487722
        52.140791,4.487132
        52.140278,4.485742'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(2);
        $segment->setPolygon(new Polygon('52.141733,4.490705
        52.140251,4.487722
        52.140791,4.487132
        52.141469,4.488516
        52.142148,4.490243'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // first point is in segment 2
        $latLng1 = new LatLng(52.141832,4.490018);

        $this->assertTrue($segment->getPolygon()->isPointInPolygon($latLng1));

        // create first AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1691936021);

        $processor->processVesselPosition($aisRecord);

        // second point is also in segment 2
        $latLng2 = new LatLng(52.141101,4.4882);

        // create second AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng2);
        $aisRecord->setTimestamp(1691936081);

        $processor->processVesselPosition($aisRecord);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // third point is in segment 1 on other side of bridge
        $latLng3 = new LatLng(52.140416,4.486638);

        // create third AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng3);
        $aisRecord->setTimestamp(1691936141);

        $processor->processVesselPosition($aisRecord);

        // assert 3 events emitted

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(3, $recordedEvents);

        // check events
        $this->assertTrue($recordedEvents[0]['params'][0] instanceof SegmentEvent);
        $this->assertTrue($recordedEvents[1]['params'][0] instanceof JourneyEvent);
        $this->assertTrue($recordedEvents[2]['params'][0] instanceof SegmentEvent);

        /**
         * @var SegmentEvent
         */
        $segmentEvent = $recordedEvents[0]['params'][0];

        // check segment event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_EXIT, $segmentEvent->getType());
        $this->assertEquals(1691936141, $segmentEvent->getCurrentTimestamp());
        $this->assertEquals($latLng3, $segmentEvent->getCurrentLocation());
        $this->assertNotNull($segmentEvent->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent->getJourney()->getId());
        $this->assertNotNull($segmentEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent->getJourney()->getVessel()->getMmsi());

        /**
         * @var JourneyEvent
         */
        $journeyEvent = $recordedEvents[1]['params'][0];

        // check journey event
        $this->assertEquals(JourneyEvent::EVENT_TYPE_UPDATE, $journeyEvent->getType());
        $this->assertEquals('12345678-1691936021', $journeyEvent->getJourney()->getId());
        $this->assertNotNull($journeyEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $journeyEvent->getJourney()->getVessel()->getMmsi());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getFirstLocation());

        // check second segment event

        /**
         * @var SegmentEvent
         */
        $segmentEvent = $recordedEvents[2]['params'][0];

        // check segment event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_ENTER, $segmentEvent->getType());
        $this->assertEquals(1691936141, $segmentEvent->getCurrentTimestamp());
        $this->assertEquals($latLng3, $segmentEvent->getCurrentLocation());
        $this->assertNotNull($segmentEvent->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent->getJourney()->getId());
        $this->assertNotNull($segmentEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent->getJourney()->getVessel()->getMmsi());

        // assert 1 active journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);

        // check waterway segments
        $this->assertCount(2, $currentJourneys['12345678']->getJourneySegments());

    }

    public function testProcessVesselPositionJourneyUpdateAfterMovingOutOfKnownSegmentsOnJourneyWithOneSegment()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(1);
        $segment->setPolygon(new Polygon('52.139655,4.485818
        52.140251,4.487722
        52.140791,4.487132
        52.140278,4.485742'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(2);
        $segment->setPolygon(new Polygon('52.141733,4.490705
        52.140251,4.487722
        52.140791,4.487132
        52.141469,4.488516
        52.142148,4.490243'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // first point is in segment 2
        $latLng1 = new LatLng(52.141832,4.490018);

        $this->assertTrue($segment->getPolygon()->isPointInPolygon($latLng1));

        // create AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1691936021);

        $processor->processVesselPosition($aisRecord);

        // assert 2 events emitted

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(2, $recordedEvents);

        // check events
        $this->assertTrue($recordedEvents[0]['params'][0] instanceof JourneyEvent);
        $this->assertTrue($recordedEvents[1]['params'][0] instanceof SegmentEvent);

        /**
         * @var JourneyEvent
         */
        $journeyEvent = $recordedEvents[0]['params'][0];

        /**
         * @var SegmentEvent
         */
        $segmentEvent = $recordedEvents[1]['params'][0];

        // check journey event
        $this->assertEquals(JourneyEvent::EVENT_TYPE_START, $journeyEvent->getType());
        $this->assertEquals('12345678-1691936021', $journeyEvent->getJourney()->getId());
        $this->assertNotNull($journeyEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $journeyEvent->getJourney()->getVessel()->getMmsi());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getFirstLocation());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getLastLocation());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getFirstTimestamp());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getLastTimestamp());

        // check segment event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_ENTER, $segmentEvent->getType());
        $this->assertEquals(1691936021, $segmentEvent->getCurrentTimestamp());
        $this->assertEquals($latLng1, $segmentEvent->getCurrentLocation());
        $this->assertNotNull($segmentEvent->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent->getJourney()->getId());
        $this->assertNotNull($segmentEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent->getJourney()->getVessel()->getMmsi());

        // assert 1 active journey
        $this->assertCount(1, $processor->getCurrentJourneys());

        // now process new record that is not in any known segment

        $latLng2 = new LatLng(52.1459816,4.503645);

        $this->assertFalse($segments[0]->getPolygon()->isPointInPolygon($latLng2));
        $this->assertFalse($segments[1]->getPolygon()->isPointInPolygon($latLng2));

        // create AISRecord
        $aisRecord2 = new AISRecord();
        $aisRecord2->setMmsi('12345678');
        $aisRecord2->setLocation($latLng2);
        $aisRecord2->setTimestamp(1691936081);

        // reset event dispatcher
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // process ais record
        $processor->processVesselPosition($aisRecord2);

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(2, $recordedEvents);

        // check events

        $segmentEvent = $recordedEvents[0]['params'][0];
        $journeyEvent = $recordedEvents[1]['params'][0];

        $this->assertTrue($segmentEvent instanceof SegmentEvent);
        $this->assertTrue($journeyEvent instanceof JourneyEvent);

        // check segment exit event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_EXIT, $segmentEvent->getType());
        $this->assertEquals(1691936081, $segmentEvent->getCurrentTimestamp());
        $this->assertEquals($latLng2, $segmentEvent->getCurrentLocation());
        $this->assertNotNull($segmentEvent->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent->getJourney()->getId());
        $this->assertNotNull($segmentEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent->getJourney()->getVessel()->getMmsi());

        // check journey update event
        $this->assertEquals(JourneyEvent::EVENT_TYPE_UPDATE, $journeyEvent->getType());
        $this->assertEquals('12345678-1691936021', $journeyEvent->getJourney()->getId());
        $this->assertNotNull($journeyEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $journeyEvent->getJourney()->getVessel()->getMmsi());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getFirstLocation());
        $this->assertEquals($latLng2, $journeyEvent->getJourney()->getLastLocation());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getFirstTimestamp());
        $this->assertEquals(1691936081, $journeyEvent->getJourney()->getLastTimestamp());

        // assert 1 active journey
        $this->assertCount(1, $processor->getCurrentJourneys());

        // assert 0 ended journeys
        $this->assertCount(0, $processor->getEndedJourneys());

    }

    public function testProcessVesselPositionJourneyEndAfterMovingOutOfKnownSegmentsOnJourneyWithTwoSegments()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(1);
        $segment->setPolygon(new Polygon('52.139655,4.485818
        52.140251,4.487722
        52.140791,4.487132
        52.140278,4.485742'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(2);
        $segment->setPolygon(new Polygon('52.141733,4.490705
        52.140251,4.487722
        52.140791,4.487132
        52.141469,4.488516
        52.142148,4.490243'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // first point is in segment 2
        $latLng1 = new LatLng(52.141832,4.490018);

        $this->assertTrue($segment->getPolygon()->isPointInPolygon($latLng1));

        // create AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1691936021);

        $processor->processVesselPosition($aisRecord);

        // second point is in segment 1 on other side of bridge
        $latLng2 = new LatLng(52.140416,4.486638);

        // create second AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng2);
        $aisRecord->setTimestamp(1691936141);

        $processor->processVesselPosition($aisRecord);

        // assert 1 active journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);

        // assert journey now has 2 segments

        $this->assertCount(2, $currentJourneys['12345678']->getJourneySegments());

        // now process new record that is not in any known segment

        $latLng3 = new LatLng(52.1459816,4.503645);

        $this->assertFalse($segments[0]->getPolygon()->isPointInPolygon($latLng3));
        $this->assertFalse($segments[1]->getPolygon()->isPointInPolygon($latLng3));

        // create third AISRecord
        $aisRecord3 = new AISRecord();
        $aisRecord3->setMmsi('12345678');
        $aisRecord3->setLocation($latLng3);
        $aisRecord3->setTimestamp(1691936201);

        // reset event dispatcher
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // process ais record
        $processor->processVesselPosition($aisRecord3);

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(2, $recordedEvents);

        /**
         * @var SegmentEvent
         */
        $segmentEvent = $recordedEvents[0]['params'][0];

        /**
         * @var JourneyEvent
         */
        $journeyEvent = $recordedEvents[1]['params'][0];

        // check events
        $this->assertTrue($journeyEvent instanceof JourneyEvent);
        $this->assertTrue($segmentEvent instanceof SegmentEvent);

        // check segment exit event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_EXIT, $segmentEvent->getType());
        $this->assertEquals(1691936201, $segmentEvent->getCurrentTimestamp());
        $this->assertEquals($latLng3, $segmentEvent->getCurrentLocation());
        $this->assertNotNull($segmentEvent->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent->getJourney()->getId());
        $this->assertNotNull($segmentEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent->getJourney()->getVessel()->getMmsi());

        // check journey end event
        $this->assertEquals(JourneyEvent::EVENT_TYPE_END, $journeyEvent->getType());
        $this->assertEquals('12345678-1691936021', $journeyEvent->getJourney()->getId());
        $this->assertNotNull($journeyEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $journeyEvent->getJourney()->getVessel()->getMmsi());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getFirstLocation());
        $this->assertEquals($latLng2, $journeyEvent->getJourney()->getLastLocation());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getFirstTimestamp());
        $this->assertEquals(1691936141, $journeyEvent->getJourney()->getLastTimestamp());

        // assert no active journeys
        $this->assertCount(0, $processor->getCurrentJourneys());

        // assert 1 ended journey
        $this->assertCount(1, $processor->getEndedJourneys());

    }

    public function testProcessVesselPositionFlapBetweenTwoSegments()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(1);
        $segment->setPolygon(new Polygon('52.139655,4.485818
        52.140251,4.487722
        52.140791,4.487132
        52.140278,4.485742'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(2);
        $segment->setPolygon(new Polygon('52.141733,4.490705
        52.140251,4.487722
        52.140791,4.487132
        52.141469,4.488516
        52.142148,4.490243'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // first point is in segment 2
        $latLng1 = new LatLng(52.141832,4.490018);

        $this->assertTrue($segment->getPolygon()->isPointInPolygon($latLng1));

        // create AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1691936021);

        $processor->processVesselPosition($aisRecord);

        // second point is in segment 1 on other side of bridge
        $latLng2 = new LatLng(52.140416,4.486638);

        // create second AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng2);
        $aisRecord->setTimestamp(1691936141);

        $processor->processVesselPosition($aisRecord);

        // assert 1 active journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);

        // assert journey now has 2 segments

        $this->assertCount(2, $currentJourneys['12345678']->getJourneySegments());

        // create third AISRecord
        $aisRecord3 = new AISRecord();
        $aisRecord3->setMmsi('12345678');
        $aisRecord3->setLocation($latLng1);
        $aisRecord3->setTimestamp(1691936201);

        // reset event dispatcher
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // process ais record
        $processor->processVesselPosition($aisRecord3);

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(3, $recordedEvents);

        /**
         * @var SegmentEvent
         */
        $segmentEvent1 = $recordedEvents[0]['params'][0];

        /**
         * @var JourneyEvent
         */
        $journeyEvent = $recordedEvents[1]['params'][0];

        /**
         * @var SegmentEvent
         */
        $segmentEvent2 = $recordedEvents[2]['params'][0];

        // check events
        $this->assertTrue($segmentEvent1 instanceof SegmentEvent);
        $this->assertTrue($journeyEvent instanceof JourneyEvent);
        $this->assertTrue($segmentEvent2 instanceof SegmentEvent);

        // check segment exit event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_EXIT, $segmentEvent1->getType());
        $this->assertEquals(1691936201, $segmentEvent1->getCurrentTimestamp());
        $this->assertEquals($latLng2, $segmentEvent1->getPreviousLocation());
        $this->assertNotNull($segmentEvent1->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent1->getJourney()->getId());
        $this->assertNotNull($segmentEvent1->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent1->getJourney()->getVessel()->getMmsi());

        // check journey update event
        $this->assertEquals(JourneyEvent::EVENT_TYPE_UPDATE, $journeyEvent->getType());
        $this->assertEquals('12345678-1691936021', $journeyEvent->getJourney()->getId());
        $this->assertNotNull($journeyEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $journeyEvent->getJourney()->getVessel()->getMmsi());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getFirstLocation());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getLastLocation());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getFirstTimestamp());
        $this->assertEquals(1691936201, $journeyEvent->getJourney()->getLastTimestamp());

        // check segment enter event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_ENTER, $segmentEvent2->getType());
        $this->assertEquals(1691936201, $segmentEvent2->getCurrentTimestamp());
        $this->assertEquals($latLng1, $segmentEvent2->getCurrentLocation());
        $this->assertNotNull($segmentEvent2->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent2->getJourney()->getId());
        $this->assertNotNull($segmentEvent2->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent2->getJourney()->getVessel()->getMmsi());

        // assert 1 active journey
        $this->assertCount(1, $processor->getCurrentJourneys());

        // assert 0 ended journeys
        $this->assertCount(0, $processor->getEndedJourneys());

        // create fourth AISRecord
        $aisRecord4 = new AISRecord();
        $aisRecord4->setMmsi('12345678');
        $aisRecord4->setLocation($latLng2);
        $aisRecord4->setTimestamp(1691936221);

        // reset event dispatcher
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // process ais record
        $processor->processVesselPosition($aisRecord4);

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(3, $recordedEvents);

        /**
         * @var SegmentEvent
         */
        $segmentEvent1 = $recordedEvents[0]['params'][0];

        /**
         * @var JourneyEvent
         */
        $journeyEvent = $recordedEvents[1]['params'][0];

        /**
         * @var SegmentEvent
         */
        $segmentEvent2 = $recordedEvents[2]['params'][0];

        // check events
        $this->assertTrue($segmentEvent1 instanceof SegmentEvent);
        $this->assertTrue($journeyEvent instanceof JourneyEvent);
        $this->assertTrue($segmentEvent2 instanceof SegmentEvent);

        // check segment exit event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_EXIT, $segmentEvent1->getType());
        $this->assertEquals(1691936221, $segmentEvent1->getCurrentTimestamp());
        $this->assertEquals($latLng1, $segmentEvent1->getPreviousLocation());
        $this->assertNotNull($segmentEvent1->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent1->getJourney()->getId());
        $this->assertNotNull($segmentEvent1->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent1->getJourney()->getVessel()->getMmsi());

        // check journey update event
        $this->assertEquals(JourneyEvent::EVENT_TYPE_UPDATE, $journeyEvent->getType());
        $this->assertEquals('12345678-1691936021', $journeyEvent->getJourney()->getId());
        $this->assertNotNull($journeyEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $journeyEvent->getJourney()->getVessel()->getMmsi());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getFirstLocation());
        $this->assertEquals($latLng2, $journeyEvent->getJourney()->getLastLocation());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getFirstTimestamp());
        $this->assertEquals(1691936221, $journeyEvent->getJourney()->getLastTimestamp());

        // check segment enter event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_ENTER, $segmentEvent2->getType());
        $this->assertEquals(1691936221, $segmentEvent2->getCurrentTimestamp());
        $this->assertEquals($latLng2, $segmentEvent2->getCurrentLocation());
        $this->assertNotNull($segmentEvent2->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent2->getJourney()->getId());
        $this->assertNotNull($segmentEvent2->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent2->getJourney()->getVessel()->getMmsi());

        // assert 1 active journey
        $this->assertCount(1, $processor->getCurrentJourneys());

        // assert 0 ended journeys
        $this->assertCount(0, $processor->getEndedJourneys());

    }

    public function testProcessVesselPositionExitAfterFlapping()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(1);
        $segment->setPolygon(new Polygon('52.139655,4.485818
        52.140251,4.487722
        52.140791,4.487132
        52.140278,4.485742'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(2);
        $segment->setPolygon(new Polygon('52.141733,4.490705
        52.140251,4.487722
        52.140791,4.487132
        52.141469,4.488516
        52.142148,4.490243'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // first point is in segment 2
        $latLng1 = new LatLng(52.141832,4.490018);

        $this->assertTrue($segment->getPolygon()->isPointInPolygon($latLng1));

        // create AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1691936021);

        $processor->processVesselPosition($aisRecord);

        // second point is in segment 1 on other side of bridge
        $latLng2 = new LatLng(52.140416,4.486638);

        // create second AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng2);
        $aisRecord->setTimestamp(1691936141);

        $processor->processVesselPosition($aisRecord);

        // assert 1 active journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);

        // assert journey now has 2 segments

        $this->assertCount(2, $currentJourneys['12345678']->getJourneySegments());

        // create third AISRecord
        $aisRecord3 = new AISRecord();
        $aisRecord3->setMmsi('12345678');
        $aisRecord3->setLocation($latLng1);
        $aisRecord3->setTimestamp(1691936201);

        // reset event dispatcher
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // process ais record
        $processor->processVesselPosition($aisRecord3);

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(3, $recordedEvents);

        /**
         * @var SegmentEvent
         */
        $segmentEvent1 = $recordedEvents[0]['params'][0];

        /**
         * @var JourneyEvent
         */
        $journeyEvent = $recordedEvents[1]['params'][0];

        /**
         * @var SegmentEvent
         */
        $segmentEvent2 = $recordedEvents[2]['params'][0];

        // check events
        $this->assertTrue($segmentEvent1 instanceof SegmentEvent);
        $this->assertTrue($journeyEvent instanceof JourneyEvent);
        $this->assertTrue($segmentEvent2 instanceof SegmentEvent);

        // check segment exit event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_EXIT, $segmentEvent1->getType());
        $this->assertEquals(1691936201, $segmentEvent1->getCurrentTimestamp());
        $this->assertEquals($latLng2, $segmentEvent1->getPreviousLocation());
        $this->assertNotNull($segmentEvent1->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent1->getJourney()->getId());
        $this->assertNotNull($segmentEvent1->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent1->getJourney()->getVessel()->getMmsi());

        // check journey update event
        $this->assertEquals(JourneyEvent::EVENT_TYPE_UPDATE, $journeyEvent->getType());
        $this->assertEquals('12345678-1691936021', $journeyEvent->getJourney()->getId());
        $this->assertNotNull($journeyEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $journeyEvent->getJourney()->getVessel()->getMmsi());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getFirstLocation());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getLastLocation());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getFirstTimestamp());
        $this->assertEquals(1691936201, $journeyEvent->getJourney()->getLastTimestamp());

        // check segment enter event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_ENTER, $segmentEvent2->getType());
        $this->assertEquals(1691936201, $segmentEvent2->getCurrentTimestamp());
        $this->assertEquals($latLng1, $segmentEvent2->getCurrentLocation());
        $this->assertNotNull($segmentEvent2->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent2->getJourney()->getId());
        $this->assertNotNull($segmentEvent2->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent2->getJourney()->getVessel()->getMmsi());

        // assert 1 active journey
        $this->assertCount(1, $processor->getCurrentJourneys());

        // assert 0 ended journeys
        $this->assertCount(0, $processor->getEndedJourneys());

        // now process new record that is not in any known segment

        $latLng3 = new LatLng(52.1459816,4.503645);

        $this->assertFalse($segments[0]->getPolygon()->isPointInPolygon($latLng3));
        $this->assertFalse($segments[1]->getPolygon()->isPointInPolygon($latLng3));

        // create fourth AISRecord
        $aisRecord4 = new AISRecord();
        $aisRecord4->setMmsi('12345678');
        $aisRecord4->setLocation($latLng3);
        $aisRecord4->setTimestamp(1691936221);

        // reset event dispatcher
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // process ais record
        $processor->processVesselPosition($aisRecord4);

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(2, $recordedEvents);

        /**
         * @var SegmentEvent
         */
        $segmentEvent = $recordedEvents[0]['params'][0];

        /**
         * @var JourneyEvent
         */
        $journeyEvent = $recordedEvents[1]['params'][0];

        // check events
        $this->assertTrue($journeyEvent instanceof JourneyEvent);
        $this->assertTrue($segmentEvent instanceof SegmentEvent);

        // check segment exit event
        $this->assertEquals(SegmentEvent::EVENT_TYPE_EXIT, $segmentEvent->getType());
        $this->assertEquals(1691936221, $segmentEvent->getCurrentTimestamp());
        $this->assertEquals($latLng3, $segmentEvent->getCurrentLocation());
        $this->assertNotNull($segmentEvent->getJourney());
        $this->assertEquals('12345678-1691936021', $segmentEvent->getJourney()->getId());
        $this->assertNotNull($segmentEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $segmentEvent->getJourney()->getVessel()->getMmsi());

        // check journey end event
        $this->assertEquals(JourneyEvent::EVENT_TYPE_END, $journeyEvent->getType());
        $this->assertEquals('12345678-1691936021', $journeyEvent->getJourney()->getId());
        $this->assertNotNull($journeyEvent->getJourney()->getVessel());
        $this->assertEquals('12345678', $journeyEvent->getJourney()->getVessel()->getMmsi());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getFirstLocation());
        $this->assertEquals($latLng1, $journeyEvent->getJourney()->getLastLocation());
        $this->assertEquals(1691936021, $journeyEvent->getJourney()->getFirstTimestamp());
        $this->assertEquals(1691936201, $journeyEvent->getJourney()->getLastTimestamp());

    }

    public function testProcessVesselPositionNewSegmentAfterLongPeriodInSingleSegment()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(181);
        $segment->setPolygon(new Polygon('51.927062,4.596803
        51.927909,4.601486
        51.925263,4.601639
        51.923358,4.597071
        51.9204,4.5901
        51.919182,4.584209
        51.9164,4.581038
        51.917521,4.576706
        51.918449,4.576494
        51.920893,4.578735
        51.921756,4.582015
        51.924257,4.589813'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(182);
        $segment->setPolygon(new Polygon('51.913941,4.578201
        51.9164,4.581038
        51.917521,4.576706
        51.915228,4.575516
        51.913523,4.571016
        51.911416,4.567295
        51.910055,4.565077
        51.908853,4.567151
        51.911321,4.572757
        51.913407,4.5767
        51.913809,4.576941
        51.914064,4.577493'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // initialize existing journey
        $existingJourneys = array();

        $vessel = new Vessel();
        $vessel->setMmsi('12345678');

        $existingJourney = new VesselJourney();
        $existingJourney->setId('12345678-1672527389');
        $existingJourney->setVessel($vessel);

        $journeySegment = new JourneySegment();
        $journeySegment->setSegmentId(181);
        $journeySegment->setFirstTimestamp(1672527389);
        $journeySegment->setFirstLocation(new LatLng("51.9221,4.59044"));
        $journeySegment->setLastTimestamp(1672721709);
        $journeySegment->setLastLocation(new LatLng("51.91725,4.57941"));

        $existingJourney->setJourneySegments(array($journeySegment));
        $existingJourneys[] = $existingJourney;

        $processor->initializeJourneys($existingJourneys);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // new point is in segment 182
        $latLng1 = new LatLng(51.91593,4.578495);

        $this->assertTrue($segment->getPolygon()->isPointInPolygon($latLng1));

        // create AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1672721761);

        $processor->processVesselPosition($aisRecord);

        // assert 1 active journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);

        // assert active journey has 2 segments
        $currentSegments = $currentJourneys['12345678']->getJourneySegments();
        $this->assertCount(2, $currentSegments);

        // assert first segment has last timestamp from last segment from previous journey

        $this->assertEquals(181, $currentSegments[0]->getSegmentId());
        $this->assertEquals(new LatLng("51.91725,4.57941"), $currentSegments[0]->getFirstLocation());
        $this->assertEquals(1672721709, $currentSegments[0]->getFirstTimestamp());
        $this->assertEquals(new LatLng("51.91725,4.57941"), $currentSegments[0]->getLastLocation());
        $this->assertEquals(1672721709, $currentSegments[0]->getLastTimestamp());
        $this->assertNotNull($currentSegments[0]->getJourney());

        // assert second segment has timestamp and location from new AisRecord

        $this->assertEquals(182, $currentSegments[1]->getSegmentId());
        $this->assertEquals($latLng1, $currentSegments[1]->getFirstLocation());
        $this->assertEquals(1672721761, $currentSegments[1]->getFirstTimestamp());
        $this->assertEquals($latLng1, $currentSegments[1]->getLastLocation());
        $this->assertEquals(1672721761, $currentSegments[1]->getLastTimestamp());
        $this->assertNotNull($currentSegments[1]->getJourney());

        $recordedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(5, $recordedEvents);

        // journey end
        $this->assertTrue($recordedEvents[0]['params'][0] instanceof JourneyEvent);
        $this->assertEquals(JourneyEvent::EVENT_TYPE_END, $recordedEvents[0]['params'][0]->getType());
        $this->assertEquals('12345678-1672527389', $recordedEvents[0]['params'][0]->getJourney()->getId());

        // journey start
        $this->assertTrue($recordedEvents[1]['params'][0] instanceof JourneyEvent);
        $this->assertEquals(JourneyEvent::EVENT_TYPE_START, $recordedEvents[1]['params'][0]->getType());
        $this->assertEquals('12345678-1672721709', $recordedEvents[1]['params'][0]->getJourney()->getId());

        // segment exit
        $this->assertTrue($recordedEvents[2]['params'][0] instanceof SegmentEvent);
        $this->assertEquals(SegmentEvent::EVENT_TYPE_EXIT, $recordedEvents[2]['params'][0]->getType());
        $this->assertEquals(181, $recordedEvents[2]['params'][0]->getSegment()->getId());

        // journey update
        $this->assertTrue($recordedEvents[3]['params'][0] instanceof JourneyEvent);
        $this->assertEquals(JourneyEvent::EVENT_TYPE_UPDATE, $recordedEvents[3]['params'][0]->getType());
        $this->assertEquals('12345678-1672721709', $recordedEvents[3]['params'][0]->getJourney()->getId());

        // segment enter
        $this->assertTrue($recordedEvents[4]['params'][0] instanceof SegmentEvent);
        $this->assertEquals(SegmentEvent::EVENT_TYPE_ENTER, $recordedEvents[4]['params'][0]->getType());
        $this->assertEquals(182, $recordedEvents[4]['params'][0]->getSegment()->getId());

        // assert 1 ended journey
        $endedJourneys = $processor->getEndedJourneys();
        $this->assertCount(1, $endedJourneys);

        $endedJourney = $endedJourneys[0];

        $this->assertEquals('12345678-1672527389', $endedJourney->getId());

        $endedSegments = $endedJourney->getJourneySegments();

        $this->assertCount(1, $endedSegments);

        $this->assertEquals(181, $endedSegments[0]->getSegmentId());
        $this->assertEquals(new LatLng("51.9221,4.59044"), $endedSegments[0]->getFirstLocation());
        $this->assertEquals(1672527389, $endedSegments[0]->getFirstTimestamp());
        $this->assertEquals(new LatLng("51.91725,4.57941"), $endedSegments[0]->getLastLocation());
        $this->assertEquals(1672721709, $endedSegments[0]->getLastTimestamp());

    }

    public function testProcessVesselPositionNewJourneyAfterLongPeriodInSingleSegmentTooLongAgo()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(100);
        $segment->setPolygon(new Polygon('52.144429,4.499497
        52.142845,4.494739
        52.143346,4.494084
        52.144739,4.498563'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(242);
        $segment->setPolygon(new Polygon('52.194472,4.514747
        52.195689,4.513649
        52.196955,4.511168
        52.196215,4.510722
        52.195316,4.509316
        52.194236,4.507373
        52.192583,4.506831
        52.190502,4.506785
        52.190105,4.507511
        52.190602,4.508619
        52.189252,4.510923
        52.189481,4.513221
        52.193989,4.515615'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // first points are in segment 242
        $latLng1 = new LatLng(52.192218333333,4.5077533333333);
        $latLng2 = new LatLng(52.192208333333,4.507775);

        $this->assertTrue($segments[1]->getPolygon()->isPointInPolygon($latLng1));
        $this->assertTrue($segments[1]->getPolygon()->isPointInPolygon($latLng2));

        // third point is in segment 100
        $latLng3 = new LatLng(52.144028333333,4.4972733333333);
        $this->assertTrue($segments[0]->getPolygon()->isPointInPolygon($latLng3));

        // create and process first AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1672705527);

        $processor->processVesselPosition($aisRecord);

        // create and process second AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng2);
        $aisRecord->setTimestamp(1672723487);

        $processor->processVesselPosition($aisRecord);

        // assert 1 active journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);

        // assert timestamps and locations
        $currentJourney = $currentJourneys['12345678'];
        $currentJourneySegments = $currentJourney->getJourneySegments();
        $this->assertCount(1, $currentJourneySegments);
        $this->assertEquals($latLng1, $currentJourneySegments[0]->getFirstLocation());
        $this->assertEquals($latLng2, $currentJourneySegments[0]->getLastLocation());
        $this->assertEquals(1672705527, $currentJourneySegments[0]->getFirstTimestamp());
        $this->assertEquals(1672723487, $currentJourneySegments[0]->getLastTimestamp());

        // create and process third AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng3);
        $aisRecord->setTimestamp(1672737478);

        $processor->processVesselPosition($aisRecord);

        // assert ended journey
        $endedJourneys = $processor->getEndedJourneys();
        $this->assertCount(1, $endedJourneys);
        $endedJourneySegments = $endedJourneys[0]->getJourneySegments();
        $this->assertCount(1, $endedJourneySegments);
        $this->assertEquals(242, $endedJourneySegments[0]->getSegmentId());
        $this->assertEquals($latLng1, $endedJourneySegments[0]->getFirstLocation());
        $this->assertEquals($latLng2, $endedJourneySegments[0]->getLastLocation());
        $this->assertEquals(1672705527, $endedJourneySegments[0]->getFirstTimestamp());
        $this->assertEquals(1672723487, $endedJourneySegments[0]->getLastTimestamp());

        // assert current journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);

        // assert timestamps and locations
        $currentJourney = $currentJourneys['12345678'];
        $this->assertEquals('12345678-1672737478', $currentJourney->getId());
        $currentJourneySegments = $currentJourney->getJourneySegments();
        $this->assertCount(1, $currentJourneySegments);
        $this->assertEquals(100, $currentJourneySegments[0]->getSegmentId());
        $this->assertEquals($latLng3, $currentJourneySegments[0]->getFirstLocation());
        $this->assertEquals($latLng3, $currentJourneySegments[0]->getLastLocation());
        $this->assertEquals(1672737478, $currentJourneySegments[0]->getFirstTimestamp());
        $this->assertEquals(1672737478, $currentJourneySegments[0]->getLastTimestamp());

    }

    public function testJourneyTimeoutBetweenTwoKnownSegments()
    {

        // create VesselPositionProcessor
        $processor = new VesselPositionProcessor();

        // create a log channel
        $log = new \Monolog\Logger('BrugOpen.VesselPositionProcessor');
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::DEBUG));

        $processor->setLog($log);

        // create test segments
        $segments = array();

        $segment = new WaterwaySegment();
        $segment->setId(19);
        $segment->setPolygon(new Polygon('52.103413,4.431019
        52.102793,4.431552
        52.100354,4.426034
        52.0988,4.423073
        52.096219,4.417612
        52.094811,4.415143
        52.095078,4.4145
        52.096731,4.417264
        52.098139,4.420219
        52.099158,4.422559
        52.101732,4.427666
        52.103189,4.430286'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(14);
        $segment->setPolygon(new Polygon('52.121291,4.4557
        52.119872,4.4536
        52.118003,4.451199
        52.116898,4.449949
        52.116611,4.450588
        52.121112,4.456372'));
        $segments[] = $segment;

        $segment = new WaterwaySegment();
        $segment->setId(241);
        $segment->setPolygon(new Polygon('52.197783,4.533473
        52.196252,4.530901
        52.1942,4.528361
        52.193523,4.531717
        52.195552,4.534661
        52.195277,4.536673
        52.1963,4.537626
        52.197085,4.536755'));
        $segments[] = $segment;

        $processor->initalizeWaterwaySegments($segments);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $processor->setEventDispatcher($eventDispatcher);

        // first point is in segment 19
        $latLng1 = new LatLng(52.099062536718,4.4229810095111);
        $this->assertTrue($segments[0]->getPolygon()->isPointInPolygon($latLng1));

        // second point is in segment 14
        $latLng2 = new LatLng(52.119032326695,4.4531103616927);
        $this->assertTrue($segments[1]->getPolygon()->isPointInPolygon($latLng2));

        // third point is in segment 241
        $latLng3 = new LatLng(52.196149964057,4.5332691775681);
        $this->assertTrue($segments[2]->getPolygon()->isPointInPolygon($latLng3));

        // create and process first AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng1);
        $aisRecord->setTimestamp(1685447712);

        $processor->processVesselPosition($aisRecord);

        // assert 1 active journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);
        $this->assertEquals('12345678-1685447712', $currentJourneys['12345678']->getId());

        // create and process second AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng2);
        $aisRecord->setTimestamp(1685448524);

        $processor->processVesselPosition($aisRecord);

        // assert 1 active journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);
        $this->assertEquals('12345678-1685447712', $currentJourneys['12345678']->getId());

        // assert 0 ended journeys
        $endedJourneys = $processor->getEndedJourneys();
        $this->assertEmpty($endedJourneys);

        // create and process third AISRecord
        $aisRecord = new AISRecord();
        $aisRecord->setMmsi('12345678');
        $aisRecord->setLocation($latLng3);
        $aisRecord->setTimestamp(1685452572);

        $processor->processVesselPosition($aisRecord);

        // assert 1 ended journey
        $endedJourneys = $processor->getEndedJourneys();
        $this->assertCount(1, $endedJourneys);
        $this->assertEquals('12345678-1685447712', $endedJourneys[0]->getId());

        // assert 1 active journey
        $currentJourneys = $processor->getCurrentJourneys();
        $this->assertCount(1, $currentJourneys);
        $this->assertArrayHasKey('12345678', $currentJourneys);
        $this->assertEquals('12345678-1685452572', $currentJourneys['12345678']->getId());

    }

}
