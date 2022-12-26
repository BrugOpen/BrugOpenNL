<?php
use BrugOpen\Core\TestEventDispatcher;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Ndw\Service\NdwQueueProcessor;
use BrugOpen\Ndw\Service\SituationEventProcessor;
use BrugOpen\Ndw\Service\SituationProcessor;
use BrugOpen\Service\BridgeIndexService;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class NdwQueueProcessorTest extends TestCase
{

    public function testGetQueueFiles()
    {

        $testFilesDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR;
        $queueDir = $testFilesDir . 'ndw-queue' . DIRECTORY_SEPARATOR;

        $queueProcessor = new NdwQueueProcessor(null);
        $queueFiles = $queueProcessor->getQueueFiles($queueDir);

        $this->assertCount(27, $queueFiles);

        // test first and last file

        $this->assertEquals('brugdata-20220619175002-285439-push.xml.gz', basename($queueFiles[0]));
        $this->assertEquals('brugdata-20220619175933-290870-push.xml.gz', basename($queueFiles[26]));

    }

    public function testProcessFileSingleSituation()
    {
        $log = new \Monolog\Logger('SituationProcessor');
        $log->pushHandler(new TestHandler());

        $eventDispatcher = new TestEventDispatcher();

        $queueProcessor = new NdwQueueProcessor(null);
        $queueProcessor->setLog($log);

        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_operation', 'id', 101);
        $bridgeIndexService = new BridgeIndexService();
        $bridgeIndexService->setTableManager($tableManager);
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setBridgeIndexService($bridgeIndexService);
        $situationEventProcessor->setLog($log);

        $situationProcessor = new SituationProcessor(null);
        $situationProcessor->setTableManager($tableManager);
        $situationProcessor->setEventDispatcher($eventDispatcher);
        $situationProcessor->setLog($log);

        $queueProcessor->setSituationProcessor($situationProcessor);

        $testFilesDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR;
        $testFile = $testFilesDir . 'brugdata-single-situation-push-version1.xml.gz';

        // process file with single situation

        $queueProcessor->processQueueFile($testFile);

        // assert situation processed

        $situations = $tableManager->findRecords('bo_situation');

        $this->assertNotNull($situations);

        $this->assertCount(1, $situations);

        // assert situation event posted

        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(1, $postedEvents);

        $postedEvent = $postedEvents[0];

        $this->assertEquals('Ndw.Situation.update', $postedEvent['name']);

        $eventParams = $postedEvent['params'];
        $this->assertNotEmpty($eventParams);

        $this->assertEquals('NDW04_NLGRQ000600502900272_53325436', $eventParams[0]);

        // assert no DeliveryBreak event posted

        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertNotEmpty($postedEvents);

        $hasDeliveryBreakEvent = false;

        foreach ($postedEvents as $postedEvent) {

            if ($postedEvent['name'] == 'Ndw.DeliveryBreak') {

                $hasDeliveryBreakEvent = true;
                break;

            }

        }

        $this->assertFalse($hasDeliveryBreakEvent);
        
    }

    public function testProcessFileSnapshot()
    {
        $log = new \Monolog\Logger('SituationProcessor');
        $testLogHandler = new TestHandler();
        $log->pushHandler($testLogHandler);

        $eventDispatcher = new TestEventDispatcher();

        $queueProcessor = new NdwQueueProcessor(null);
        $queueProcessor->setLog($log);

        $tableManager = new MemoryTableManager();
        $tableManager->setAutoIncrement('bo_operation', 'id', 101);
        $bridgeIndexService = new BridgeIndexService();
        $bridgeIndexService->setTableManager($tableManager);
        $eventDispatcher = new TestEventDispatcher();
        $situationEventProcessor = new SituationEventProcessor(null);
        $situationEventProcessor->setTableManager($tableManager);
        $situationEventProcessor->setEventDispatcher($eventDispatcher);
        $situationEventProcessor->setBridgeIndexService($bridgeIndexService);
        $situationEventProcessor->setLog($log);

        $situationProcessor = new SituationProcessor(null);
        $situationProcessor->setTableManager($tableManager);
        $situationProcessor->setEventDispatcher($eventDispatcher);
        $situationProcessor->setLog($log);

        $queueProcessor->setSituationProcessor($situationProcessor);

        // prepare existing operations

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
        $record['time_start'] = new \DateTime('2022-05-09 02:33:00');
        $record['time_end'] = new \DateTime('2022-05-09 02:39:00');
        // last published 2022-05-09 02:39:00

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 202;
        $record['event_id'] = 'SITUATION_1202';
        $record['bridge'] = 22;
        $record['finished'] = 0;
        // last published 2022-05-09 03:09:39

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
        $record['last_publication_time'] = new \DateTime('2022-05-09 02:31:00');

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'SITUATION_1201';
        $record['version'] = 2;
        $record['last_publication_time'] = new \DateTime('2022-05-09 02:39:00');

        $insertRecords[] = $record;

        $record = array();
        $record['id'] = 'SITUATION_1202';
        $record['version'] = 1;
        $record['last_publication_time'] = new \DateTime('2022-05-09T03:09:39Z');

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

        $testFilesDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR;

        $testFile = $testFilesDir . 'brugdata-snapshot.xml.gz';

        // process file with snapshot

        $queueProcessor->processQueueFile($testFile);

        // assert situations processed

        $situations = $tableManager->findRecords('bo_situation');

        $this->assertNotNull($situations);

        $this->assertCount(41, $situations);

        // count ignored situations

        $unfinishedSituations = $tableManager->findRecords('bo_situation', array('operation_id' => 0));

        $this->assertCount(32, $unfinishedSituations);

        // assert situation events posted

        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertCount(7, $postedEvents);

        $this->assertEquals('Ndw.Situation.update', $postedEvents[0]['name']);
        $this->assertEquals('NDW04_NLNWG002260443400105_53143259', $postedEvents[0]['params'][0]);

        $this->assertEquals('Ndw.Situation.update', $postedEvents[1]['name']);
        $this->assertEquals('NDW04_NLLWR000270322300426_53152780', $postedEvents[1]['params'][0]);

        $this->assertEquals('Ndw.Situation.update', $postedEvents[2]['name']);
        $this->assertEquals('NDW04_NLGRQ000601013900364_53121689', $postedEvents[2]['params'][0]);

        $this->assertEquals('Ndw.Situation.update', $postedEvents[3]['name']);
        $this->assertEquals('NDW04_NLCPI0211D0518200003_53113614', $postedEvents[3]['params'][0]);

        $this->assertEquals('Operation.update', $postedEvents[4]['name']);
        $this->assertEquals(201, $postedEvents[4]['params'][0]);

        $this->assertEquals('Operation.update', $postedEvents[5]['name']);
        $this->assertEquals(203, $postedEvents[5]['params'][0]);

        $this->assertEquals('Operation.update', $postedEvents[6]['name']);
        $this->assertEquals(204, $postedEvents[6]['params'][0]);

        // assert gone operations now closed

        $keys = array();
        $keys['finished'] = 0;

        $numUnfinishedOperations = $tableManager->countRecords('bo_operation', $keys);

        $this->assertEquals(1, $numUnfinishedOperations);

        $unfinishedOperations = $tableManager->findRecords('bo_operation', $keys);
        $this->assertCount(1, $unfinishedOperations);
        $this->assertEquals(202, $unfinishedOperations[0]['id']);

        // assert no DeliveryBreak event posted

        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertNotEmpty($postedEvents);

        $hasDeliveryBreakEvent = false;

        foreach ($postedEvents as $postedEvent) {

            if ($postedEvent['name'] == 'Ndw.DeliveryBreak') {

                $hasDeliveryBreakEvent = true;
                break;

            }

        }

        $this->assertFalse($hasDeliveryBreakEvent);

    }

    public function testProcessKeepAliveDeliveryBreak()
    {

        $log = new \Monolog\Logger('SituationProcessor');
        $testLogHandler = new TestHandler();
        $log->pushHandler($testLogHandler);

        $eventDispatcher = new TestEventDispatcher();

        $queueProcessor = new NdwQueueProcessor(null);
        $queueProcessor->setLog($log);

        $queueProcessor->setEventDispatcher($eventDispatcher);

        // process file with KeepAlive + DeliveryBreak

        $testFilesDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR;

        $testFile = $testFilesDir . 'brugdata-only-keepalive-push.xml.gz';

        // process file with snapshot

        $queueProcessor->processQueueFile($testFile);

        // assert DeliveryBreak event posted

        $postedEvents = $eventDispatcher->getPostedEvents();

        $this->assertNotEmpty($postedEvents);

        $hasDeliveryBreakEvent = false;

        foreach ($postedEvents as $postedEvent) {

            if ($postedEvent['name'] == 'Ndw.DeliveryBreak') {

                $hasDeliveryBreakEvent = true;
                break;

            }

        }

        $this->assertTrue($hasDeliveryBreakEvent);

    }

}
