<?php

namespace BrugOpen\Ndw\Service;

use BrugOpen\Core\TestEventDispatcher;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Service\BridgeIndexService;
use Monolog\Handler\NullHandler;
use PHPUnit\Framework\TestCase;

class SituationEventProcessorTest extends TestCase
{

    public function createBridgeIndexService()
    {

        $tableManager = new MemoryTableManager();

        $tableManager->insertRecord('bo_bridge_isrs', ['bridge_id' => 1, 'isrs_code' => 'NLLID002010538900173']);
        $tableManager->insertRecord('bo_bridge_isrs', ['bridge_id' => 90, 'isrs_code' => 'NLGOU002700535500051']);

        $bridgeIndexService = new BridgeIndexService();
        $bridgeIndexService->setTableManager($tableManager);

        return $bridgeIndexService;
    }

    public function testOnSituationUpdateMatchingProjectedOperation()
    {

        $situationEventProcessor = new SituationEventProcessor(null);

        // set null logger
        $logger = new \Monolog\Logger('SituationEventProcessor');
        $logger->setHandlers(array(new NullHandler()));
        $situationEventProcessor->setLog($logger);

        // add table manager
        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_operation', 'id', 100);

        // add two operations in database

        $values = [];
        $values['id'] = 98;
        $values['event_id'] = 'BONL01_NLGOU002700535500051_1234';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['time_start'] = new \DateTime('2023-01-30 10:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 10:40:00');

        $tableManager->insertRecord('bo_operation', $values);

        $values = [];
        $values['id'] = 99;
        $values['event_id'] = 'BONL01_NLGOU002700535500051_2345';
        $values['version'] = 2;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['time_start'] = new \DateTime('2023-01-30 12:31:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:41:00');

        $tableManager->insertRecord('bo_operation', $values);

        // add situation in database

        $values = [];
        $values['id'] = 'NDW01_NLGOU002700535500051_1234';
        $values['version'] = 1;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:40:00');
        $values['probability'] = 'certain';

        $tableManager->insertRecord('bo_situation', $values);

        $situationEventProcessor->setTableManager($tableManager);

        $bridgeIndexService = $this->createBridgeIndexService();
        $situationEventProcessor->setBridgeIndexService($bridgeIndexService);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor->setEventDispatcher($eventDispatcher);

        // notify updated operation projection
        $situationEventProcessor->onSituationUpdate('NDW01_NLGOU002700535500051_1234');

        // check if operation was updated
        $operation = $tableManager->findRecord('bo_operation', ['id' => 99]);

        // assert updated values
        $this->assertEquals('NDW01_NLGOU002700535500051_1234', $operation['event_id']);
        $this->assertEquals(3, $operation['certainty']);
        $this->assertEquals(new \DateTime('2023-01-30 12:30:00'), $operation['time_start']);
        $this->assertEquals(new \DateTime('2023-01-30 12:40:00'), $operation['time_end']);

        // check if event was recorded
        $postedEvents = $eventDispatcher->getPostedEvents();
        $this->assertCount(1, $postedEvents);

        $this->assertEquals('Operation.update', $postedEvents[0]['name']);
        $this->assertCount(1, $postedEvents[0]['params']);
        $this->assertEquals(99, $postedEvents[0]['params'][0]);
    }

    public function testOnSituationUpdateMatchingProjectedOperationMuchLater()
    {

        $situationEventProcessor = new SituationEventProcessor(null);

        // set null logger
        $logger = new \Monolog\Logger('SituationEventProcessor');
        $logger->setHandlers(array(new NullHandler()));
        $situationEventProcessor->setLog($logger);

        // add table manager
        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_operation', 'id', 100);

        // add two operations in database

        $values = [];
        $values['id'] = 98;
        $values['event_id'] = 'BONL01_NLGOU002700535500051_1234';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['time_start'] = new \DateTime('2023-01-30 10:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 10:40:00');

        $tableManager->insertRecord('bo_operation', $values);

        $values = [];
        $values['id'] = 99;
        $values['event_id'] = 'BONL01_NLGOU002700535500051_2345';
        $values['version'] = 2;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['time_start'] = new \DateTime('2023-01-30 12:45:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:55:00');

        $tableManager->insertRecord('bo_operation', $values);

        // add situation in database

        $values = [];
        $values['id'] = 'NDW01_NLGOU002700535500051_1234';
        $values['version'] = 1;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:40:00');
        $values['probability'] = 'certain';

        $tableManager->insertRecord('bo_situation', $values);

        $situationEventProcessor->setTableManager($tableManager);

        $bridgeIndexService = $this->createBridgeIndexService();
        $situationEventProcessor->setBridgeIndexService($bridgeIndexService);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor->setEventDispatcher($eventDispatcher);

        // notify updated operation projection
        $situationEventProcessor->onSituationUpdate('NDW01_NLGOU002700535500051_1234');

        // check if operation was updated
        $operation = $tableManager->findRecord('bo_operation', ['id' => 99]);

        // assert old values
        $this->assertEquals('BONL01_NLGOU002700535500051_2345', $operation['event_id']);
        $this->assertEquals(2, $operation['certainty']);
        $this->assertEquals(new \DateTime('2023-01-30 12:45:00'), $operation['time_start']);
        $this->assertEquals(new \DateTime('2023-01-30 12:55:00'), $operation['time_end']);

        // check if new operation was inserted
        $operation = $tableManager->findRecord('bo_operation', ['id' => 100]);
        $this->assertNotNull($operation);

        // assert new operation values
        $this->assertEquals('NDW01_NLGOU002700535500051_1234', $operation['event_id']);
        $this->assertEquals(3, $operation['certainty']);
        $this->assertEquals(new \DateTime('2023-01-30 12:30:00'), $operation['time_start']);
        $this->assertEquals(new \DateTime('2023-01-30 12:40:00'), $operation['time_end']);

        // check if event was recorded
        $postedEvents = $eventDispatcher->getPostedEvents();
        $this->assertCount(1, $postedEvents);

        $this->assertEquals('Operation.update', $postedEvents[0]['name']);
        $this->assertCount(1, $postedEvents[0]['params']);
        $this->assertEquals(100, $postedEvents[0]['params'][0]);
    }
}
