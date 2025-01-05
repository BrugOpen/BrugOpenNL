<?php

namespace BrugOpen\Tracking\Service;

use PHPUnit\Framework\TestCase;

class AISHUBFileReaderTest extends TestCase
{

    public function testReadRecordsFromCSVFile()
    {

        $testsDir = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/';
        $testFile = $testsDir . 'testfiles/aishub.csv';

        $records = [];

        $reader = new AISHubFileReader($testFile);

        while ($record = $reader->nextRecord()) {
            $records[] = $record;
        }

        $this->assertCount(10, $records);

        $this->assertEquals('244758001', $records[0]->getMmsi());
        $this->assertEquals('245142010', $records[9]->getMmsi());
    }

    public function testReadRecordsFromGzippedFile()
    {

        $testsDir = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/';
        $testFile = $testsDir . 'testfiles/aishub.csv.gz';

        $records = [];

        $reader = new AISHubFileReader($testFile);

        while ($record = $reader->nextRecord()) {
            $records[] = $record;
        }

        $this->assertCount(10, $records);

        $this->assertEquals('244758001', $records[0]->getMmsi());
        $this->assertEquals('245142010', $records[9]->getMmsi());
    }
}
