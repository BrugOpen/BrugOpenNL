<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Core\TestEventDispatcher;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Model\Bridge;
use BrugOpen\Projection\Model\ProjectedBridgePassage;
use Monolog\Handler\NullHandler;
use PHPUnit\Framework\TestCase;

class OperationProjectionServiceTest extends TestCase
{

    public function testCreateOperationProjectionsSinglePassage()
    {

        $bridge = new Bridge();

        $now = time();

        $maxStandardDeviation = 90;
        $minOperationProbability = 0.5;
        $maxDateTimePassage = new \DateTime('@' . ($now + (30 * 60)));

        // projected passage in 400 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (400)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections = array($passageProjection);

        $operationProjectionService = new OperationProjectionService();

        $operationProjections = $operationProjectionService->createOperationProjections($bridge, $passageProjections, $maxStandardDeviation, $minOperationProbability, $maxDateTimePassage);

        $this->assertCount(1, $operationProjections);

        $operationProjection = $operationProjections[0];

        $this->assertEquals($now + 400 - 120, $operationProjection->getTimeStart()->getTimestamp());
        $this->assertEquals($now + 400 + 120, $operationProjection->getTimeEnd()->getTimestamp());

        $duration = $operationProjection->getTimeEnd()->getTimestamp() - $operationProjection->getTimeStart()->getTimestamp();
        $this->assertEquals(240, $duration);

        $this->assertEquals(2, $operationProjection->getCertainty());

        $projectedBridgePassages = $operationProjection->getProjectedPassages();
        $this->assertNotEmpty($projectedBridgePassages);
        $this->assertCount(1, $projectedBridgePassages);
        $this->assertEquals($passageProjection, $projectedBridgePassages[0]);
    }

    public function testCreateOperationProjectionsTwoCombinedPassages()
    {

        $bridge = new Bridge();

        $passageProjections = array();

        $now = time();

        $maxStandardDeviation = 90;
        $minOperationProbability = 0.5;
        $maxDateTimePassage = new \DateTime('@' . ($now + (30 * 60)));

        // first projected passage in 400 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (400)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setId(123);
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections[] = $passageProjection;

        // second projected passage in 550 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (550)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setId(234);
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections[] = $passageProjection;

        $operationProjectionService = new OperationProjectionService();
        $operationProjections = $operationProjectionService->createOperationProjections($bridge, $passageProjections, $maxStandardDeviation, $minOperationProbability, $maxDateTimePassage);

        $this->assertCount(1, $operationProjections);

        $operationProjection = $operationProjections[0];

        // first ship must wait for second ship
        // start time is the second passage time minus the normal operation duration
        $this->assertEquals($now + 550 - 240, $operationProjection->getTimeStart()->getTimestamp());

        // end time is the second passage time plus half of the normal operation duration
        $this->assertEquals($now + 550 + (240 / 2), $operationProjection->getTimeEnd()->getTimestamp());

        $duration = $operationProjection->getTimeEnd()->getTimestamp() - $operationProjection->getTimeStart()->getTimestamp();
        $this->assertEquals(240 * 1.5, $duration);

        $this->assertEquals(2, $operationProjection->getCertainty());

        $projectedBridgePassages = $operationProjection->getProjectedPassages();
        $this->assertNotEmpty($projectedBridgePassages);
        $this->assertCount(2, $projectedBridgePassages);
        $this->assertEquals(123, $projectedBridgePassages[0]->getId());
        $this->assertEquals(234, $projectedBridgePassages[1]->getId());
    }

    public function testCreateOperationProjectionsTwoSeparatePassages()
    {

        $bridge = new Bridge();

        $passageProjections = array();

        $now = time();

        $maxStandardDeviation = 90;
        $minOperationProbability = 0.5;
        $maxDateTimePassage = new \DateTime('@' . ($now + (30 * 60)));

        // first projected passage in 400 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (400)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setId(123);
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections[] = $passageProjection;

        // second projected passage in 1000 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (1000)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setId(234);
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections[] = $passageProjection;

        $operationProjectionService = new OperationProjectionService();
        $operationProjections = $operationProjectionService->createOperationProjections($bridge, $passageProjections, $maxStandardDeviation, $minOperationProbability, $maxDateTimePassage);

        $this->assertCount(2, $operationProjections);

        // check first operation projection

        $operationProjection = $operationProjections[0];

        // start time is the second passage time minus half the normal operation duration
        $this->assertEquals($now + 400 - (240 / 2), $operationProjection->getTimeStart()->getTimestamp());

        // end time is the second passage time plus half of the normal operation duration
        $this->assertEquals($now + 400 + (240 / 2), $operationProjection->getTimeEnd()->getTimestamp());

        $duration = $operationProjection->getTimeEnd()->getTimestamp() - $operationProjection->getTimeStart()->getTimestamp();
        $this->assertEquals(240, $duration);

        $this->assertEquals(2, $operationProjection->getCertainty());

        $projectedBridgePassages = $operationProjection->getProjectedPassages();
        $this->assertNotEmpty($projectedBridgePassages);
        $this->assertCount(1, $projectedBridgePassages);
        $this->assertEquals(123, $projectedBridgePassages[0]->getId());

        // check second operation projection

        $operationProjection = $operationProjections[1];

        // start time is the second passage time minus half the normal operation duration
        $this->assertEquals($now + 1000 - (240 / 2), $operationProjection->getTimeStart()->getTimestamp());

        // end time is the second passage time plus half of the normal operation duration
        $this->assertEquals($now + 1000 + (240 / 2), $operationProjection->getTimeEnd()->getTimestamp());

        $duration = $operationProjection->getTimeEnd()->getTimestamp() - $operationProjection->getTimeStart()->getTimestamp();
        $this->assertEquals(240, $duration);

        $this->assertEquals(2, $operationProjection->getCertainty());

        $projectedBridgePassages = $operationProjection->getProjectedPassages();
        $this->assertNotEmpty($projectedBridgePassages);
        $this->assertCount(1, $projectedBridgePassages);
        $this->assertEquals(234, $projectedBridgePassages[0]->getId());
    }

    public function testUpdateOperationProjectionsFirstProjectionVersion()
    {

        $operationProjectionService = new OperationProjectionService();

        // set table manager
        $tableManager = new MemoryTableManager();
        $operationProjectionService->setTableManager($tableManager);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $operationProjectionService->setEventDispatcher($eventDispatcher);

        // set projected passage data store
        $projectedPassageDataStore = new ProjectedPassageDataStore();
        $projectedPassageDataStore->setTableManager($tableManager);
        $operationProjectionService->setProjectedPassageDataStore($projectedPassageDataStore);

        // set null logger
        $logger = new \Monolog\Logger('OperationProjectionEventProcessor');
        $logger->setHandlers(array(new NullHandler()));
        $operationProjectionService->setLog($logger);

        $record = [];
        $record['id'] = 9;
        $record['project_operations'] = 1;
        $record['isrs_code'] = 'NLAMB002120533600182';

        $tableManager->insertRecord('bo_bridge', $record);

        // create passage projection
        $record = [];
        $record['id'] = 452440;
        $record['journey_id'] = '234567890-1726502736';
        $record['bridge_id'] = 9;
        $record['datetime_passage'] = new \DateTime('2024-09-16 21:12:51');
        $record['standard_deviation'] = 44;
        $record['operation_probability'] = 0.875;
        $record['event_id'] = null;
        $record['datetime_projection'] = new \DateTime('2024-09-16 20:43:01');

        $tableManager->insertRecord('bo_passage_projection', $record);

        // create previous operation projection
        $record = [];
        $record['id'] = 37386;
        $record['event_id'] = 'BONL01_NLSPL002120533400126_37349';
        $record['version'] = 12;
        $record['operation_id'] = 5986870;
        $record['bridge_id'] = 8;
        $record['certainty'] = 2;
        $record['time_start'] = new \DateTime('2024-09-16 20:21:12');
        $record['time_end'] = new \DateTime('2024-09-16 20:25:12');
        $record['datetime_projection'] = new \DateTime('2024-09-16 20:23:10');

        $tableManager->insertRecord('bo_operation_projection', $record);

        $tableManager->setAutoIncrement('bo_operation_projection', 'id', 37387);

        $datetimeProjection = new \DateTime('2024-09-16 20:43:02');

        $operationProjectionService->updateOperationProjections($datetimeProjection);

        // check if operation projection is created
        $operationProjections = $tableManager->findRecords('bo_operation_projection', ['id' => 37387]);
        $this->assertCount(1, $operationProjections);

        $operationProjection = $operationProjections[0];
        $this->assertEquals(37387, $operationProjection['id']);
        $this->assertEquals('BONL01_NLAMB002120533600182_37387', $operationProjection['event_id']);
        $this->assertEquals(1, $operationProjection['version']);
        $this->assertEquals(null, $operationProjection['operation_id']);
        $this->assertEquals(9, $operationProjection['bridge_id']);
        $this->assertEquals(2, $operationProjection['certainty']);
        $this->assertEquals(1726513851, $operationProjection['time_start']->getTimestamp());
        $this->assertEquals(1726514091, $operationProjection['time_end']->getTimestamp());
        $this->assertEquals(1726512182, $operationProjection['datetime_projection']->getTimestamp());
    }

    public function testUpdateOperationProjectionsSecondProjectionVersion()
    {

        $operationProjectionService = new OperationProjectionService();

        // set table manager
        $tableManager = new MemoryTableManager();
        $operationProjectionService->setTableManager($tableManager);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $operationProjectionService->setEventDispatcher($eventDispatcher);

        // set projected passage data store
        $projectedPassageDataStore = new ProjectedPassageDataStore();
        $projectedPassageDataStore->setTableManager($tableManager);
        $operationProjectionService->setProjectedPassageDataStore($projectedPassageDataStore);

        // set null logger
        $logger = new \Monolog\Logger('OperationProjectionEventProcessor');
        $logger->setHandlers(array(new NullHandler()));
        $operationProjectionService->setLog($logger);

        $record = [];
        $record['id'] = 9;
        $record['project_operations'] = 1;
        $record['isrs_code'] = 'NLAMB002120533600182';
        $record['title'] = 'Aalsmeerderbrug';
        $record['active'] = 1;
        $record['city'] = 'Aalsmeerderbrug';
        $record['city2'] = null;

        $tableManager->insertRecord('bo_bridge', $record);

        // create operation
        $record = [];
        $record['id'] = 5986953;
        $record['event_id'] = 'BONL01_NLAMB002120533600182_37387';
        $record['bridge_id'] = 9;
        $record['certainty'] = 2;
        $record['time_start'] = new \DateTime('2024-09-16 21:10:51');
        $record['time_end'] = new \DateTime('2024-09-16 21:14:51');

        $tableManager->insertRecord('bo_operation', $record);

        // create passage projection
        $record = [];
        $record['id'] = 452446;
        $record['journey_id'] = '234567890-1726502736';
        $record['bridge_id'] = 9;
        $record['datetime_passage'] = new \DateTime('2024-09-16 21:12:56');
        $record['standard_deviation'] = 37;
        $record['operation_probability'] = 0.875;
        $record['event_id'] = null;
        $record['datetime_projection'] = new \DateTime('2024-09-16 20:44:01');

        $tableManager->insertRecord('bo_passage_projection', $record);

        // create previous operation projection
        $record = [];
        $record['id'] = 37387;
        $record['event_id'] = 'BONL01_NLAMB002120533600182_37387';
        $record['version'] = 1;
        $record['operation_id'] = 5986953;
        $record['bridge_id'] = 9;
        $record['certainty'] = 2;
        $record['time_start'] = new \DateTime('2024-09-16 21:10:51');
        $record['time_end'] = new \DateTime('2024-09-16 21:14:51');
        $record['datetime_projection'] = new \DateTime('2024-09-16 20:43:09');

        $tableManager->insertRecord('bo_operation_projection', $record);

        $tableManager->setAutoIncrement('bo_operation_projection', 'id', 37388);

        $datetimeProjection = new \DateTime('2024-09-16 20:44:02');

        $operationProjectionService->updateOperationProjections($datetimeProjection);

        // check if operation projection is created
        $operationProjections = $tableManager->findRecords('bo_operation_projection', ['id' => 37388]);
        $this->assertCount(1, $operationProjections);

        $operationProjection = $operationProjections[0];
        $this->assertEquals(37388, $operationProjection['id']);
        $this->assertEquals('BONL01_NLAMB002120533600182_37387', $operationProjection['event_id']);
        $this->assertEquals(2, $operationProjection['version']);
        $this->assertEquals(null, $operationProjection['operation_id']);
        $this->assertEquals(9, $operationProjection['bridge_id']);
        $this->assertEquals(2, $operationProjection['certainty']);
        $this->assertEquals(1726513856, $operationProjection['time_start']->getTimestamp());
        $this->assertEquals(1726514096, $operationProjection['time_end']->getTimestamp());
        $this->assertEquals(1726512242, $operationProjection['datetime_projection']->getTimestamp());
    }
}
