<?php
use BrugOpen\Core\TestEventDispatcher;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Ndw\Service\SituationEventProcessor;
use BrugOpen\Service\BridgeIndexService;
use BrugOpen\Service\BridgeService;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class SituationEventProcessorTest extends TestCase
{

    public function testGetCertaintyRiskOf()
    {

        $situationEventProcessor = new SituationEventProcessor(null);

        $situations = array();

        $situation = array();
        $situation['probability'] = 'riskOf';

        $situations[] = $situation;

        $this->assertEquals(1, $situationEventProcessor->getCertainty($situations));

        // now with two situations
        $situations = array();

        $situation = array();
        $situation['probability'] = 'certain';

        $situations[] = $situation;

        $situation = array();
        $situation['probability'] = 'riskOf';

        $situations[] = $situation;

        $this->assertEquals(1, $situationEventProcessor->getCertainty($situations));

    }

    public function testGetCertaintyProbable()
    {

        $situationEventProcessor = new SituationEventProcessor(null);

        $situations = array();

        $situation = array();
        $situation['probability'] = 'probable';

        $situations[] = $situation;

        $this->assertEquals(2, $situationEventProcessor->getCertainty($situations));

        // now with two situations
        $situations = array();

        $situation = array();
        $situation['probability'] = 'riskOf';

        $situations[] = $situation;

        $situation = array();
        $situation['probability'] = 'probable';

        $situations[] = $situation;

        $this->assertEquals(2, $situationEventProcessor->getCertainty($situations));

    }

    public function testGetCertaintyCertain()
    {

        $situationEventProcessor = new SituationEventProcessor(null);

        $situations = array();

        $situation = array();
        $situation['probability'] = 'certain';

        $situations[] = $situation;

        $this->assertEquals(3, $situationEventProcessor->getCertainty($situations));

        // now with two situations
        $situations = array();

        $situation = array();
        $situation['probability'] = 'probable';

        $situations[] = $situation;

        $situation = array();
        $situation['probability'] = 'certain';

        $situations[] = $situation;

        $this->assertEquals(3, $situationEventProcessor->getCertainty($situations));

    }

    public function testGetCertaintyCancelled()
    {

        $situationEventProcessor = new SituationEventProcessor(null);

        $situations = array();

        $situation = array();
        $situation['probability'] = 'certain';
        $situation['status'] = 'cancelled';

        $situations[] = $situation;

        $this->assertEquals(0, $situationEventProcessor->getCertainty($situations));

        // now with two situations
        $situations = array();

        $situation = array();
        $situation['probability'] = 'probable';

        $situations[] = $situation;

        $situation = array();
        $situation['probability'] = 'certain';
        $situation['status'] = 'cancelled';

        $situations[] = $situation;

        $this->assertEquals(0, $situationEventProcessor->getCertainty($situations));

    }

    public function testIgnoreRiskOfSituation()
    {
        $log = new \Monolog\Logger('SituationEventProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setLog($log);

        $record = array();
        $record['id'] = 'NDW01_123';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['status'] = 'approved';
        $record['probability'] = 'riskOf';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');

        $tableManager->insertRecord('bo_situation', $record);

        $situationEventProcessor->onSituationUpdate('NDW01_123');

        // assert no operation inserted
        $operations = $tableManager->findRecords('bo_operation');
        $this->assertCount(0, $operations);

        // assert no events posted
        $events = $eventDispatcher->getPostedEvents();
        $this->assertCount(0, $events);

    }

    public function testIgnoreProbableSituation()
    {
        $log = new \Monolog\Logger('SituationEventProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setLog($log);

        $record = array();
        $record['id'] = 'NDW01_123';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['status'] = 'approved';
        $record['probability'] = 'probable';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');

        $tableManager->insertRecord('bo_situation', $record);

        $situationEventProcessor->onSituationUpdate('NDW01_123');

        // assert no operation inserted
        $operations = $tableManager->findRecords('bo_operation');
        $this->assertCount(0, $operations);

        // assert no events posted
        $events = $eventDispatcher->getPostedEvents();
        $this->assertCount(0, $events);

    }

    public function testIgnoreCancelledSituation()
    {
        $log = new \Monolog\Logger('SituationEventProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setLog($log);

        $record = array();
        $record['id'] = 'NDW01_123';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['status'] = 'cancelled';
        $record['probability'] = 'certain';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');

        $tableManager->insertRecord('bo_situation', $record);

        $situationEventProcessor->onSituationUpdate('NDW01_123');

        // assert no operation inserted
        $operations = $tableManager->findRecords('bo_operation');
        $this->assertCount(0, $operations);

        // assert no events posted
        $events = $eventDispatcher->getPostedEvents();
        $this->assertCount(0, $events);

    }

    public function testInsertCertainSituationNoBridge()
    {
        $log = new \Monolog\Logger('SituationEventProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_operation', 'id', 101);
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setLog($log);

        $record = array();
        $record['id'] = 'NDW01_123';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['status'] = 'approved';
        $record['probability'] = 'certain';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');

        $tableManager->insertRecord('bo_situation', $record);

        $situationEventProcessor->onSituationUpdate('NDW01_123');

        // assert operation inserted
        $operations = $tableManager->findRecords('bo_operation');
        $this->assertCount(1, $operations);

        $this->assertEquals(101, $operations[0]['id']);

        // assert operation event posted
        $events = $eventDispatcher->getPostedEvents();
        $this->assertCount(1, $events);

        $this->assertEquals('Operation.update', $events[0]['name']);

        $this->assertCount(1, $events[0]['params']);

        $this->assertEquals(101, $events[0]['params'][0]);

    }

    public function testInsertCertainSituationExistingBridgeByNwdId()
    {
        $log = new \Monolog\Logger('SituationEventProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_operation', 'id', 101);
        $bridgeIndexService = new BridgeIndexService();
        $bridgeIndexService->setTableManager($tableManager);
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setBridgeIndexService($bridgeIndexService);
        $situationEventProcessor->setLog($log);

        $record = array();
        $record['id'] = 'NDW01_123';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['status'] = 'approved';
        $record['probability'] = 'certain';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');
        $record['location'] = 123;

        $tableManager->insertRecord('bo_situation', $record);

        $record = array();
        $record['id'] = 12;
        $record['name'] = 'some-bridge';
        $record['ndw_id'] = 123;

        $tableManager->insertRecord('bo_bridge', $record);

        $situationEventProcessor->onSituationUpdate('NDW01_123');

        // assert operation inserted
        $operations = $tableManager->findRecords('bo_operation');
        $this->assertCount(1, $operations);

        $this->assertEquals(101, $operations[0]['id']);

        $this->assertEquals(12, $operations[0]['bridge_id']);

        // assert operation event posted
        $events = $eventDispatcher->getPostedEvents();
        $this->assertCount(1, $events);

        $this->assertEquals('Operation.update', $events[0]['name']);

        $this->assertCount(1, $events[0]['params']);

        $this->assertEquals(101, $events[0]['params'][0]);

    }

    public function testInsertCertainSituationExistingBridgeByIsrs()
    {
        $log = new \Monolog\Logger('SituationEventProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_operation', 'id', 101);
        $bridgeIndexService = new BridgeIndexService();
        $bridgeIndexService->setTableManager($tableManager);
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setBridgeIndexService($bridgeIndexService);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setLog($log);

        $record = array();
        $record['id'] = 'PZH02_NLLID002010538900173_56639066';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['status'] = 'approved';
        $record['probability'] = 'certain';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');

        $tableManager->insertRecord('bo_situation', $record);

        $record = array();
        $record['id'] = 12;
        $record['name'] = 'some-bridge';

        $tableManager->insertRecord('bo_bridge', $record);

        $record = array();
        $record['id'] = 1;
        $record['bridge_id'] = 12;
        $record['isrs_code'] = 'NLLID002010538900173';

        $tableManager->insertRecord('bo_bridge_isrs', $record);

        $situationEventProcessor->onSituationUpdate('PZH02_NLLID002010538900173_56639066');

        // assert operation inserted
        $operations = $tableManager->findRecords('bo_operation');
        $this->assertCount(1, $operations);

        $this->assertEquals(101, $operations[0]['id']);

        $this->assertEquals(12, $operations[0]['bridge_id']);

        // assert operation event posted
        $events = $eventDispatcher->getPostedEvents();
        $this->assertCount(1, $events);

        $this->assertEquals('Operation.update', $events[0]['name']);

        $this->assertCount(1, $events[0]['params']);

        $this->assertEquals(101, $events[0]['params'][0]);

    }

    public function testInsertCertainSituationInsertNewBridgeWithNdwId()
    {
        $log = new \Monolog\Logger('SituationEventProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_bridge', 'id', 11);
        $tableManager->setAutoIncrement('bo_operation', 'id', 101);
        $bridgeIndexService = new BridgeIndexService();
        $bridgeIndexService->setTableManager($tableManager);
        $bridgeService = new BridgeService();
        $bridgeService->setTableManager($tableManager);
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setBridgeIndexService($bridgeIndexService);
        $situationEventProcessor->setBridgeService($bridgeService);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setLog($log);

        $record = array();
        $record['id'] = 'NDW01_123';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['status'] = 'approved';
        $record['probability'] = 'certain';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');
        $record['location'] = 123;
        $record['lat'] = 52.140663;
        $record['lng'] = 4.4873033;

        $tableManager->insertRecord('bo_situation', $record);

        $situationEventProcessor->onSituationUpdate('NDW01_123');

        // assert operation inserted
        $operations = $tableManager->findRecords('bo_operation');
        $this->assertCount(1, $operations);

        $this->assertEquals(101, $operations[0]['id']);

        // assert bridge_id in inserted operation
        $this->assertEquals(11, $operations[0]['bridge_id']);

        // assert bridge inserted

        $insertedBridge = $tableManager->findRecord('bo_bridge', array('id' => 11));

        $this->assertNotNull($insertedBridge);
        $this->assertEquals(11, $insertedBridge['id']);
        $this->assertEquals(123, $insertedBridge['ndw_id']);
        $this->assertEquals(52.140663, $insertedBridge['ndw_lat']);
        $this->assertEquals(4.4873033, $insertedBridge['ndw_lng']);


        $events = $eventDispatcher->getPostedEvents();
        $this->assertCount(2, $events);

        // assert bridge event posted

        $this->assertEquals('Bridge.insert', $events[0]['name']);

        $this->assertCount(1, $events[0]['params']);

        $this->assertEquals(11, $events[0]['params'][0]);
        
        // assert operation event posted

        $this->assertEquals('Operation.update', $events[1]['name']);

        $this->assertCount(1, $events[1]['params']);

        $this->assertEquals(101, $events[1]['params'][0]);

    }

    public function testInsertCertainSituationInsertNewBridgeWithIsrs()
    {
        $log = new \Monolog\Logger('SituationEventProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_bridge', 'id', 11);
        $tableManager->setAutoIncrement('bo_operation', 'id', 101);
        $bridgeIndexService = new BridgeIndexService();
        $bridgeIndexService->setTableManager($tableManager);
        $bridgeService = new BridgeService();
        $bridgeService->setTableManager($tableManager);
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setBridgeIndexService($bridgeIndexService);
        $situationEventProcessor->setBridgeService($bridgeService);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setLog($log);

        $record = array();
        $record['id'] = 'PZH02_NLLID002010538900173_56639066';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['status'] = 'approved';
        $record['probability'] = 'certain';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');
        $record['location'] = null;
        $record['lat'] = 52.140663;
        $record['lng'] = 4.4873033;

        $tableManager->insertRecord('bo_situation', $record);

        // assert bridge index empty
        $bridgeIdByIsrs = $bridgeIndexService->getBridgeIdByIsrs('NLLID002010538900173');
        $this->assertNull($bridgeIdByIsrs);

        $situationEventProcessor->onSituationUpdate('PZH02_NLLID002010538900173_56639066');

        // assert operation inserted
        $operations = $tableManager->findRecords('bo_operation');
        $this->assertCount(1, $operations);

        $this->assertEquals(101, $operations[0]['id']);

        // assert bridge_id in inserted operation
        $this->assertEquals(11, $operations[0]['bridge_id']);

        // assert bridge inserted

        $insertedBridge = $tableManager->findRecord('bo_bridge', array('id' => 11));

        $this->assertNotNull($insertedBridge);
        $this->assertEquals(11, $insertedBridge['id']);
        $this->assertEquals(52.140663, $insertedBridge['ndw_lat']);
        $this->assertEquals(4.4873033, $insertedBridge['ndw_lng']);
        $this->assertEquals('NLLID002010538900173', $insertedBridge['isrs_code']);

        // assert bridge index updated

        $bridgeIdByIsrs = $bridgeIndexService->getBridgeIdByIsrs('NLLID002010538900173');
        $this->assertEquals(11, $bridgeIdByIsrs);

        // assert posted events
        $events = $eventDispatcher->getPostedEvents();
        $this->assertCount(2, $events);

        // assert bridge event posted

        $this->assertEquals('Bridge.insert', $events[0]['name']);

        $this->assertCount(1, $events[0]['params']);

        $this->assertEquals(11, $events[0]['params'][0]);
        
        // assert operation event posted

        $this->assertEquals('Operation.update', $events[1]['name']);

        $this->assertCount(1, $events[1]['params']);

        $this->assertEquals(101, $events[1]['params'][0]);

    }

    public function testUpdateExistingOperation()
    {
        $log = new \Monolog\Logger('SituationEventProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setLog($log);

        $record = array();
        $record['id'] = 'NDW01_123';
        $record['version'] = 1;
        $record['operation_id'] = 123;
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');

        $tableManager->insertRecord('bo_situation', $record);

        $record = array();
        $record['id'] = 'NDW01_234';
        $record['version'] = 1;
        $record['operation_id'] = 234;
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = null;
        
        $tableManager->insertRecord('bo_situation', $record);

        $record = array();
        $record['id'] = 'NDW01_234';
        $record['version'] = 2;
        $record['operation_id'] = 234;
        $record['time_start'] = new \DateTime('@1652286015');
        $record['time_end'] = new \DateTime('@1652286315');
        
        $tableManager->insertRecord('bo_situation', $record);

        $record = array();
        $record['id'] = 123;
        $record['event_id'] = 'NDW01_123';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = new \DateTime('@1652286252');

        $tableManager->insertRecord('bo_operation', $record);

        $record = array();
        $record['id'] = 234;
        $record['event_id'] = 'NDW01_234';
        $record['time_start'] = new \DateTime('@1652286012');
        $record['time_end'] = null;

        $tableManager->insertRecord('bo_operation', $record);

        $situationEventProcessor->onSituationUpdate('NDW01_234');

        // assert operation updated
        $criteria = array();
        $criteria['id'] = 234;
        $record = $tableManager->findRecord('bo_operation', $criteria);
        $this->assertNotNull($record);

        $this->assertEquals(234, $record['id']);
        $this->assertEquals(1652286015, $record['datetime_start']);
        $this->assertEquals(1652286015, $record['time_start']->getTimestamp());
        $this->assertEquals(1652286315, $record['datetime_end']);
        $this->assertEquals(1652286315, $record['time_end']->getTimestamp());

        // assert operation event posted
        $events = $eventDispatcher->getPostedEvents();
        $this->assertCount(1, $events);

        $this->assertEquals('Operation.update', $events[0]['name']);

        $this->assertCount(1, $events[0]['params']);

        $this->assertEquals(234, $events[0]['params'][0]);

    }

}
