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
        $record['situation_id'] = 'foo1';
        $record['operation_id'] = 0;

        $tableManager->insertRecord('bo_situation', $record);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertEmpty($situationsWithoutOperationId);

        $record['situation_id'] = 'foo2';
        $record['operation_id'] = 1;

        $tableManager->insertRecord('bo_situation', $record);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertEmpty($situationsWithoutOperationId);

        $record['situation_id'] = 'foo3';
        $record['operation_id'] = null;

        $tableManager->insertRecord('bo_situation', $record);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertNotEmpty($situationsWithoutOperationId);
        $this->assertCount(1, $situationsWithoutOperationId);
        $this->assertTrue(in_array('foo3', $situationsWithoutOperationId));

        $record['situation_id'] = 'foo4';
        $record['operation_id'] = null;

        $tableManager->insertRecord('bo_situation', $record);

        $situationsWithoutOperationId = $situationProcessor->findSituationIdsWithoutOperationId();

        $this->assertNotEmpty($situationsWithoutOperationId);
        $this->assertCount(2, $situationsWithoutOperationId);
        $this->assertTrue(in_array('foo3', $situationsWithoutOperationId));
        $this->assertTrue(in_array('foo4', $situationsWithoutOperationId));
    }
}
