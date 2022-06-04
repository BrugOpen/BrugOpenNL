<?php
use BrugOpen\Core\EventDispatcher;
use BrugOpen\Db\Service\MemoryTableManager;
use BrugOpen\Ndw\Service\SituationProcessor;
use PHPUnit\Framework\TestCase;

class SituationProcessorTest extends TestCase
{

    public function testProcessSingleNewSituation()
    {
        $tableManager = new MemoryTableManager();
        $eventDispatcher = new EventDispatcher();
        $situationProcessor = new SituationProcessor(null);
        $situationProcessor->setTableManager($tableManager);
        $situationProcessor->setEventDispatcher($eventDispatcher);
    }

    public function testProcessSingleUpdatedSituation()
    {}

    public function testProcessSnapshot()
    {}
}
