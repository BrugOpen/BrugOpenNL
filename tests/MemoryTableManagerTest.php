<?php
use BrugOpen\Db\Service\MemoryTableManager;
use PHPUnit\Framework\TestCase;

class MemoryTableManagerTest extends TestCase
{

    public function testFindRecordsWholeTable()
    {
        $tableManager = new MemoryTableManager();

        $records = $tableManager->findRecords('mytable');

        $this->assertEmpty($records);

        // insert one record
        $record = array();
        $record['id'] = 1;
        $record['title'] = 'foo';

        $tableManager->insertRecord('mytable', $record);

        // now find all records again

        $records = $tableManager->findRecords('mytable');

        $this->assertNotEmpty($records);
        $this->assertCount(1, $records);

        $record = $records[0];
        $this->assertEquals(1, $record['id']);
        $this->assertEquals('foo', $record['title']);

        // now insert another record
        $record = array();
        $record['id'] = 2;
        $record['title'] = 'bar';

        $tableManager->insertRecord('mytable', $record);

        // now find all records again

        $records = $tableManager->findRecords('mytable');

        $this->assertNotEmpty($records);
        $this->assertCount(2, $records);

        $record = $records[0];
        $this->assertEquals(1, $record['id']);
        $this->assertEquals('foo', $record['title']);

        $record = $records[1];
        $this->assertEquals(2, $record['id']);
        $this->assertEquals('bar', $record['title']);
    }

    public function testFindInsertRecordAutoIncrement()
    {
        $tableManager = new MemoryTableManager();

        $records = $tableManager->findRecords('mytable');

        $this->assertEmpty($records);

        // insert one record
        $record = array();
        $record['id'] = 0;
        $record['title'] = 'foo';

        // insert without auto increment
        $tableManager->insertRecord('mytable', $record);

        $records = $tableManager->findRecords('mytable');

        $this->assertNotEmpty($records);
        $this->assertCount(1, $records);

        $record = $records[0];
        $this->assertEquals(0, $record['id']);
        $this->assertEquals('foo', $record['title']);

        // clear table manager
        $tableManager = new MemoryTableManager();

        // set auto increment
        $tableManager->setAutoIncrement('mytable', 'id', 123);

        // insert record
        $insertedId = $tableManager->insertRecord('mytable', $record);

        $this->assertEquals(123, $insertedId);

        $records = $tableManager->findRecords('mytable');

        $this->assertNotEmpty($records);
        $this->assertCount(1, $records);

        $record = $records[0];
        $this->assertEquals(123, $record['id']);
        $this->assertEquals('foo', $record['title']);

        // insert another record
        $record = array();
        $record['title'] = 'bar';

        $insertedId = $tableManager->insertRecord('mytable', $record);

        $this->assertEquals(124, $insertedId);

        $records = $tableManager->findRecords('mytable');

        $this->assertNotEmpty($records);
        $this->assertCount(2, $records);

        $this->assertEquals(123, $records[0]['id']);
        $this->assertEquals(124, $records[1]['id']);
    }

    public function testFindRecordsWithCriteria()
    {
        $tableManager = new MemoryTableManager();

        $records = $tableManager->findRecords('mytable');

        $this->assertEmpty($records);

        $record = array();
        $record['id'] = 1;
        $record['type_id'] = 1;
        $record['title'] = 'foo1';

        $tableManager->insertRecord('mytable', $record);

        $record = array();
        $record['id'] = 2;
        $record['type_id'] = 1;
        $record['title'] = 'foo2';

        $tableManager->insertRecord('mytable', $record);

        $record = array();
        $record['id'] = 3;
        $record['type_id'] = 2;
        $record['title'] = 'bar2';

        $tableManager->insertRecord('mytable', $record);

        // find all records

        $records = $tableManager->findRecords('mytable');

        $this->assertCount(3, $records);

        // find by id
        $criteria = array();
        $criteria['id'] = 2;

        $records = $tableManager->findRecords('mytable', $criteria);

        $this->assertCount(1, $records);
        $this->assertEquals(2, $records[0]['id']);

        // find by array of ids

        $criteria = array();
        $criteria['id'] = array();
        $criteria['id'][] = 1;
        $criteria['id'][] = 3;

        $records = $tableManager->findRecords('mytable', $criteria);

        $this->assertCount(2, $records);
        $this->assertEquals(1, $records[0]['id']);
        $this->assertEquals(3, $records[1]['id']);

        // find by type id

        $criteria = array();
        $criteria['type_id'] = 1;
        $records = $tableManager->findRecords('mytable', $criteria);

        $this->assertCount(2, $records);

        $this->assertEquals(1, $records[0]['id']);
        $this->assertEquals(2, $records[1]['id']);
    }

    public function testFindRecordsWithOrdering()
    {
        $tableManager = new MemoryTableManager();

        $records = $tableManager->findRecords('mytable');

        $this->assertEmpty($records);

        $record = array();
        $record['id'] = 2;
        $record['type_id'] = 1;
        $record['title'] = 'foo2';

        $tableManager->insertRecord('mytable', $record);

        $record = array();
        $record['id'] = 1;
        $record['type_id'] = 1;
        $record['title'] = 'foo1';

        $tableManager->insertRecord('mytable', $record);

        $record = array();
        $record['id'] = 3;
        $record['type_id'] = 2;
        $record['title'] = 'bar2';

        $tableManager->insertRecord('mytable', $record);

        // assert insertion order

        $records = $tableManager->findRecords('mytable');

        $this->assertCount(3, $records);
        $this->assertEquals(2, $records[0]['id']);
        $this->assertEquals(1, $records[1]['id']);
        $this->assertEquals(3, $records[2]['id']);

        // now order by id asc
        $order = array();
        $order[] = 'id';
        $records = $tableManager->findRecords('mytable', null, null, $order);

        $this->assertCount(3, $records);
        $this->assertEquals(1, $records[0]['id']);
        $this->assertEquals(2, $records[1]['id']);
        $this->assertEquals(3, $records[2]['id']);

        // now order by id desc
        $order = array(
            array(
                'id',
                'desc'
            )
        );
        $records = $tableManager->findRecords('mytable', null, null, $order);

        $this->assertCount(3, $records);
        $this->assertEquals(3, $records[0]['id']);
        $this->assertEquals(2, $records[1]['id']);
        $this->assertEquals(1, $records[2]['id']);

        // now order by type id desc and title asc

        $order = array(
            array(
                'type_id',
                'desc'
            ),
            array(
                'title',
                'asc'
            )
        );
        $records = $tableManager->findRecords('mytable', null, null, $order);

        $this->assertCount(3, $records);
        $this->assertEquals(3, $records[0]['id']);
        $this->assertEquals(1, $records[1]['id']);
        $this->assertEquals(2, $records[2]['id']);
    }

    public function testFindRecordsWithOrderingAndLimit()
    {
        $tableManager = new MemoryTableManager();

        $records = $tableManager->findRecords('mytable');

        $this->assertEmpty($records);

        $record = array();
        $record['id'] = 2;
        $record['type_id'] = 1;
        $record['title'] = 'foo2';

        $tableManager->insertRecord('mytable', $record);

        $record = array();
        $record['id'] = 1;
        $record['type_id'] = 1;
        $record['title'] = 'foo1';

        $tableManager->insertRecord('mytable', $record);

        $record = array();
        $record['id'] = 3;
        $record['type_id'] = 2;
        $record['title'] = 'bar2';

        $tableManager->insertRecord('mytable', $record);

        // assert insertion order

        $records = $tableManager->findRecords('mytable', null, null, null, 1);

        $this->assertCount(1, $records);
        $this->assertEquals(2, $records[0]['id']);

        $records = $tableManager->findRecords('mytable', null, null, null, 2);

        $this->assertCount(2, $records);
        $this->assertEquals(2, $records[0]['id']);
        $this->assertEquals(1, $records[1]['id']);

        // now order by id asc
        $order = array();
        $order[] = 'id';
        $records = $tableManager->findRecords('mytable', null, null, $order, 2);

        $this->assertCount(2, $records);
        $this->assertEquals(1, $records[0]['id']);
        $this->assertEquals(2, $records[1]['id']);

        // now order by id desc
        $order = array(
            array(
                'id',
                'desc'
            )
        );
        $records = $tableManager->findRecords('mytable', null, null, $order, 2);

        $this->assertCount(2, $records);
        $this->assertEquals(3, $records[0]['id']);
        $this->assertEquals(2, $records[1]['id']);

        // now order by id asc one record starting from second record
        $order = array();
        $order[] = 'id';
        $records = $tableManager->findRecords('mytable', null, null, $order, 1, 1);

        $this->assertCount(1, $records);
        $this->assertEquals(2, $records[0]['id']);

        // now order by id asc two records starting from second record
        $order = array();
        $order[] = 'id';
        $records = $tableManager->findRecords('mytable', null, null, $order, 2, 1);

        $this->assertCount(2, $records);
        $this->assertEquals(2, $records[0]['id']);
        $this->assertEquals(3, $records[1]['id']);

        // now order by id asc starting from third record
        $order = array();
        $order[] = 'id';
        $records = $tableManager->findRecords('mytable', null, null, $order, 1, 2);

        $this->assertCount(1, $records);
        $this->assertEquals(3, $records[0]['id']);
    }
}
