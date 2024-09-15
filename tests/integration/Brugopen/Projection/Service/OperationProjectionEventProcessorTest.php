<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Core\TestEventDispatcher;
use BrugOpen\Db\Service\MemoryTableManager;
use Monolog\Handler\NullHandler;
use PHPUnit\Framework\TestCase;

class OperationProjectionEventProcessorTest extends TestCase
{

    public function testOnOperationProjectionUpdatedNewOperation()
    {

        $operationProjectionEventProcessor = new OperationProjectionEventProcessor(null);

        // set null logger
        $logger = new \Monolog\Logger('OperationProjectionEventProcessor');
        $logger->setHandlers(array(new NullHandler()));
        $operationProjectionEventProcessor->setLog($logger);

        // add table manager
        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_operation', 'id', 100);

        // add three operation projections in database

        $values = [];
        $values['id'] = 1234;
        $values['event_id'] = 'BONL01_AAAAA_1234';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:40:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:00:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        $values = [];
        $values['id'] = 1235;
        $values['event_id'] = 'BONL01_AAAAA_1234';
        $values['version'] = 2;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:31:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:41:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:01:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        $values = [];
        $values['id'] = 1236;
        $values['event_id'] = 'BONL01_AAAAA_1236';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 13:30:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:01:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        $operationProjectionEventProcessor->setTableManager($tableManager);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $operationProjectionEventProcessor->setEventDispatcher($eventDispatcher);

        // notify opdated operation projection
        $operationProjectionEventProcessor->onOperationProjectionUpdated('BONL01_AAAAA_1234');

        // check if operation was stored
        $operation = $tableManager->findRecord('bo_operation', ['event_id' => 'BONL01_AAAAA_1234']);

        $this->assertNotNull($operation);
        $this->assertEquals(100, $operation['id']);
        $this->assertEquals('BONL01_AAAAA_1234', $operation['event_id']);
        $this->assertEquals(90, $operation['bridge_id']);
        $this->assertEquals(new \DateTime('2023-01-30 12:31:00'), $operation['time_start']);
        $this->assertEquals(new \DateTime('2023-01-30 12:41:00'), $operation['time_end']);

        // check if operation id was updated on operation projection
        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_1234', 'version' => 1]);
        $this->assertEquals(100, $record['operation_id']);

        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_1234', 'version' => 2]);
        $this->assertEquals(100, $record['operation_id']);

        // check if operation was not set on other operation projection
        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_2345', 'version' => 1]);
        $this->assertNull($record['operation_id']);

        // check if event was recorded
        $postedEvents = $eventDispatcher->getPostedEvents();
        $this->assertCount(1, $postedEvents);

        $this->assertEquals('Operation.update', $postedEvents[0]['name']);
        $this->assertCount(1, $postedEvents[0]['params']);
        $this->assertEquals(100, $postedEvents[0]['params'][0]);
    }

    public function testOnOperationProjectionUpdatedExistingNdwOperation()
    {

        $operationProjectionEventProcessor = new OperationProjectionEventProcessor(null);

        // set null logger
        $logger = new \Monolog\Logger('OperationProjectionEventProcessor');
        $logger->setHandlers(array(new NullHandler()));
        $operationProjectionEventProcessor->setLog($logger);

        // add table manager
        $tableManager = new MemoryTableManager();

        // add three operation projections in database

        $values = [];
        $values['id'] = 1234;
        $values['event_id'] = 'BONL01_AAAAA_1234';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:40:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:00:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        $values = [];
        $values['id'] = 1235;
        $values['event_id'] = 'BONL01_AAAAA_1234';
        $values['version'] = 2;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:31:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:41:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:01:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        $values = [];
        $values['id'] = 1236;
        $values['event_id'] = 'BONL01_AAAAA_1236';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 13:30:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:01:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        // add NDW operation in database
        $values = [];
        $values['id'] = 99;
        $values['event_id'] = 'NDW01_AAAAA_3333';
        $values['bridge_id'] = 90;
        $values['certainty'] = 3;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:40:00');

        $tableManager->insertRecord('bo_operation', $values);

        $tableManager->setAutoIncrement('bo_operation', 'id', 100);

        $operationProjectionEventProcessor->setTableManager($tableManager);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $operationProjectionEventProcessor->setEventDispatcher($eventDispatcher);

        // notify opdated operation projection
        $operationProjectionEventProcessor->onOperationProjectionUpdated('BONL01_AAAAA_1234');

        // check if operation was stored
        $operation = $tableManager->findRecord('bo_operation', ['event_id' => 'BONL01_AAAAA_1234']);

        $this->assertNull($operation);

        // check if operation id was updated on operation projection
        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_1234', 'version' => 1]);
        $this->assertEquals(99, $record['operation_id']);

        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_1234', 'version' => 2]);
        $this->assertEquals(99, $record['operation_id']);

        // check if operation was not set on other operation projection
        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_2345', 'version' => 1]);
        $this->assertNull($record['operation_id']);

        // assert no event was emitted
        $postedEvents = $eventDispatcher->getPostedEvents();
        $this->assertCount(0, $postedEvents);
    }


    public function testOnOperationProjectionUpdatedExistingSlightlyLaterNdwOperation()
    {

        $operationProjectionEventProcessor = new OperationProjectionEventProcessor(null);

        // set null logger
        $logger = new \Monolog\Logger('OperationProjectionEventProcessor');
        $logger->setHandlers(array(new NullHandler()));
        $operationProjectionEventProcessor->setLog($logger);

        // add table manager
        $tableManager = new MemoryTableManager();

        // add three operation projections in database

        $values = [];
        $values['id'] = 1234;
        $values['event_id'] = 'BONL01_AAAAA_1234';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:40:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:00:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        $values = [];
        $values['id'] = 1235;
        $values['event_id'] = 'BONL01_AAAAA_1234';
        $values['version'] = 2;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:31:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:41:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:01:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        $values = [];
        $values['id'] = 1236;
        $values['event_id'] = 'BONL01_AAAAA_1236';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 13:30:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:01:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        // add NDW operation in database
        $values = [];
        $values['id'] = 99;
        $values['event_id'] = 'NDW01_AAAAA_3333';
        $values['bridge_id'] = 90;
        $values['certainty'] = 3;
        $values['time_start'] = new \DateTime('2023-01-30 12:32:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:42:00');

        $tableManager->insertRecord('bo_operation', $values);

        $tableManager->setAutoIncrement('bo_operation', 'id', 100);

        $operationProjectionEventProcessor->setTableManager($tableManager);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $operationProjectionEventProcessor->setEventDispatcher($eventDispatcher);

        // notify opdated operation projection
        $operationProjectionEventProcessor->onOperationProjectionUpdated('BONL01_AAAAA_1234');

        // check if operation was stored
        $operation = $tableManager->findRecord('bo_operation', ['event_id' => 'BONL01_AAAAA_1234']);

        $this->assertNull($operation);

        // check if operation id was updated on operation projection
        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_1234', 'version' => 1]);
        $this->assertEquals(99, $record['operation_id']);

        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_1234', 'version' => 2]);
        $this->assertEquals(99, $record['operation_id']);

        // check if operation was not set on other operation projection
        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_2345', 'version' => 1]);
        $this->assertNull($record['operation_id']);

        // assert no event was emitted
        $postedEvents = $eventDispatcher->getPostedEvents();
        $this->assertCount(0, $postedEvents);
    }

    public function testOnOperationProjectionUpdatedNewOperationWithMuchLaterNdwOperation()
    {

        $operationProjectionEventProcessor = new OperationProjectionEventProcessor(null);

        // set null logger
        $logger = new \Monolog\Logger('OperationProjectionEventProcessor');
        $logger->setHandlers(array(new NullHandler()));
        $operationProjectionEventProcessor->setLog($logger);

        // add table manager
        $tableManager = new MemoryTableManager();

        // add three operation projections in database

        $values = [];
        $values['id'] = 1234;
        $values['event_id'] = 'BONL01_AAAAA_1234';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:40:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:00:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        $values = [];
        $values['id'] = 1235;
        $values['event_id'] = 'BONL01_AAAAA_1234';
        $values['version'] = 2;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:31:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:41:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:01:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        $values = [];
        $values['id'] = 1236;
        $values['event_id'] = 'BONL01_AAAAA_1236';
        $values['version'] = 1;
        $values['bridge_id'] = 90;
        $values['certainty'] = 2;
        $values['operation_id'] = null;
        $values['time_start'] = new \DateTime('2023-01-30 12:30:00');
        $values['time_end'] = new \DateTime('2023-01-30 13:30:00');
        $values['datetime_projection'] = new \DateTime('2023-01-30 12:01:00');

        $tableManager->insertRecord('bo_operation_projection', $values);

        // add NDW operation in database
        $values = [];
        $values['id'] = 99;
        $values['event_id'] = 'NDW01_AAAAA_3333';
        $values['bridge_id'] = 90;
        $values['certainty'] = 3;
        $values['time_start'] = new \DateTime('2023-01-30 12:39:00');
        $values['time_end'] = new \DateTime('2023-01-30 12:49:00');

        $tableManager->insertRecord('bo_operation', $values);

        $tableManager->setAutoIncrement('bo_operation', 'id', 100);

        $operationProjectionEventProcessor->setTableManager($tableManager);

        // add event recorder
        $eventDispatcher = new TestEventDispatcher();
        $operationProjectionEventProcessor->setEventDispatcher($eventDispatcher);

        // notify opdated operation projection
        $operationProjectionEventProcessor->onOperationProjectionUpdated('BONL01_AAAAA_1234');

        // check if operation was stored
        $operation = $tableManager->findRecord('bo_operation', ['event_id' => 'BONL01_AAAAA_1234']);

        $this->assertNotNull($operation);
        $this->assertEquals(100, $operation['id']);
        $this->assertEquals('BONL01_AAAAA_1234', $operation['event_id']);
        $this->assertEquals(90, $operation['bridge_id']);
        $this->assertEquals(new \DateTime('2023-01-30 12:31:00'), $operation['time_start']);
        $this->assertEquals(new \DateTime('2023-01-30 12:41:00'), $operation['time_end']);

        // check if operation id was updated on operation projection
        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_1234', 'version' => 1]);
        $this->assertEquals(100, $record['operation_id']);

        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_1234', 'version' => 2]);
        $this->assertEquals(100, $record['operation_id']);

        // check if operation was not set on other operation projection
        $record = $tableManager->findRecord('bo_operation_projection', ['event_id' => 'BONL01_AAAAA_2345', 'version' => 1]);
        $this->assertNull($record['operation_id']);

        // check if event was recorded
        $postedEvents = $eventDispatcher->getPostedEvents();
        $this->assertCount(1, $postedEvents);

        $this->assertEquals('Operation.update', $postedEvents[0]['name']);
        $this->assertCount(1, $postedEvents[0]['params']);
        $this->assertEquals(100, $postedEvents[0]['params'][0]);
    }
}
