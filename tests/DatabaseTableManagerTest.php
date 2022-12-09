<?php
use BrugOpen\Datex\Service\DatexFileParser;
use BrugOpen\Db\Service\DatabaseTableManager;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Service\BridgeService;
use BrugOpen\Service\OperationService;
use PHPUnit\Framework\TestCase;

class DatabaseTableManagerTest extends TestCase
{
    public function testCreateSelectStatementParametersWholeTable()
    {

        $tableManager = new DatabaseTableManager(null);

        $parameters = $tableManager->createSelectStatementParameters('foo_table');

        $this->assertNotNull($parameters);
        $this->assertCount(2, $parameters);

        $this->assertEquals('SELECT * FROM foo_table', $parameters[0]);
        $this->assertEmpty($parameters[1]);

    }

    public function testCreateSelectStatementParametersWithOneCriterium()
    {

        $tableManager = new DatabaseTableManager(null);

        $criteria = array();
        $criteria['field'] = 'bar';

        $parameters = $tableManager->createSelectStatementParameters('foo_table', $criteria);

        $this->assertNotNull($parameters);
        $this->assertCount(2, $parameters);

        $this->assertEquals('SELECT * FROM foo_table WHERE (field = :c0)', $parameters[0]);
        $this->assertNotEmpty($parameters[1]);
        $this->assertCount(1, $parameters[1]);
        $this->assertArrayHasKey('c0', $parameters[1]);
        $this->assertEquals('bar', $parameters[1]['c0']);

    }

    public function testCreateSelectStatementParametersWithTwoCriteria()
    {

        $tableManager = new DatabaseTableManager(null);

        $criteria = array();
        $criteria['field1'] = 'bar1';
        $criteria['field2'] = 'bar2';

        $parameters = $tableManager->createSelectStatementParameters('foo_table', $criteria);

        $this->assertNotNull($parameters);
        $this->assertCount(2, $parameters);

        $this->assertEquals('SELECT * FROM foo_table WHERE (field1 = :c0) AND (field2 = :c1)', $parameters[0]);
        $this->assertNotEmpty($parameters[1]);
        $this->assertCount(2, $parameters[1]);
        $this->assertArrayHasKey('c0', $parameters[1]);
        $this->assertEquals('bar1', $parameters[1]['c0']);
        $this->assertArrayHasKey('c1', $parameters[1]);
        $this->assertEquals('bar2', $parameters[1]['c1']);

    }

    public function testCreateSelectStatementParametersWholeTableWithDateMapping()
    {

        $tableManager = new DatabaseTableManager(null);

        $columnDefinitions = array();
        $columnDefinitions['id'] = DatabaseTableManager::COLUMN_INT;
        $columnDefinitions['date_start'] = DatabaseTableManager::COLUMN_DATE;

        $tableManager->setColumnDefinitions('sometable', $columnDefinitions);

        $parameters = $tableManager->createSelectStatementParameters('sometable');

        $this->assertNotNull($parameters);
        $this->assertCount(2, $parameters);

        $this->assertEquals('SELECT id, UNIX_TIMESTAMP(date_start) AS date_start FROM sometable', $parameters[0]);
        $this->assertEmpty($parameters[1]);

    }

    public function testCreateSelectStatementParametersWholeTableWithDateTimeMapping()
    {

        $tableManager = new DatabaseTableManager(null);

        $columnDefinitions = array();
        $columnDefinitions['id'] = DatabaseTableManager::COLUMN_INT;
        $columnDefinitions['datetime_start'] = DatabaseTableManager::COLUMN_DATE + DatabaseTableManager::COLUMN_TIME;

        $tableManager->setColumnDefinitions('sometable', $columnDefinitions);

        $parameters = $tableManager->createSelectStatementParameters('sometable');

        $this->assertNotNull($parameters);
        $this->assertCount(2, $parameters);

        $this->assertEquals('SELECT id, UNIX_TIMESTAMP(datetime_start) AS datetime_start FROM sometable', $parameters[0]);
        $this->assertEmpty($parameters[1]);

    }    

    public function testCreateInsertStatementParametersSingleRecord()
    {

        $tableManager = new DatabaseTableManager(null);

        $parameters = $tableManager->createInsertStatementParameters('foo_table', array('field1', 'field2'), array(array('foo', 'bar')));

        $this->assertNotNull($parameters);
        $this->assertCount(2, $parameters);

        $this->assertEquals('INSERT INTO foo_table (field1, field2) VALUES (:v0, :v1)', $parameters[0]);
        $this->assertNotEmpty($parameters[1]);
        $this->assertCount(2, $parameters[1]);
        $this->assertEquals('foo', $parameters[1]['v0']);
        $this->assertEquals('bar', $parameters[1]['v1']);

    }

    public function testCreateInsertStatementParametersTwoRecords()
    {

        $tableManager = new DatabaseTableManager(null);

        $parameters = $tableManager->createInsertStatementParameters('foo_table', array('field1', 'field2'), array(array('foo', 'bar'), array('foo2', 'bar2')));

        $this->assertNotNull($parameters);
        $this->assertCount(2, $parameters);

        $this->assertEquals('INSERT INTO foo_table (field1, field2) VALUES (:v0, :v1), (:v2, :v3)', $parameters[0]);
        $this->assertNotEmpty($parameters[1]);
        $this->assertCount(4, $parameters[1]);
        $this->assertEquals('foo', $parameters[1]['v0']);
        $this->assertEquals('bar', $parameters[1]['v1']);
        $this->assertEquals('foo2', $parameters[1]['v2']);
        $this->assertEquals('bar2', $parameters[1]['v3']);

    }

}
