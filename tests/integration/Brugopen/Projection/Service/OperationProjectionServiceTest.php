<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Model\Bridge;
use BrugOpen\Projection\Model\ProjectedBridgePassage;
use PHPUnit\Framework\TestCase;

class OperationProjectionServiceTest extends TestCase
{

    public function testCreateOperationProjectionsSinglePassage()
    {

        $bridge = new Bridge();

        $now = time();

        $maxStandardDeviation = 90;
        $minOperationProbability = 0.5;
        $maxDateTimePassage = new \DateTime('@' . ($now + (30 * 60)));

        // projected passage in 400 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (400)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections = array($passageProjection);

        $operationProjectionService = new OperationProjectionService();

        $operationProjections = $operationProjectionService->createOperationProjections($bridge, $passageProjections, $maxStandardDeviation, $minOperationProbability, $maxDateTimePassage);

        $this->assertCount(1, $operationProjections);

        $operationProjection = $operationProjections[0];

        $this->assertEquals($now + 400 - 120, $operationProjection->getTimeStart()->getTimestamp());
        $this->assertEquals($now + 400 + 120, $operationProjection->getTimeEnd()->getTimestamp());

        $duration = $operationProjection->getTimeEnd()->getTimestamp() - $operationProjection->getTimeStart()->getTimestamp();
        $this->assertEquals(240, $duration);

        $this->assertEquals(2, $operationProjection->getCertainty());

        $projectedBridgePassages = $operationProjection->getProjectedPassages();
        $this->assertNotEmpty($projectedBridgePassages);
        $this->assertCount(1, $projectedBridgePassages);
        $this->assertEquals($passageProjection, $projectedBridgePassages[0]);
    }

    public function testCreateOperationProjectionsTwoCombinedPassages()
    {

        $bridge = new Bridge();

        $passageProjections = array();

        $now = time();

        $maxStandardDeviation = 90;
        $minOperationProbability = 0.5;
        $maxDateTimePassage = new \DateTime('@' . ($now + (30 * 60)));

        // first projected passage in 400 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (400)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setId(123);
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections[] = $passageProjection;

        // second projected passage in 550 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (550)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setId(234);
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections[] = $passageProjection;

        $operationProjectionService = new OperationProjectionService();
        $operationProjections = $operationProjectionService->createOperationProjections($bridge, $passageProjections, $maxStandardDeviation, $minOperationProbability, $maxDateTimePassage);

        $this->assertCount(1, $operationProjections);

        $operationProjection = $operationProjections[0];

        // first ship must wait for second ship
        // start time is the second passage time minus the normal operation duration
        $this->assertEquals($now + 550 - 240, $operationProjection->getTimeStart()->getTimestamp());

        // end time is the second passage time plus half of the normal operation duration
        $this->assertEquals($now + 550 + (240 / 2), $operationProjection->getTimeEnd()->getTimestamp());

        $duration = $operationProjection->getTimeEnd()->getTimestamp() - $operationProjection->getTimeStart()->getTimestamp();
        $this->assertEquals(240 * 1.5, $duration);

        $this->assertEquals(2, $operationProjection->getCertainty());

        $projectedBridgePassages = $operationProjection->getProjectedPassages();
        $this->assertNotEmpty($projectedBridgePassages);
        $this->assertCount(2, $projectedBridgePassages);
        $this->assertEquals(123, $projectedBridgePassages[0]->getId());
        $this->assertEquals(234, $projectedBridgePassages[1]->getId());
    }

    public function testCreateOperationProjectionsTwoSeparatePassages()
    {

        $bridge = new Bridge();

        $passageProjections = array();

        $now = time();

        $maxStandardDeviation = 90;
        $minOperationProbability = 0.5;
        $maxDateTimePassage = new \DateTime('@' . ($now + (30 * 60)));

        // first projected passage in 400 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (400)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setId(123);
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections[] = $passageProjection;

        // second projected passage in 1000 seconds
        $datetimeProjectedPassage = new \DateTime('@' . ($now + (1000)));

        $passageProjection = new ProjectedBridgePassage();
        $passageProjection->setId(234);
        $passageProjection->setDatetimeProjectedPassage($datetimeProjectedPassage);
        $passageProjection->setOperationProbability(1);
        $passageProjection->setStandardDeviation(30);

        $passageProjections[] = $passageProjection;

        $operationProjectionService = new OperationProjectionService();
        $operationProjections = $operationProjectionService->createOperationProjections($bridge, $passageProjections, $maxStandardDeviation, $minOperationProbability, $maxDateTimePassage);

        $this->assertCount(2, $operationProjections);

        // check first operation projection

        $operationProjection = $operationProjections[0];

        // start time is the second passage time minus half the normal operation duration
        $this->assertEquals($now + 400 - (240 / 2), $operationProjection->getTimeStart()->getTimestamp());

        // end time is the second passage time plus half of the normal operation duration
        $this->assertEquals($now + 400 + (240 / 2), $operationProjection->getTimeEnd()->getTimestamp());

        $duration = $operationProjection->getTimeEnd()->getTimestamp() - $operationProjection->getTimeStart()->getTimestamp();
        $this->assertEquals(240, $duration);

        $this->assertEquals(2, $operationProjection->getCertainty());

        $projectedBridgePassages = $operationProjection->getProjectedPassages();
        $this->assertNotEmpty($projectedBridgePassages);
        $this->assertCount(1, $projectedBridgePassages);
        $this->assertEquals(123, $projectedBridgePassages[0]->getId());

        // check second operation projection

        $operationProjection = $operationProjections[1];

        // start time is the second passage time minus half the normal operation duration
        $this->assertEquals($now + 1000 - (240 / 2), $operationProjection->getTimeStart()->getTimestamp());

        // end time is the second passage time plus half of the normal operation duration
        $this->assertEquals($now + 1000 + (240 / 2), $operationProjection->getTimeEnd()->getTimestamp());

        $duration = $operationProjection->getTimeEnd()->getTimestamp() - $operationProjection->getTimeStart()->getTimestamp();
        $this->assertEquals(240, $duration);

        $this->assertEquals(2, $operationProjection->getCertainty());

        $projectedBridgePassages = $operationProjection->getProjectedPassages();
        $this->assertNotEmpty($projectedBridgePassages);
        $this->assertCount(1, $projectedBridgePassages);
        $this->assertEquals(234, $projectedBridgePassages[0]->getId());
    }
}
