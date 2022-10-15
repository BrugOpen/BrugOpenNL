<?php
use BrugOpen\Core\TestEventDispatcher;
use BrugOpen\Datex\Service\DatexFileParser;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Ndw\Service\SituationProcessor;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class SituationProcessorTest extends TestCase
{

    public function testProcessSingleNewSituation()
    {
        $log = new \Monolog\Logger('SituationProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $eventDispatcher = new TestEventDispatcher();
        $situationProcessor = new SituationProcessor(null);
        $situationProcessor->setTableManager($tableManager);
        $situationProcessor->setEventDispatcher($eventDispatcher);
        $situationProcessor->setLog($log);

        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-single-situation-push-version1.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getPayloadPublication());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getSituations());

        $situations = $logicalModel->getPayloadPublication()->getSituations();

        $this->assertCount(1, $situations);

        $situation = $situations[0];

        $publicationTime = $logicalModel->getPayloadPublication()->getPublicationTime();

        $situationProcessor->processSituation($situation, $publicationTime);

        // assert situation record inserted
        $criteria = array();
        $criteria['id'] = 'NDW04_NLGRQ000600502900272_53325436';
        $records = $tableManager->findRecords('bo_situation', $criteria);

        $this->assertNotEmpty($records);
        $this->assertCount(1, $records);

        $record = $records[0];
        $this->assertEquals('NDW04_NLGRQ000600502900272_53325436', $record['id']);
        $this->assertEquals('1', $record['version']);
        $this->assertArrayNotHasKey('operation_id', $record);
        $this->assertEquals('53.2276455', $record['lat']);
        $this->assertEquals('6.5917772', $record['lng']);
        $this->assertEquals(1652369466, $record['datetime_start']);
        $this->assertEquals(1652369466, $record['time_start']->getTimestamp());
        $this->assertArrayNotHasKey('datetime_end', $record);
        $this->assertArrayNotHasKey('time_end', $record);
        $this->assertEquals('implemented', $record['status']);
        $this->assertEquals('certain', $record['probability']);
        $this->assertEquals('1652369466', $record['datetime_version']);
        $this->assertEquals(1652369466, $record['version_time']->getTimestamp());
        $this->assertEquals('1652369467', $record['first_publication']);
        $this->assertEquals(1652369467, $record['first_publication_time']->getTimestamp());
        $this->assertEquals('1652369467', $record['last_publication']);
        $this->assertEquals(1652369467, $record['last_publication_time']->getTimestamp());

        // assert situation event dispatched

        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(1, $postedEvents);

        $postedEvent = $postedEvents[0];
        $this->assertEquals('Ndw.Situation.update', $postedEvent['name']);

        $params = $postedEvent['params'];
        $this->assertTrue(is_array($params));
        $this->assertCount(1, $params);
        $this->assertEquals('NDW04_NLGRQ000600502900272_53325436', $params[0]);
    }

    public function testProcessSingleUpdatedSituation()
    {
        $log = new \Monolog\Logger('SituationProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $eventDispatcher = new TestEventDispatcher();
        $situationProcessor = new SituationProcessor(null);
        $situationProcessor->setTableManager($tableManager);
        $situationProcessor->setEventDispatcher($eventDispatcher);
        $situationProcessor->setLog($log);

        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-single-situation-push-version1.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getPayloadPublication());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getSituations());

        $situations = $logicalModel->getPayloadPublication()->getSituations();

        $this->assertCount(1, $situations);

        $situation = $situations[0];

        $publicationTime = $logicalModel->getPayloadPublication()->getPublicationTime();

        $situationProcessor->processSituation($situation, $publicationTime);

        // assert situation record inserted
        $criteria = array();
        $criteria['id'] = 'NDW04_NLGRQ000600502900272_53325436';
        $records = $tableManager->findRecords('bo_situation', $criteria);

        $this->assertNotEmpty($records);
        $this->assertCount(1, $records);

        $record = $records[0];
        $this->assertEquals('NDW04_NLGRQ000600502900272_53325436', $record['id']);
        $this->assertEquals('1', $record['version']);
        $this->assertArrayNotHasKey('operation_id', $record);
        $this->assertEquals('53.2276455', $record['lat']);
        $this->assertEquals('6.5917772', $record['lng']);
        $this->assertEquals(1652369466, $record['datetime_start']);
        $this->assertEquals(1652369466, $record['time_start']->getTimestamp());
        $this->assertArrayNotHasKey('datetime_end', $record);
        $this->assertArrayNotHasKey('time_end', $record);
        $this->assertEquals('implemented', $record['status']);
        $this->assertEquals('certain', $record['probability']);
        $this->assertEquals('1652369466', $record['datetime_version']);
        $this->assertEquals(1652369466, $record['version_time']->getTimestamp());
        $this->assertEquals('1652369467', $record['first_publication']);
        $this->assertEquals(1652369467, $record['first_publication_time']->getTimestamp());
        $this->assertEquals('1652369467', $record['last_publication']);
        $this->assertEquals(1652369467, $record['last_publication_time']->getTimestamp());

        // assert situation event dispatched

        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(1, $postedEvents);

        $postedEvent = $postedEvents[0];
        $this->assertEquals('Ndw.Situation.update', $postedEvent['name']);

        $params = $postedEvent['params'];
        $this->assertTrue(is_array($params));
        $this->assertCount(1, $params);
        $this->assertEquals('NDW04_NLGRQ000600502900272_53325436', $params[0]);

        // now process second version

        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-single-situation-push-version2.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getPayloadPublication());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getSituations());

        $situations = $logicalModel->getPayloadPublication()->getSituations();

        $this->assertCount(1, $situations);

        $situation = $situations[0];

        $publicationTime = $logicalModel->getPayloadPublication()->getPublicationTime();

        $situationProcessor->processSituation($situation, $publicationTime);

        // assert second situation record inserted
        $criteria = array();
        $criteria['id'] = 'NDW04_NLGRQ000600502900272_53325436';
        $records = $tableManager->findRecords('bo_situation', $criteria);

        $this->assertNotEmpty($records);
        $this->assertCount(2, $records);

        $record = $records[0];
        $this->assertEquals('NDW04_NLGRQ000600502900272_53325436', $record['id']);
        $this->assertEquals('1', $record['version']);
        $this->assertArrayNotHasKey('operation_id', $record);
        $this->assertEquals('53.2276455', $record['lat']);
        $this->assertEquals('6.5917772', $record['lng']);
        $this->assertEquals(1652369466, $record['datetime_start']);
        $this->assertEquals(1652369466, $record['time_start']->getTimestamp());
        $this->assertArrayNotHasKey('datetime_end', $record);
        $this->assertArrayNotHasKey('time_end', $record);
        $this->assertEquals('implemented', $record['status']);
        $this->assertEquals('certain', $record['probability']);
        $this->assertEquals('1652369466', $record['datetime_version']);
        $this->assertEquals(1652369466, $record['version_time']->getTimestamp());
        $this->assertEquals('1652369467', $record['first_publication']);
        $this->assertEquals(1652369467, $record['first_publication_time']->getTimestamp());
        $this->assertEquals('1652369467', $record['last_publication']);
        $this->assertEquals(1652369467, $record['last_publication_time']->getTimestamp());

        $record = $records[1];
        $this->assertEquals('NDW04_NLGRQ000600502900272_53325436', $record['id']);
        $this->assertEquals('2', $record['version']);
        $this->assertArrayNotHasKey('operation_id', $record);
        $this->assertEquals('53.2276455', $record['lat']);
        $this->assertEquals('6.5917772', $record['lng']);
        $this->assertEquals(1652369466, $record['datetime_start']);
        $this->assertEquals(1652369466, $record['time_start']->getTimestamp());
        $this->assertEquals('1652370126', $record['datetime_end']);
        $this->assertEquals(1652370126, $record['time_end']->getTimestamp());
        $this->assertEquals('beingTerminated', $record['status']);
        $this->assertEquals('certain', $record['probability']);
        $this->assertEquals('1652370126', $record['datetime_version']);
        $this->assertEquals(1652370126, $record['version_time']->getTimestamp());
        $this->assertEquals('1652370126', $record['first_publication']);
        $this->assertEquals(1652370126, $record['first_publication_time']->getTimestamp());
        $this->assertEquals('1652370126', $record['last_publication']);
        $this->assertEquals(1652370126, $record['last_publication_time']->getTimestamp());

        // assert second event posted

        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(2, $postedEvents);

        $postedEvent = $postedEvents[1];
        $this->assertEquals('Ndw.Situation.update', $postedEvent['name']);

        $params = $postedEvent['params'];
        $this->assertTrue(is_array($params));
        $this->assertCount(1, $params);
        $this->assertEquals('NDW04_NLGRQ000600502900272_53325436', $params[0]);
    }

    public function testProcessSnapshot()
    {
        $log = new \Monolog\Logger('SituationProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $eventDispatcher = new TestEventDispatcher();
        $situationProcessor = new SituationProcessor(null);
        $situationProcessor->setTableManager($tableManager);
        $situationProcessor->setEventDispatcher($eventDispatcher);
        $situationProcessor->setLog($log);

        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-snapshot.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getPayloadPublication());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getSituations());

        $situations = $logicalModel->getPayloadPublication()->getSituations();

        $this->assertCount(36, $situations);

        $publicationTime = $logicalModel->getPayloadPublication()->getPublicationTime();

        foreach ($situations as $situation) {

            $situationProcessor->processSituation($situation, $publicationTime);
        }

        $records = $tableManager->findRecords('bo_situation');

        $this->assertCount(36, $records);

        // certain situations
        $certainSituations = array();
        $certainSituations[] = 'NDW04_NLNWG002260443400105_53143259';
        $certainSituations[] = 'NDW04_NLLWR000270322300426_53152780';
        $certainSituations[] = 'NDW04_NLGRQ000601013900364_53121689';
        $certainSituations[] = 'NDW04_NLCPI0211D0518200003_53113614';

        $record = $records[0];

        $this->assertEquals('NDW04_NLGRU000605560900877_53022369', $record['id']);
        $this->assertEquals(1, $record['version']);

        // assert triggered events

        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(4, $postedEvents);

        foreach ($certainSituations as $i => $situationId) {

            $this->assertEquals($situationId, $postedEvents[$i]['params'][0]);
        }

        // assert uncertain situations have operation_id 0

        foreach ($records as $record) {

            $situationId = $record['id'];

            if (! in_array($situationId, $certainSituations)) {

                $this->assertArrayHasKey('operation_id', $record);
                $this->assertEquals(0, $record['operation_id']);
            }
        }

        // assert certain situations have no operation_id

        foreach ($records as $record) {

            $situationId = $record['id'];

            if (in_array($situationId, $certainSituations)) {

                $this->assertArrayNotHasKey('operation_id', $record);
            }
        }
    }

    public function testFindSituationIdsWithoutOperationId()
    {
        $tableManager = new MemoryTableManager();
        $situationProcessor = new SituationProcessor(null);
        $situationProcessor->setTableManager($tableManager);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertEmpty($situationsWithoutOperationId);

        $record = array();
        $record['id'] = 'foo1';
        $record['operation_id'] = 0;

        $tableManager->insertRecord('bo_situation', $record);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertEmpty($situationsWithoutOperationId);

        $record['id'] = 'foo2';
        $record['operation_id'] = 1;

        $tableManager->insertRecord('bo_situation', $record);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertEmpty($situationsWithoutOperationId);

        $record['id'] = 'foo3';
        $record['operation_id'] = null;

        $tableManager->insertRecord('bo_situation', $record);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertNotEmpty($situationsWithoutOperationId);
        $this->assertCount(1, $situationsWithoutOperationId);
        $this->assertTrue(in_array('foo3', $situationsWithoutOperationId));

        $record['id'] = 'foo4';
        $record['operation_id'] = null;

        $tableManager->insertRecord('bo_situation', $record);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertNotEmpty($situationsWithoutOperationId);
        $this->assertCount(2, $situationsWithoutOperationId);
        $this->assertTrue(in_array('foo3', $situationsWithoutOperationId));
        $this->assertTrue(in_array('foo4', $situationsWithoutOperationId));
    }

    public function testMarkUncertainSituationsIgnored()
    {
        $log = new \Monolog\Logger('SituationProcessor');
        $log->pushHandler(new TestHandler());

        $tableManager = new MemoryTableManager();
        $eventDispatcher = new TestEventDispatcher();
        $situationProcessor = new SituationProcessor(null);
        $situationProcessor->setTableManager($tableManager);
        $situationProcessor->setEventDispatcher($eventDispatcher);
        $situationProcessor->setLog($log);

        $insertRecords = array();

        $record = array();
        $record['id'] = 'NDW04_NLALK002340558100405_53182296';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['probability'] = 'riskOf';

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'NDW04_NLALK002340558100405_53182296';
        $record['version'] = 2;
        $record['operation_id'] = null;
        $record['probability'] = 'riskOf';

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'NDW04_NLALK002340558100405_53182296';
        $record['version'] = 3;
        $record['operation_id'] = null;
        $record['probability'] = 'riskOf';

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'NDW04_NLALK002340558100405_53182296';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['probability'] = 'riskOf';

        $insertRecords[] = $record;

        // now a situation that went from riskOf to certain

        $record = array();
        $record['id'] = 'NDW04_NLALK002340558100405_53182297';
        $record['version'] = 1;
        $record['operation_id'] = null;
        $record['probability'] = 'riskOf';

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'NDW04_NLALK002340558100405_53182297';
        $record['version'] = 2;
        $record['operation_id'] = 123;
        $record['probability'] = 'certain';

        $insertRecords[] = $record;

        $tableManager->insertRecords('bo_situation', $insertRecords);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertCount(2, $situationsWithoutOperationId);
        $this->assertEquals('NDW04_NLALK002340558100405_53182296', $situationsWithoutOperationId[0]);
        $this->assertEquals('NDW04_NLALK002340558100405_53182297', $situationsWithoutOperationId[1]);

        // find records, assert operation id null
        $criteria = array();
        $criteria['id'] = 'NDW04_NLALK002340558100405_53182296';
        $records = $tableManager->findRecords('bo_situation', $criteria);

        $this->assertCount(4, $records);

        foreach ($records as $record) {

            $this->assertNull($record['operation_id']);
        }

        // now mark situations that have no certain version as ignored

        $situationProcessor->markUncertainSituationsIgnored();

        // find records, assert operation id 0

        $criteria = array();
        $criteria['id'] = 'NDW04_NLALK002340558100405_53182296';
        $records = $tableManager->findRecords('bo_situation', $criteria);

        $this->assertCount(4, $records);

        foreach ($records as $record) {

            $this->assertNotNull($record['operation_id']);
            $this->assertEquals(0, $record['operation_id']);
        }

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertCount(0, $situationsWithoutOperationId);

        // make sure all situation versions from actual operation are updated

        $criteria = array();
        $criteria['id'] = 'NDW04_NLALK002340558100405_53182297';
        $records = $tableManager->findRecords('bo_situation', $criteria);

        $this->assertCount(2, $records);

        foreach ($records as $record) {

            $this->assertNotNull($record['operation_id']);
            $this->assertEquals(123, $record['operation_id']);
        }
    }

    public function testCheckUnfinishedGoneOperations()
    {

        $log = new \Monolog\Logger('SituationProcessor');
        $testHandler = new TestHandler();
        $log->pushHandler($testHandler);

        $tableManager = new MemoryTableManager();
        $eventDispatcher = new TestEventDispatcher();
        $situationProcessor = new SituationProcessor(null);
        $situationProcessor->setTableManager($tableManager);
        $situationProcessor->setEventDispatcher($eventDispatcher);
        $situationProcessor->setLog($log);

        // create a few finished operations

        $insertRecords = array();

        $record = array();
        $record['id'] = 123;
        $record['bridge'] = 12;
        $record['finished'] = 1;

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 124;
        $record['bridge'] = 13;
        $record['finished'] = 1;

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 125;
        $record['bridge'] = 15;
        $record['finished'] = 1;

        $insertRecords[] = $record;

        // create a few active (unfinished) operations

        $record = array();
        $record['id'] = 201;
        $record['event_id'] = 'SITUATION_1201';
        $record['bridge'] = 12;
        $record['finished'] = 0;
        $record['time_start'] = new \DateTime('2022-05-01 20:33:00');
        $record['time_end'] = new \DateTime('2022-05-01 20:39:00');
        // last published 2022-05-01 20:34:56

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 202;
        $record['event_id'] = 'SITUATION_1202';
        $record['bridge'] = 22;
        $record['finished'] = 0;
        // last published 2022-05-01 20:34:56

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 203;
        $record['event_id'] = 'SITUATION_1203';
        $record['bridge'] = 13;
        $record['finished'] = 0;
        // last published 2022-05-01 22:34:56

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 204;
        $record['event_id'] = 'SITUATION_1204';
        $record['bridge'] = 14;
        $record['finished'] = 0;
        // last published 2022-05-01 22:34:56

        $insertRecords[] = $record;

        $tableManager->insertRecords('bo_operation', $insertRecords);

        // create corresponding situation records

        $insertRecords = array();

        $record = array();
        $record['id'] = 'SITUATION_1201';
        $record['version'] = 1;
        $record['last_publication_time'] = new \DateTime('2022-05-01 20:24:34');

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'SITUATION_1201';
        $record['version'] = 2;
        $record['last_publication_time'] = new \DateTime('2022-05-01 20:34:56');

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'SITUATION_1202';
        $record['version'] = 1;
        $record['last_publication_time'] = new \DateTime('2022-05-01 20:34:56');

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'SITUATION_1203';
        $record['version'] = 1;
        $record['last_publication_time'] = new \DateTime('2022-05-01 22:34:56');

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'SITUATION_1204';
        $record['version'] = 1;
        $record['last_publication_time'] = new \DateTime('2022-05-01 22:34:56');

        $insertRecords[] = $record;

        $tableManager->insertRecords('bo_situation', $insertRecords);

        // assert unfinished record count

        $keys = array();
        $keys['finished'] = 0;

        $numUnfinishedOperations = $tableManager->countRecords('bo_operation', $keys);

        $this->assertEquals(4, $numUnfinishedOperations);

        // call checkUnfinishedGoneOperations()

        $publicationTime = new \DateTime('2022-05-01 22:34:56');

        $situationProcessor->checkUnfinishedGoneOperations($publicationTime);

        // assert operations are marked finished

        $keys = array();
        $keys['finished'] = 0;

        $numUnfinishedOperations = $tableManager->countRecords('bo_operation', $keys);

        $this->assertEquals(2, $numUnfinishedOperations);

        // assert operations have datetime gone and datetime end values

        $operation = $tableManager->findRecord('bo_operation', array('id' => 201));

        $this->assertNotEmpty($operation);
        $this->assertEquals(1, $operation['finished']);
        $this->assertNotNull($operation['time_end']);
        // time_end was already set
        $this->assertEquals('2022-05-01 20:39:00', $operation['time_end']->format('Y-m-d H:i:s'));
        $this->assertNotNull($operation['time_gone']);
        $this->assertEquals('2022-05-01 22:34:56', $operation['time_gone']->format('Y-m-d H:i:s'));

        $operation = $tableManager->findRecord('bo_operation', array('id' => 202));

        $this->assertNotEmpty($operation);
        $this->assertEquals(1, $operation['finished']);
        $this->assertNotNull($operation['time_end']);
        // time_end is taken from expire publication time
        $this->assertEquals('2022-05-01 22:34:56', $operation['time_end']->format('Y-m-d H:i:s'));
        $this->assertNotNull($operation['time_gone']);
        $this->assertEquals('2022-05-01 22:34:56', $operation['time_gone']->format('Y-m-d H:i:s'));

        // assert operation update events have been fired 
        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(2, $postedEvents);

        $this->assertEquals('Operation.update', $postedEvents[0]['name']);
        $this->assertCount(1, $postedEvents[0]['params']);
        $this->assertEquals(201, $postedEvents[0]['params'][0]);

        $this->assertEquals('Operation.update', $postedEvents[1]['name']);
        $this->assertCount(1, $postedEvents[1]['params']);
        $this->assertEquals(202, $postedEvents[1]['params'][0]);

    }
}
