<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Model\Bridge;
use BrugOpen\Tracking\Model\WaterwaySegment;
use BrugOpen\Tracking\Service\JourneyDataService;
use PHPUnit\Framework\TestCase;

class JourneyProjectorTest extends TestCase
{

    public function createBridges()
    {

        $bridges = array();

        $csv = <<<END
id,connections
2,"14=153,194=154"
24,"148=157,328=156"
77,"17=138,197=139"
90,"4=147,184=148"
504,"9=166,189=167"
512,"12=165,192=166"
555,"77=511,257=168"
END;

        $lines = explode("\n", trim($csv));

        $headers = null;

        foreach ($lines as $line) {

            $fields = str_getcsv($line);

            if ($headers === null) {
                $headers = $fields;
            } else {

                $values = array();
                foreach ($headers as $i => $header) {

                    $values[$header] = array_key_exists($i, $fields) ? $fields[$i] : null;
                }

                $bridge = new Bridge();
                $bridge->setId((int)$values['id']);
                $connections = array();
                $connectionParts = explode(",", $values['connections']);
                foreach ($connectionParts as $connectionPart) {
                    $partParts = explode('=', $connectionPart);
                    $connections[(int)$partParts[0]] = (int)$partParts[1];
                }
                $bridge->setConnectedSegmentIds($connections);

                $bridges[$bridge->getId()] = $bridge;
            }
        }

        return $bridges;
    }

    public function createWaterwaySegments()
    {
        $waterwaySegments = array();

        $csv = <<<END
id,routepoints,connections
168,"51.996956657379,4.6913643510461","167,511"
167,"51.999206742977,4.6927287980211","166,168"
511,"51.997099851494,4.6977735934417","168,510,512"
166,"52.001792073061,4.6935087377554","165,167"
510,"51.998307932904,4.6956900107045","509,511"
512,"51.998890331747,4.7032241204666","511,513"
165,"52.00368333818,4.6940840060101","164,166"
509,"51.999660864356,4.6959602440254","508,510"
513,"52.002160932589,4.7077536272393","512,514"
164,"52.007333201726,4.6917858880121","163,165,506"
508,"52.001717054528,4.6962461234641","507,509"
514,"52.005540694752,4.7123213580468",513
163,"52.011572523,4.6859358991964","162,164"
506,"52.004877445871,4.6954579730295","164,507"
507,"52.003617432904,4.6960137282869","506,508"
162,"52.014833681153,4.6842562772552","161,163"
161,"52.016427840717,4.6830650561972","160,162"
160,"52.018413730036,4.6821929305714","159,161"
159,"52.020654120985,4.6804287761066","158,160"
158,"52.021998537024,4.6758133825751","157,159"
157,"52.022941883402,4.6701593163129","156,158"
156,"52.027460544125,4.6639802894087","155,157"
155,"52.034205254562,4.658925948527","154,156"
154,"52.040960033912,4.6591641335025","153,155"
153,"52.046509102556,4.6599895975028","152,154"
152,"52.051096655286,4.6615548730746","151,153"
151,"52.055072872879,4.6628800033707","150,152"
150,"52.060485806109,4.6614429397496","149,151"
149,"52.067170330973,4.6589148708242","148,150"
148,"52.072219789251,4.6602656155085","147,149"
147,"52.076712486973,4.6606988005818","146,148"
146,"52.081499649703,4.6616150935412","145,147"
145,"52.085943994273,4.6612812259733","144,146"
144,"52.093733602962,4.6631477492472","143,145"
143,"52.10338144374,4.6661974474309","142,144"
142,"52.10966514927,4.669733070746","141,143"
141,"52.112701044984,4.6712567280606","140,142"
140,"52.113930733148,4.6719157475759","139,141"
139,"52.11602844707,4.6730691387957","138,140"
138,"52.118113136042,4.6739522426033","137,139"
137,"52.119130164714,4.6743674740701","136,138,464,468"
136,"52.120090196981,4.6699759661283",137
464,"52.118779333152,4.6754985727598",137
468,"52.120087634021,4.6752230874467",137
END;

        $lines = explode("\n", trim($csv));

        $headers = null;

        foreach ($lines as $line) {

            $fields = str_getcsv($line);

            if ($headers === null) {
                $headers = $fields;
            } else {

                $values = array();
                foreach ($headers as $i => $header) {

                    $values[$header] = array_key_exists($i, $fields) ? $fields[$i] : null;
                }

                $segmentId = (int)$values['id'];

                $segment = new WaterwaySegment();
                $segment->setId($segmentId);

                $routePoints = array();
                if ($values['routepoints']) {
                    $routePoints[] = new LatLng($values['routepoints']);
                }

                $segment->setRoutePoints($routePoints);

                $connectedSegmentIds = array();

                if ($values['connections']) {

                    foreach (explode(',', $values['connections']) as $connectedSegmentId) {
                        $connectedSegmentIds[] = (int)$connectedSegmentId;
                    }
                }

                $segment->setConnectedSegmentIds($connectedSegmentIds);

                $waterwaySegments[$segmentId] = $segment;
            }
        }

        return $waterwaySegments;
    }

    public function testFindLongestSingleTrack()
    {

        // $bridges = $this->createBridges();
        $segments = $this->createWaterwaySegments();

        $projector = new JourneyProjector();
        $projector->initalizeWaterwaySegments($segments);

        // from Alpherium heading to Gouda
        $journeySegmentIds = array(141, 142);
        $projectedTrack = $projector->findLongestSingleTrack($journeySegmentIds);
        $this->assertNotEmpty($projectedTrack);
        $this->assertCount(22, $projectedTrack);
        $this->assertEquals(143, $projectedTrack[0]);
        $this->assertEquals(164, $projectedTrack[21]);

        // from Gouda heading to Alphen before intersection
        $journeySegmentIds = array(168, 167);
        $projectedTrack = $projector->findLongestSingleTrack($journeySegmentIds);
        $this->assertNotEmpty($projectedTrack);
        $this->assertCount(3, $projectedTrack);
        $this->assertEquals(166, $projectedTrack[0]);
        $this->assertEquals(164, $projectedTrack[2]);

        // from Gouda heading to Alphen inside intersection
        $journeySegmentIds = array(168, 167, 166, 165, 164);
        $projectedTrack = $projector->findLongestSingleTrack($journeySegmentIds);
        $this->assertEmpty($projectedTrack);

        // from Gouda heading to Alphen inside intersection
        $journeySegmentIds = array(165, 164, 163);
        $projectedTrack = $projector->findLongestSingleTrack($journeySegmentIds);
        $this->assertNotEmpty($projectedTrack);
        $this->assertCount(26, $projectedTrack);
        $this->assertEquals(162, $projectedTrack[0]);
        $this->assertEquals(137, $projectedTrack[25]);
    }

    public function testDetermineCurrentSpeed()
    {

        $journeyJson = <<<JSON
{
    "id":"12345678-1716717776",
	"mmsi": "12345678",
	"segments": [
		{
			"segmentId": 167,
			"firstTimestamp": 1716717776,
			"firstLocation": "51.998668333333,4.69182",
			"lastTimestamp": 1716718582,
			"lastLocation": "52.000151666667,4.693165"
		},
		{
			"segmentId": 166,
			"firstTimestamp": 1716718642,
			"firstLocation": "52.001055,4.6934566666667",
			"lastTimestamp": 1716719001,
			"lastLocation": "52.001655,4.6936833333333"
		},
		{
			"segmentId": 165,
			"firstTimestamp": 1716719061,
			"firstLocation": "52.002845,4.694015",
			"lastTimestamp": 1716719121,
			"lastLocation": "52.004296666667,4.6943916666667"
		},
		{
			"segmentId": 164,
			"firstTimestamp": 1716719241,
			"firstLocation": "52.006248333333,4.6929883333333",
			"lastTimestamp": 1716719485,
			"lastLocation": "52.009445,4.68891"
		},
		{
			"segmentId": 163,
			"firstTimestamp": 1716719544,
			"firstLocation": "52.010266666667,4.687755",
			"lastTimestamp": 1716719724,
			"lastLocation": "52.013245,4.6844333333333"
		}
	]
}
JSON;

        $journeyDataService = new JourneyDataService();
        $journey = $journeyDataService->parseJourney(json_decode($journeyJson, true));
        $journeySegments = $journey->getJourneySegments();

        $journeyProjector = new JourneyProjector();
        $cruiseSpeedData = $journeyProjector->determineCurrentSpeed($journeySegments);

        $this->assertNotNull($cruiseSpeedData);
        $this->assertCount(2, $cruiseSpeedData);
        $this->assertTrue($cruiseSpeedData[0] >= 6);
        $this->assertTrue($cruiseSpeedData[0] <= 8);
    }

    public function testProjectRouteToBridge()
    {

        $journeyProjector = new JourneyProjector();
        $journeyProjector->initalizeWaterwaySegments($this->createWaterwaySegments());

        $startLocation = new LatLng("52.013245,4.6844333333333");

        $projectedSegmentIds = array();
        $projectedSegmentIds[] = 162;
        $projectedSegmentIds[] = 161;
        $projectedSegmentIds[] = 160;
        $projectedSegmentIds[] = 159;
        $projectedSegmentIds[] = 158;
        $projectedSegmentIds[] = 157;
        $projectedSegmentIds[] = 156;

        $bridge = new Bridge();
        $bridge->setId(24);
        $bridge->setLatLng(new LatLng('52.024437,4.6677303'));
        $bridge->setConnectedSegmentIds(array(148 => 157, 328 => 156));

        $projectedRoute = $journeyProjector->projectRouteToBridge($bridge, $startLocation, $projectedSegmentIds);
        $this->assertNotNull($projectedRoute);

        $distance = $projectedRoute->getLineLength();
        $this->assertEquals(1863, round($distance));
    }
}
