<?php

namespace BrugOpen\Service;

use BrugOpen\Db\Service\MemoryTableManager;
use PHPUnit\Framework\TestCase;

class OperationIndexServiceTest extends TestCase
{

    public function testGetLastStartedOperation()
    {

        $records = array();

        $record = array();
        $record['id'] = 101;
        $record['bridge_id'] = 1;
        $record['datetime_start'] = mktime(12, 30, 0, 1, 30, 2023);
        $record['time_start'] = new \DateTime('@' . $record['datetime_start']);

        $records[] = $record;

        $record = array();
        $record['id'] = 102;
        $record['bridge_id'] = 2;
        $record['datetime_start'] = mktime(12, 30, 0, 1, 30, 2023);
        $record['time_start'] = new \DateTime('@' . $record['datetime_start']);

        $records[] = $record;

        $record = array();
        $record['id'] = 111;
        $record['bridge_id'] = 1;
        $record['datetime_start'] = mktime(12, 30, 0, 1, 31, 2023);
        $record['time_start'] = new \DateTime('@' . $record['datetime_start']);

        $records[] = $record;

        $record = array();
        $record['id'] = 112;
        $record['bridge_id'] = 1;
        $record['datetime_start'] = mktime(12, 30, 0, 2, 1, 2023);
        $record['time_start'] = new \DateTime('@' . $record['datetime_start']);

        $records[] = $record;

        $tableManager = new MemoryTableManager();

        foreach ($records as $record) {

            $tableManager->insertRecord('bo_operation', $record);
        }

        $operationIndexService = new OperationIndexService();
        $operationIndexService->setTableManager($tableManager);

        $operation = $operationIndexService->getLastStartedOperation(1, mktime(1, 0, 0, 1, 31, 2023));

        $this->assertNotNull($operation);
        $this->assertEquals(101, $operation->getId());

        $operation = $operationIndexService->getLastStartedOperation(1, mktime(13, 0, 0, 1, 31, 2023));

        $this->assertNotNull($operation);
        $this->assertEquals(111, $operation->getId());

        $operation = $operationIndexService->getLastStartedOperation(1, mktime(13, 0, 0, 2, 28, 2023));

        $this->assertNotNull($operation);
        $this->assertEquals(112, $operation->getId());

        $operation = $operationIndexService->getLastStartedOperation(1, mktime(1, 0, 0, 1, 30, 2023));

        $this->assertNull($operation);

        $operation = $operationIndexService->getLastStartedOperation(2, mktime(13, 0, 0, 2, 28, 2023));

        $this->assertNotNull($operation);
        $this->assertEquals(102, $operation->getId());

        $operation = $operationIndexService->getLastStartedOperation(2, mktime(12, 0, 0, 1, 30, 2023));

        $this->assertNull($operation);
    }
}
