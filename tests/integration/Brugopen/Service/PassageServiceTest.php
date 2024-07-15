<?php

namespace BrugOpen\Service;

use BrugOpen\Db\Service\MemoryTableManager;
use PHPUnit\Framework\TestCase;

class PassageServiceTest extends TestCase
{

    public function testFindPassagesByOperation()
    {

        $records = array();

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 1;
        $record['datetime_passage'] = new \DateTime('2024-06-01 14:00');
        $record['operation_id'] = 12001;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 2;
        $record['datetime_passage'] = new \DateTime('2024-06-02 14:00');
        $record['operation_id'] = 12002;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '23456789';
        $record['bridge_id'] = 2;
        $record['datetime_passage'] = new \DateTime('2024-06-02 14:02');
        $record['operation_id'] = 12002;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 3;
        $record['datetime_passage'] = new \DateTime('2024-06-03 14:00');
        $record['operation_id'] = 12003;

        $records[] = $record;

        $tableManager = new MemoryTableManager();

        foreach ($records as $record) {

            $tableManager->insertRecord('bo_bridge_passage', $record);
        }

        $passageService = new PassageService();
        $passageService->setTableManager($tableManager);

        $passagesByOperation = $passageService->findPassagesByOperation(array(12002, 12003, 12004));

        $this->assertCount(2, $passagesByOperation);
        $this->assertArrayHasKey(12002, $passagesByOperation);
        $this->assertArrayHasKey(12003, $passagesByOperation);

        $this->assertCount(2, $passagesByOperation[12002]);
        $this->assertCount(1, $passagesByOperation[12003]);
    }

    public function testFindVesselPassagesByBridge()
    {

        $records = array();

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 1;
        $record['datetime_passage'] = new \DateTime('2024-06-01 14:00');
        $record['operation_id'] = 12001;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 2;
        $record['datetime_passage'] = new \DateTime('2024-06-02 14:00');
        $record['operation_id'] = 12002;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '23456789';
        $record['bridge_id'] = 2;
        $record['datetime_passage'] = new \DateTime('2024-06-02 14:02');
        $record['operation_id'] = 12002;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 3;
        $record['datetime_passage'] = new \DateTime('2024-06-03 14:00');
        $record['operation_id'] = 12003;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 1;
        $record['datetime_passage'] = new \DateTime('2024-06-04 14:00');
        $record['operation_id'] = 12004;

        $records[] = $record;

        $tableManager = new MemoryTableManager();

        foreach ($records as $record) {

            $tableManager->insertRecord('bo_bridge_passage', $record);
        }

        $passageService = new PassageService();
        $passageService->setTableManager($tableManager);

        $passagesByBridge = $passageService->findVesselPassagesByBridge('12345678', array(1, 2));

        $this->assertCount(2, $passagesByBridge);
        $this->assertArrayHasKey(1, $passagesByBridge);
        $this->assertArrayHasKey(2, $passagesByBridge);

        $this->assertCount(2, $passagesByBridge[1]);
        $this->assertCount(1, $passagesByBridge[2]);

        // now with date filter

        $passagesByBridge = $passageService->findVesselPassagesByBridge('12345678', array(1, 2), new \DateTime('2024-06-03'));

        $this->assertCount(1, $passagesByBridge);
        $this->assertArrayHasKey(1, $passagesByBridge);

        $this->assertCount(1, $passagesByBridge[1]);
    }

    public function testFindVesselPassages()
    {

        $records = array();

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 1;
        $record['datetime_passage'] = new \DateTime('2024-06-01 14:00');
        $record['operation_id'] = 12001;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 2;
        $record['datetime_passage'] = new \DateTime('2024-06-02 14:00');
        $record['operation_id'] = 12002;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '23456789';
        $record['bridge_id'] = 2;
        $record['datetime_passage'] = new \DateTime('2024-06-02 14:02');
        $record['operation_id'] = 12002;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 3;
        $record['datetime_passage'] = new \DateTime('2024-06-03 14:00');
        $record['operation_id'] = 12003;

        $records[] = $record;

        $record = array();
        $record['mmsi'] = '12345678';
        $record['bridge_id'] = 1;
        $record['datetime_passage'] = new \DateTime('2024-06-04 14:00');
        $record['operation_id'] = 12004;

        $records[] = $record;

        $tableManager = new MemoryTableManager();

        foreach ($records as $record) {

            $tableManager->insertRecord('bo_bridge_passage', $record);
        }

        $passageService = new PassageService();
        $passageService->setTableManager($tableManager);

        $passage = $passageService->findVesselPassage('12345678', 3, new \DateTime('2024-06-03 14:00'));

        $this->assertNotNull($passage);
        $this->assertEquals(12003, $passage->getOperationId());

        // now with different time

        $passage = $passageService->findVesselPassage('12345678', 3, new \DateTime('2024-06-03 14:00:01'));

        $this->assertNull($passage);
    }
}
