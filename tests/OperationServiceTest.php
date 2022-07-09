<?php
use BrugOpen\Datex\Service\DatexFileParser;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Service\OperationService;
use PHPUnit\Framework\TestCase;

class OperationServiceTest extends TestCase
{

    public function testGetCurrentOperationsByBridge()
    {

        $tableManager = new MemoryTableManager();

        $operationService = new OperationService(null);
        $operationService->setTableManager($tableManager);

        $currentOperationsByBridgeId = $operationService->getCurrentOperationsByBridgeId();

        $this->assertEmpty($currentOperationsByBridgeId);

        $record = array();
        $record['id'] = 123;
        $record['last_started_operation_id'] = null;
        
        $tableManager->insertRecord('bo_bridge', $record);

        $record = array();
        $record['id'] = 234;
        $record['last_started_operation_id'] = 2342;
        
        $tableManager->insertRecord('bo_bridge', $record);

        $record = array();
        $record['id'] = 345;
        $record['last_started_operation_id'] = 3451;
        
        $tableManager->insertRecord('bo_bridge', $record);

        $currentOperationsByBridgeId = $operationService->getCurrentOperationsByBridgeId();
        
        $this->assertEmpty($currentOperationsByBridgeId);

        // now insert operations

        $record = array();
        $record['id'] = 2341;
        $record['time_start'] = null;
        $record['time_end'] = null;
        $record['event_id'] = 'foo2341';
        $record['bridge_id'] = 234;
        $record['certainty'] = 3;
        $record['finished'] = 1;

        $tableManager->insertRecord('bo_operation', $record);

        $record = array();
        $record['id'] = 2342;
        $record['time_start'] = null;
        $record['time_end'] = null;
        $record['event_id'] = 'foo2342';
        $record['bridge_id'] = 234;
        $record['certainty'] = 3;
        $record['finished'] = 0;

        $tableManager->insertRecord('bo_operation', $record);

        $record = array();
        $record['id'] = 3451;
        $record['time_start'] = null;
        $record['time_end'] = null;
        $record['event_id'] = 'foo3451';
        $record['bridge_id'] = 345;
        $record['certainty'] = 3;
        $record['finished'] = 1;

        $tableManager->insertRecord('bo_operation', $record);

        $record = array();
        $record['id'] = 3452;
        $record['time_start'] = null;
        $record['time_end'] = null;
        $record['event_id'] = 'foo3452';
        $record['bridge_id'] = 345;
        $record['certainty'] = 3;
        $record['finished'] = 0;

        $tableManager->insertRecord('bo_operation', $record);

        $currentOperationsByBridgeId = $operationService->getCurrentOperationsByBridgeId();

        $this->assertNotEmpty($currentOperationsByBridgeId);
        
        $this->assertCount(2, $currentOperationsByBridgeId);

        $this->assertArrayHasKey('234', $currentOperationsByBridgeId);
        $this->assertArrayHasKey('345', $currentOperationsByBridgeId);

        $this->assertCount(1, $currentOperationsByBridgeId[234]);
        $this->assertArrayHasKey('2342', $currentOperationsByBridgeId[234]);
        $this->assertEquals(2342, $currentOperationsByBridgeId[234][2342]->getId());

        $this->assertCount(2, $currentOperationsByBridgeId[345]);
        $this->assertArrayHasKey('3451', $currentOperationsByBridgeId[345]);
        $this->assertArrayHasKey('3452', $currentOperationsByBridgeId[345]);

        $this->assertEquals(3451, $currentOperationsByBridgeId[345][3451]->getId());
        $this->assertEquals(3452, $currentOperationsByBridgeId[345][3452]->getId());

    }

    public function testLoadUnfinishedOperations()
    {

        $tableManager = new MemoryTableManager();

        $operationService = new OperationService(null);
        $operationService->setTableManager($tableManager);

        $unfinishedOperations = $operationService->loadUnfinishedOperations();

        $this->assertEmpty($unfinishedOperations);

        $record = array();
        $record['id'] = 123;
        $record['finished'] = 1;

        $tableManager->insertRecord('bo_operation', $record);

        $record = array();
        $record['id'] = 234;
        $record['finished'] = 1;

        $tableManager->insertRecord('bo_operation', $record);

        $this->assertEquals(2, $tableManager->countRecords('bo_operation'));

        $unfinishedOperations = $operationService->loadUnfinishedOperations();

        $this->assertEmpty($unfinishedOperations);

        $record = array();
        $record['id'] = 345;
        $record['time_start'] = null;
        $record['time_end'] = null;
        $record['event_id'] = 'foo345';
        $record['bridge_id'] = 12;
        $record['certainty'] = 3;
        $record['finished'] = 0;

        $tableManager->insertRecord('bo_operation', $record);

        $record['id'] = 456;
        $record['event_id'] = 'foo456';
        $record['finished'] = 0;

        $tableManager->insertRecord('bo_operation', $record);

        $this->assertEquals(4, $tableManager->countRecords('bo_operation'));

        $unfinishedOperations = $operationService->loadUnfinishedOperations();

        $this->assertNotEmpty($unfinishedOperations);

        $this->assertCount(2, $unfinishedOperations);

        $this->assertArrayHasKey(345, $unfinishedOperations);
        $this->assertArrayHasKey(456, $unfinishedOperations);

        $this->assertEquals(345, $unfinishedOperations[345]->getId());
        $this->assertEquals(456, $unfinishedOperations[456]->getId());

    }

    public function testLoadLastStartedOperations()
    {

        $tableManager = new MemoryTableManager();

        $operationService = new OperationService(null);
        $operationService->setTableManager($tableManager);

        $lastStartedOperations = $operationService->loadLastStartedOperationIds();

        $this->assertEmpty($lastStartedOperations);

        $record = array();
        $record['id'] = 123;
        $record['last_started_operation_id'] = null;
        
        $tableManager->insertRecord('bo_bridge', $record);

        $lastStartedOperations = $operationService->loadLastStartedOperationIds();

        $this->assertEmpty($lastStartedOperations);

        $record = array();
        $record['id'] = 234;
        $record['last_started_operation_id'] = 2234;
        
        $tableManager->insertRecord('bo_bridge', $record);

        $record = array();
        $record['id'] = 345;
        $record['last_started_operation_id'] = 3345;
        
        $tableManager->insertRecord('bo_bridge', $record);

        $lastStartedOperations = $operationService->loadLastStartedOperationIds();

        $this->assertNotEmpty($lastStartedOperations);

        $this->assertCount(2, $lastStartedOperations);

        $this->assertArrayHasKey(234, $lastStartedOperations);
        $this->assertArrayHasKey(345, $lastStartedOperations);

        $this->assertEquals(2234, $lastStartedOperations[234]);
        $this->assertEquals(3345, $lastStartedOperations[345]);

    }

    public function testUpdateLastStartedOperationId()
    {

        $tableManager = new MemoryTableManager();

        $operationService = new OperationService(null);
        $operationService->setTableManager($tableManager);

        $record = array();
        $record['id'] = 345;
        $record['time_start'] = null;
        $record['time_end'] = null;
        $record['event_id'] = 'foo345';
        $record['bridge_id'] = 12;
        $record['certainty'] = 3;
        $record['finished'] = 0;

        $tableManager->insertRecord('bo_operation', $record);

        $record['id'] = 456;
        $record['event_id'] = 'foo456';
        $record['finished'] = 0;

        $tableManager->insertRecord('bo_operation', $record);

        $this->assertEquals(2, $tableManager->countRecords('bo_operation'));

        $record = array();
        $record['id'] = 12;
        $record['last_started_operation_id'] = null;
        
        $tableManager->insertRecord('bo_bridge', $record);

        $this->assertEquals(1, $tableManager->countRecords('bo_bridge'));

        $lastStartedOperations = $operationService->loadLastStartedOperationIds();

        $this->assertEmpty($lastStartedOperations);

        $operationService->updateLastStartedOperation(12, 456);

        $lastStartedOperations = $operationService->loadLastStartedOperationIds();

        $this->assertNotEmpty($lastStartedOperations);

        $this->assertCount(1, $lastStartedOperations);

        $this->assertArrayHasKey(12, $lastStartedOperations);

        $this->assertEquals(456, $lastStartedOperations[12]);

    }

}
