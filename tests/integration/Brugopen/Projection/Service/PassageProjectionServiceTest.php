<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Controller\Admin\EditSegmentsController;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Geo\Model\Polygon;
use BrugOpen\Model\Bridge;
use BrugOpen\Model\BridgePassage;
use BrugOpen\Service\BridgeService;
use BrugOpen\Service\PassageService;
use BrugOpen\Tracking\Model\Vessel;
use BrugOpen\Tracking\Model\VesselJourney;
use BrugOpen\Tracking\Model\WaterwaySegment;
use BrugOpen\Tracking\Service\BridgePassageCalculator;
use BrugOpen\Tracking\Service\JourneyDataService;
use BrugOpen\Tracking\Service\JourneyReconstructor;
use Monolog\Handler\NullHandler;
use PHPUnit\Framework\TestCase;

class PassageProjectionServiceTest extends TestCase
{

    public function createBridgeRecords()
    {

        $bridges = array();

        $csv = <<<END
id,location,connections,clearance
2,"52.0439,4.659652","14:153,194:154",2.5
24,"52.024437,4.6677303","148:157,328:156",4.9
77,"52.11753,4.673765","17:138,197:139",4.3
90,"52.074272,4.660209","4:147,184:148",2.5
504,"52.00096,4.6934137","9:166,189:167",2.5
512,"52.002285,4.693787","12:165,192:166",3.2
555,"51.99685,4.6946993","77:511,257:168",2.73
559,"51.91705,4.5785007","22:181,202:182",4.62
142,"52.385983,4.8830304","29:376,209:375",6.40
166,"52.39044,4.8854675","23:377,203:376",
167,"52.384903,4.882134","17:375,197:374",3.10
END;

        $lines = explode("\n", trim($csv));

        $headers = null;

        foreach ($lines as $line) {

            $fields = str_getcsv(trim($line));

            if ($headers === null) {
                $headers = $fields;
            } else {

                $values = array();
                foreach ($headers as $i => $header) {

                    $values[$header] = array_key_exists($i, $fields) ? $fields[$i] : null;
                }
                $bridges[] = $values;
            }
        }
        return $bridges;
    }

    public function createBridges()
    {

        $bridgeRecords = $this->createBridgeRecords();
        if ($bridgeRecords) {
            foreach ($bridgeRecords as $values) {

                $bridge = new Bridge();
                $bridge->setId((int)$values['id']);
                $location = ($values['location'] != '') ? new \BrugOpen\Geo\Model\LatLng($values['location']) : null;
                $bridge->setLatLng($location);
                $connections = array();
                $connectionParts = explode(",", $values['connections']);
                foreach ($connectionParts as $connectionPart) {
                    $partParts = explode(':', $connectionPart);
                    $connections[(int)$partParts[0]] = (int)$partParts[1];
                }
                $bridge->setConnectedSegmentIds($connections);
                $bridge->setClearance($values['clearance']);

                $bridges[$bridge->getId()] = $bridge;
            }
        }

        return $bridges;
    }

    public function createBridgeService($bridges)
    {
        $bridgeService = new BridgeService();
        $bridgeService->setAllBridges($bridges);
        return $bridgeService;
    }

    public function createJourneyReconstructor($waterwaySegments)
    {
        $journeyReconstructor = new JourneyReconstructor();
        $journeyReconstructor->initalizeWaterwaySegments($waterwaySegments);
        return $journeyReconstructor;
    }

    public function createJourneyProjector($waterwaySegments)
    {
        $journeyProjector = new JourneyProjector();
        $journeyProjector->initalizeWaterwaySegments($waterwaySegments);
        return $journeyProjector;
    }

    public function createBridgePassageCalculator($bridges, $waterwaySegments)
    {
        $bridgePassageCalculator = new BridgePassageCalculator();
        $bridgePassageCalculator->setBridges($bridges);
        $bridgePassageCalculator->setWaterwaySegments($waterwaySegments);
        return $bridgePassageCalculator;
    }

    public function loadArrayFromCsv($csv)
    {
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, $csv);
        rewind($fp);
        $headers = null;
        $records = array();
        while (($fields = fgetcsv($fp)) !== false) {
            if ($headers === null) {
                $headers = $fields;
            } else {

                $values = array();
                foreach ($headers as $i => $header) {

                    $values[$header] = array_key_exists($i, $fields) ? $fields[$i] : null;
                }
                $records[] = $values;
            }
        }
        return $records;
    }

    public function createWaterwaySegmentsFromRecords($records)
    {
        $waterwaySegments = array();
        foreach ($records as $values) {

            $segmentId = (int)$values['id'];

            $segment = new WaterwaySegment();
            $segment->setId($segmentId);

            $routePoints = array();
            if (isset($values['routepoints']) && $values['routepoints']) {
                $routePoints[] = new LatLng($values['routepoints']);
            }

            if (array_key_exists('route_points', $values) && ($values['route_points'])) {
                $lines = explode("\n", $values['route_points']);
                foreach ($lines as $line) {
                    $routePoints[] = new LatLng($line);
                }
            }

            $segment->setRoutePoints($routePoints);

            if (array_key_exists('coordinates', $values) && ($values['coordinates'])) {
                $lines = explode("\n", $values['coordinates']);
                $path = array();
                foreach ($lines as $line) {
                    $path[] = new LatLng($line);
                }
                $segment->setPolygon(new Polygon($path));
            }

            $connectedSegmentIds = array();

            if (isset($values['connections']) && $values['connections']) {

                foreach (explode(',', $values['connections']) as $connectedSegmentId) {
                    $connectedSegmentIds[] = (int)$connectedSegmentId;
                }
            }

            if (array_key_exists('connected_segments', $values) && ($values['connected_segments'])) {

                foreach (explode(',', $values['connected_segments']) as $connectedSegmentId) {
                    $connectedSegmentIds[] = (int)$connectedSegmentId;
                }
            }

            $segment->setConnectedSegmentIds($connectedSegmentIds);

            $waterwaySegments[$segmentId] = $segment;
        }
        return $waterwaySegments;
    }

    public function createWaterwaySegments()
    {

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

        $records = $this->loadArrayFromCsv(trim($csv));

        $waterwaySegments = $this->createWaterwaySegmentsFromRecords($records);

        return $waterwaySegments;
    }

    public function createPassageRecords()
    {
        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 559;
        $record['datetime_passage'] = new \DateTime('2024-05-26 10:23:14');
        $record['operation_id'] = 5531447;

        $records[] = $record;
        return $records;
    }

    public function createPassageService($passageRecords)
    {
        $tableManager = new MemoryTableManager();
        $tableManager->insertRecords('bo_bridge_passage', $passageRecords);

        $passageService = new PassageService();
        $passageService->setTableManager($tableManager);

        return $passageService;
    }

    public function testDetermineOperationProbabilityByBridgeBasedOnCurrentJourney()
    {

        $passageProjectionService = new PassageProjectionService();

        $passageService = $this->createPassageService($this->createPassageRecords());
        $bridgeService = $this->createBridgeService($this->createBridges());
        $passageProjectionService->setPassageService($passageService);
        $passageProjectionService->setBridgeService($bridgeService);

        $journey = new VesselJourney();
        $vessel = new Vessel();
        $vessel->setMmsi('12345678');
        $journey->setVessel($vessel);

        $bridgePassage = new BridgePassage();
        $bridgePassage->setBridgeId(512); // bridge 512 has clearance of 3.2
        $bridgePassage->setOperationId(5531447);

        $bridgePassages = array($bridgePassage);

        $journey->setPassages($bridgePassages);

        $minimumClearance = $passageProjectionService->determineMinimumRequiredClearanceFromJourney($journey);
        $this->assertNotNull($minimumClearance);
        $this->assertEquals(3.2, $minimumClearance);

        $datetimeProjectedPassage = new \DateTime('2024-06-01 14:45');

        $this->assertEquals(1, $passageProjectionService->determineOperationProbability($journey, 2, $datetimeProjectedPassage));
        $this->assertNull($passageProjectionService->determineOperationProbability($journey, 24, $datetimeProjectedPassage));
        $this->assertNull($passageProjectionService->determineOperationProbability($journey, 77, $datetimeProjectedPassage));
        $this->assertEquals(1, $passageProjectionService->determineOperationProbability($journey, 90, $datetimeProjectedPassage));
        $this->assertEquals(1, $passageProjectionService->determineOperationProbability($journey, 504, $datetimeProjectedPassage));
        $this->assertEquals(1, $passageProjectionService->determineOperationProbability($journey, 555, $datetimeProjectedPassage));
    }

    public function testDetermineMinimumRequiredClearanceFromJourneyNonexistingPassage()
    {

        $passageProjectionService = new PassageProjectionService();

        // use empty passage database
        $tableManager = new MemoryTableManager();
        $passageService = new PassageService();
        $passageService->setTableManager($tableManager);

        $passageProjectionService->setPassageService($passageService);

        $journey = new VesselJourney();

        $bridgePassage = new BridgePassage();
        $bridgePassage->setBridgeId(559); // bridge 559 has clearance of 4.62
        $bridgePassage->setOperationId(5531447);

        $minimumClearance = $passageProjectionService->determineMinimumRequiredClearanceFromJourney($journey);
        $this->assertNull($minimumClearance);
    }

    public function testDetermineMinimumRequiredClearanceFromJourneySinglePassage()
    {

        $passageProjectionService = new PassageProjectionService();

        $passageService = $this->createPassageService($this->createPassageRecords());
        $bridgeService = $this->createBridgeService($this->createBridges());
        $passageProjectionService->setPassageService($passageService);
        $passageProjectionService->setBridgeService($bridgeService);

        $journey = new VesselJourney();
        $vessel = new Vessel();
        $vessel->setMmsi('12345678');
        $journey->setVessel($vessel);

        $bridgePassage = new BridgePassage();
        $bridgePassage->setBridgeId(559); // bridge 559 has clearance of 4.62
        $bridgePassage->setOperationId(5531447);

        $bridgePassages = array($bridgePassage);

        $journey->setPassages($bridgePassages);

        $minimumClearance = $passageProjectionService->determineMinimumRequiredClearanceFromJourney($journey);
        $this->assertNotNull($minimumClearance);
        $this->assertEquals(4.62, $minimumClearance);
    }

    public function testDetermineMinimumRequiredClearanceFromJourneyMultiplePassages()
    {

        $passageProjectionService = new PassageProjectionService();

        $passageRecords = $this->createPassageRecords();
        // additional passage record

        $record = array();
        $record['mmsi'] = '23456789';
        $record['bridge_id'] = 559;
        $record['datetime_passage'] = new \DateTime('2024-05-26 10:24:14');
        $record['operation_id'] = 5531447;

        $passageRecords[] = $record;

        $passageService = $this->createPassageService($passageRecords);
        $bridgeService = $this->createBridgeService($this->createBridges());
        $passageProjectionService->setPassageService($passageService);
        $passageProjectionService->setBridgeService($bridgeService);

        $journey = new VesselJourney();
        $vessel = new Vessel();
        $vessel->setMmsi('12345678');
        $journey->setVessel($vessel);

        $bridgePassage = new BridgePassage();
        $bridgePassage->setBridgeId(559); // bridge 559 has clearance of 4.62
        $bridgePassage->setOperationId(5531447);

        $bridgePassages = array($bridgePassage);

        $journey->setPassages($bridgePassages);

        $minimumClearance = $passageProjectionService->determineMinimumRequiredClearanceFromJourney($journey);
        $this->assertNull($minimumClearance);
    }

    public function testBridgeHasClearance()
    {
        $passageProjectionService = new PassageProjectionService();

        $allBridges = $this->createBridges();
        $bridgeService = new BridgeService();
        $bridgeService->setAllBridges($allBridges);

        $passageProjectionService->setBridgeService($bridgeService);

        $this->assertNull($passageProjectionService->bridgeHasClearance(12345, 2.5));

        $this->assertTrue($passageProjectionService->bridgeHasClearance(2, 0.01));
        $this->assertTrue($passageProjectionService->bridgeHasClearance(2, 2.49));
        $this->assertFalse($passageProjectionService->bridgeHasClearance(2, 2.5));
        $this->assertFalse($passageProjectionService->bridgeHasClearance(2, 999));
    }

    public function testDetermineOperationProbabilityByPreviousPassagesNonexistingPassages()
    {

        $passageProjectionService = new PassageProjectionService();

        $passageService = $this->createPassageService($this->createPassageRecords());
        $bridgeService = $this->createBridgeService($this->createBridges());
        $passageProjectionService->setPassageService($passageService);
        $passageProjectionService->setBridgeService($bridgeService);

        $datetimeProjectedPassage = new \DateTime('2024-06-01 14:45');

        $this->assertNull($passageProjectionService->determineOperationProbabilityByPreviousPassages('99999999', 559, $datetimeProjectedPassage));
    }

    public function testDetermineOperationProbabilityByPreviousPassagesExistingSinglePassage()
    {

        $passageProjectionService = new PassageProjectionService();

        $passageService = $this->createPassageService($this->createPassageRecords());
        $bridgeService = $this->createBridgeService($this->createBridges());
        $passageProjectionService->setPassageService($passageService);
        $passageProjectionService->setBridgeService($bridgeService);

        $datetimeProjectedPassage = new \DateTime('2024-06-01 14:45');

        $this->assertEquals(1, $passageProjectionService->determineOperationProbabilityByPreviousPassages('12345678', 559, $datetimeProjectedPassage));
    }

    public function testDetermineOperationProbabilityByPreviousPassagesExistingMultiplePassages()
    {

        $passageProjectionService = new PassageProjectionService();

        $passageRecords = $this->createPassageRecords();
        // additional passage record

        $record = array();
        $record['mmsi'] = '23456789';
        $record['bridge_id'] = 559;
        $record['datetime_passage'] = new \DateTime('2024-05-26 10:24:14');
        $record['operation_id'] = 5531447;

        $passageRecords[] = $record;

        $passageService = $this->createPassageService($passageRecords);
        $bridgeService = $this->createBridgeService($this->createBridges());
        $passageProjectionService->setPassageService($passageService);
        $passageProjectionService->setBridgeService($bridgeService);

        $datetimeProjectedPassage = new \DateTime('2024-06-01 14:45');

        $this->assertNull($passageProjectionService->determineOperationProbabilityByPreviousPassages('12345678', 559, $datetimeProjectedPassage));
    }

    public function testCreatePassageProjectionsByCurrentJourney()
    {
        $bridges = $this->createBridges();
        $waterwaySegments = $this->createWaterwaySegments();
        $passageService = $this->createPassageService($this->createPassageRecords());
        $bridgeService = $this->createBridgeService($this->createBridges());
        $journeyReconstructor = $this->createJourneyReconstructor($waterwaySegments);
        $journeyProjector = $this->createJourneyProjector($waterwaySegments);
        $bridgePassageCalculator = $this->createBridgePassageCalculator($bridges, $waterwaySegments);

        $passageProjectionService = new PassageProjectionService();

        $passageProjectionService->setPassageService($passageService);
        $passageProjectionService->setBridgeService($bridgeService);
        $passageProjectionService->setJourneyReconstructor($journeyReconstructor);
        $passageProjectionService->setJourneyProjector($journeyProjector);
        $passageProjectionService->setBridgePassageCalculator($bridgePassageCalculator);

        $logger = new \Monolog\Logger('PassageProjectionService');
        $logger->setHandlers(array(new NullHandler()));
        $passageProjectionService->setLog($logger);

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
	],
    "passages": [
        {
            "bridgeId": 559,
            "timestamp": 1716711794,
            "direction": 22,
            "operationId": 5531447
        },
        {
            "bridgeId": 504,
            "timestamp": 1716718636,
            "direction": 9,
            "operationId": 5532149
        },
        {
            "bridgeId": 512,
            "timestamp": 1716719032,
            "direction": 12,
            "operationId": 5532187
        }
    ]
}
JSON;

        $journeyDataService = new JourneyDataService();
        $journey = $journeyDataService->parseJourney(json_decode($journeyJson, true));

        // bridge 512 has clearance of 3.2

        $projectedPassages = $passageProjectionService->createPassageProjections($journey);

        $this->assertNotEmpty($projectedPassages);
        $this->assertCount(4, $projectedPassages);

        $lastTimestamp = 1716719724;

        $this->assertEquals(1528, $projectedPassages[0]->getDatetimeProjectedPassage()->getTimestamp() - $lastTimestamp);
        $this->assertEquals(116, $projectedPassages[0]->getStandardDeviation());
        $this->assertEquals(null, $projectedPassages[0]->getOperationProbability());

        $this->assertEquals(2538, $projectedPassages[1]->getDatetimeProjectedPassage()->getTimestamp() - $lastTimestamp);
        $this->assertEquals(192, $projectedPassages[1]->getStandardDeviation());
        $this->assertEquals(1, $projectedPassages[1]->getOperationProbability());

        $this->assertEquals(4201, $projectedPassages[2]->getDatetimeProjectedPassage()->getTimestamp() - $lastTimestamp);
        $this->assertEquals(318, $projectedPassages[2]->getStandardDeviation());
        $this->assertEquals(1, $projectedPassages[2]->getOperationProbability());

        $this->assertEquals(6409, $projectedPassages[3]->getDatetimeProjectedPassage()->getTimestamp() - $lastTimestamp);
        $this->assertEquals(486, $projectedPassages[3]->getStandardDeviation());
        $this->assertEquals(1, $projectedPassages[3]->getOperationProbability());
    }

    public function testCreatePassageProjectionsByCurrentJourneyEndOfWaterway()
    {
        $bridges = $this->createBridges();

        $journeyJson = <<<JSON
        {
            "id": "12345678-1717355490",
            "mmsi": "12345678",
            "segments": [
                {
                    "segmentId": 374,
                    "firstTimestamp": 1717355490,
                    "firstLocation": "52.384181666667,4.8821666666667",
                    "lastTimestamp": 1717355490,
                    "lastLocation": "52.384181666667,4.8821666666667"
                },
                {
                    "segmentId": 375,
                    "firstTimestamp": 1717355570,
                    "firstLocation": "52.385476666667,4.882695",
                    "lastTimestamp": 1717355570,
                    "lastLocation": "52.385476666667,4.882695"
                },
                {
                    "segmentId": 376,
                    "firstTimestamp": 1717355620,
                    "firstLocation": "52.386415,4.88341",
                    "lastTimestamp": 1717355740,
                    "lastLocation": "52.388918333333,4.8848616666667"
                },
                {
                    "segmentId": 377,
                    "firstTimestamp": 1717355870,
                    "firstLocation": "52.39103,4.886215",
                    "lastTimestamp": 1717356201,
                    "lastLocation": "52.390658333333,4.8859583333333"
                }
            ],
            "passages": [
                {
                    "bridgeId": 167,
                    "timestamp": 1717355532,
                    "direction": 17
                },
                {
                    "bridgeId": 142,
                    "timestamp": 1717355596,
                    "direction": 29
                },
                {
                    "bridgeId": 166,
                    "timestamp": 1717355828,
                    "direction": 23
                }
            ]
        }
JSON;

        $segmentsCsv = <<<CSV
        "id","title","coordinates","connected_segments","route_points"
        "374",,"52.381624,4.880268
        52.381388,4.879526
        52.381505,4.879105
        52.382051,4.878952
        52.382722,4.880345
        52.384176,4.881156
        52.384975,4.881831
        52.384838,4.882544
        52.3841,4.882849
        52.383731,4.8824
        52.383757,4.881946
        52.382068,4.880843","373,375","52.383000445749,4.8809635987839"
        "375",,"52.385551,4.881489
        52.384975,4.881831
        52.384838,4.882544
        52.385217,4.883257
        52.385864,4.883394
        52.386092,4.882728
        52.385895,4.881579","374,376","52.385533353996,4.8825124950694"
        "376",,"52.388072,4.884877
        52.386333,4.883803
        52.385864,4.883394
        52.386092,4.882728
        52.386558,4.882961
        52.388285,4.884007
        52.388656,4.884152
        52.389111,4.884439
        52.389593,4.884669
        52.390574,4.885167
        52.390369,4.885944
        52.388564,4.885223
        52.388379,4.885791
        52.387932,4.8856","375,377","52.388161769828,4.8844217001511"
        "377","IJ Amsterdam noordzijde Westerkeersluis","52.391793,4.8855
        52.390574,4.885167
        52.390369,4.885944
        52.392218,4.8876","376","52.391295031083,4.8859655517288"
CSV;

        $records = $this->loadArrayFromCsv(trim($segmentsCsv));
        $waterwaySegments = $this->createWaterwaySegmentsFromRecords($records);

        $passageService = $this->createPassageService($this->createPassageRecords());
        $bridgeService = $this->createBridgeService($this->createBridges());
        $journeyReconstructor = $this->createJourneyReconstructor($waterwaySegments);
        $journeyProjector = $this->createJourneyProjector($waterwaySegments);
        $bridgePassageCalculator = $this->createBridgePassageCalculator($bridges, $waterwaySegments);

        $passageProjectionService = new PassageProjectionService();

        $passageProjectionService->setPassageService($passageService);
        $passageProjectionService->setBridgeService($bridgeService);
        $passageProjectionService->setJourneyReconstructor($journeyReconstructor);
        $passageProjectionService->setJourneyProjector($journeyProjector);
        $passageProjectionService->setBridgePassageCalculator($bridgePassageCalculator);

        $logger = new \Monolog\Logger('PassageProjectionService');
        $logger->setHandlers(array(new NullHandler()));
        $passageProjectionService->setLog($logger);

        $journeyDataService = new JourneyDataService();
        $journey = $journeyDataService->parseJourney(json_decode($journeyJson, true));

        $projectedPassages = $passageProjectionService->createPassageProjections($journey);

        $this->assertEmpty($projectedPassages);
    }
}
