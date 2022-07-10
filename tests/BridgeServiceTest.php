<?php
use BrugOpen\Datex\Service\DatexFileParser;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Service\BridgeService;
use BrugOpen\Service\OperationService;
use PHPUnit\Framework\TestCase;

class BridgeServiceTest extends TestCase
{

    public function testCreateBridgeFromNdwSituation()
    {

        $tableManager = new MemoryTableManager();

        $tableManager->setAutoIncrement('bo_bridge', 'id', 13);

        $bridgeService = new BridgeService();

        $bridgeService->setTableManager($tableManager);

        // create existing bridge

        $record = array();
        $record['id'] = 12;
        $record['name'] = 'some-bridge';
        $record['title'] = 'SomeBridge';
        $record['city'] = 'SomeCity';
        $record['city2'] = '';
        $record['ndw_id'] = '1134';
        $record['ndw_lat'] = '52.1406519';
        $record['ndw_lng'] = '4.4873079';

        $tableManager->insertRecord('bo_bridge', $record);

        $allBridges = $bridgeService->getAllBridges();

        $this->assertNotEmpty($allBridges);

        $this->assertCount(1, $allBridges);

        $this->assertArrayHasKey(12, $allBridges);
        $this->assertEquals(12, $allBridges[12]->getId());

        // now insert bridge

        $insertedBridge = $bridgeService->insertBridgeFromNdwData(2245, 'NL12345678', new LatLng(52.1548, 4.5137));

        $this->assertNotNull($insertedBridge);

        $this->assertEquals(13, $insertedBridge->getId());

        $this->assertEquals('NL12345678', $insertedBridge->getIsrsCode());

        $this->assertNotNull($insertedBridge->getLatLng());

        $this->assertEquals(52.1548, $insertedBridge->getLatLng()->getLat());
        $this->assertEquals(4.5137, $insertedBridge->getLatLng()->getLng());

        // check inserted record

        $critera = array();
        $critera['id'] = 13;

        $record = $tableManager->findRecord('bo_bridge', $critera);

        $this->assertNotNull($record);

        $this->assertEquals(13, $record['id']);
        $this->assertEquals(2245, $record['ndw_id']);
        $this->assertEquals('NL12345678', $record['isrs_code']);
        $this->assertEquals(52.1548, $record['ndw_lat']);
        $this->assertEquals(4.5137, $record['ndw_lng']);
        
    }

}
