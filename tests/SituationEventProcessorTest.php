<?php
use BrugOpen\Core\TestEventDispatcher;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Ndw\Service\SituationEventProcessor;
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
