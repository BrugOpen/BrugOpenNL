<?php
use BrugOpen\Core\TestEventDispatcher;
use BrugOpen\Datex\Service\DatexFileParser;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Ndw\Service\NdwQueueProcessor;
use BrugOpen\Ndw\Service\SituationProcessor;
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
        // process file with single situation

        // assert situation processed

        // assert situation event posted
    }

    public function testProcessFileSnapshot()
    {
        // process file with snapshot

        // assert situations processed

        // assert situation events posted

        // assert snapshot event posted
    }

    public function testProcessKeepAliveDeliveryBreak()
    {
        // process file with KeepAlive + DeliveryBreak

        // assert event posted

    }

    public function ts() 
    {

        $log = new \Monolog\Logger('SituationProcessor');
        $log->pushHandler(new TestHandler());

    }

}
