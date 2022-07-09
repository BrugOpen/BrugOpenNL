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

        $tableManager->insertRecord('bo_operation', array());

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

}
