<?php
use BrugOpen\Datex\Service\DatexFileParser;
use PHPUnit\Framework\TestCase;

class DatexFileParserTest extends TestCase
{

    public function testParsePushMessageWithOnlyKeepAliveUnzipped()
    {
        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-only-keepalive-push.xml';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getExchange());

        $this->assertTrue($logicalModel->getExchange()
            ->getDeliveryBreak());
        $this->assertTrue($logicalModel->getExchange()
            ->getKeepAlive());
        $this->assertEquals('subscription', $logicalModel->getExchange()
            ->getRequestType());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSupplierIdentification());

        $this->assertEquals('nl', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getCountry());
        $this->assertEquals('NLNDW', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getNationalIdentifier());

        $this->assertNull($logicalModel->getPayloadPublication());
    }

    public function testParsePushMessageWithOnlyKeepAlive()
    {
        $this->assertTrue(in_array('compress.zlib', stream_get_wrappers()));

        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-only-keepalive-push.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getExchange());

        $this->assertTrue($logicalModel->getExchange()
            ->getDeliveryBreak());
        $this->assertTrue($logicalModel->getExchange()
            ->getKeepAlive());
        $this->assertEquals('subscription', $logicalModel->getExchange()
            ->getRequestType());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSupplierIdentification());

        $this->assertEquals('nl', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getCountry());
        $this->assertEquals('NLNDW', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getNationalIdentifier());

        $this->assertNull($logicalModel->getPayloadPublication());
    }

    public function testParsePushMessageWithExchangeAndPayload()
    {
        $this->assertTrue(in_array('compress.zlib', stream_get_wrappers()));

        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-exchange-payload-push.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getExchange());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSupplierIdentification());

        $this->assertEquals('nl', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getCountry());
        $this->assertEquals('NLNDW', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getNationalIdentifier());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSubscription());
        $this->assertEquals('operatingMode1', $logicalModel->getExchange()
            ->getSubscription()
            ->getOperatingMode());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSubscription()
            ->getSubscriptionStartTime());

        $this->assertEquals(strtotime('2022-05-09T10:22:49.86Z'), $logicalModel->getExchange()
            ->getSubscription()
            ->getSubscriptionStartTime()
            ->getTimestamp());

        $this->assertEquals('active', $logicalModel->getExchange()
            ->getSubscription()
            ->getSubscriptionState());
        $this->assertEquals('allElementUpdate', $logicalModel->getExchange()
            ->getSubscription()
            ->getUpdateMethod());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSubscription()
            ->getTarget());
        $this->assertEquals('https://example.com/api/push/', $logicalModel->getExchange()
            ->getSubscription()
            ->getTarget()
            ->getAddress());
        $this->assertEquals('HTTP', $logicalModel->getExchange()
            ->getSubscription()
            ->getTarget()
            ->getProtocol());

        $this->assertNotNull($logicalModel->getPayloadPublication());

        $this->assertEquals('nl', $logicalModel->getPayloadPublication()
            ->getLang());
        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getPublicationTime());
        $this->assertEquals(strtotime('2022-05-12T14:43:16.957Z'), $logicalModel->getPayloadPublication()
            ->getPublicationTime()
            ->getTimestamp());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getPublicationCreator());
        $this->assertEquals('nl', $logicalModel->getPayloadPublication()
            ->getPublicationCreator()
            ->getCountry());
        $this->assertEquals('NLNDW', $logicalModel->getPayloadPublication()
            ->getPublicationCreator()
            ->getNationalIdentifier());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getSituations());

        $situations = $logicalModel->getPayloadPublication()->getSituations();

        $this->assertCount(2, $situations);

        $situation = $situations[0];

        $this->assertNotNull($situation);
        $this->assertEquals('NDW04_NLKGZ002360564300056_53186614', $situation->getId());
        $this->assertEquals('4', $situation->getVersion());
        $this->assertEquals('unknown', $situation->getOverallSeverity());
        $this->assertNotNull($situation->getSituationVersionTime());
        $this->assertEquals(strtotime('2022-05-12T14:43:15.000Z'), $situation->getSituationVersionTime()
            ->getTimestamp());

        $this->assertNotNull($situation->getHeaderInformation());
        $this->assertEquals('noRestriction', $situation->getHeaderInformation()
            ->getConfidentiality());
        $this->assertEquals('real', $situation->getHeaderInformation()
            ->getInformationStatus());

        $this->assertNotNull($situation->getSituationRecord());

        $situationRecord = $situation->getSituationRecord();

        $this->assertEquals('NDW04_NLKGZ002360564300056_53186614_01', $situationRecord->getId());
        $this->assertEquals('4', $situationRecord->getVersion());

        $this->assertNotNull($situationRecord->getSituationRecordCreationTime());
        $this->assertEquals(strtotime('2022-05-12T14:43:15.000Z'), $situationRecord->getSituationRecordCreationTime()
            ->getTimestamp());

        $this->assertNotNull($situationRecord->getSituationRecordVersionTime());
        $this->assertEquals(strtotime('2022-05-12T14:43:15.000Z'), $situationRecord->getSituationRecordVersionTime()
            ->getTimestamp());

        $this->assertEquals('riskOf', $situationRecord->getProbabilityOfOccurrence());

        $this->assertNotNull($situationRecord->getSource());
        $this->assertNotNull($situationRecord->getSource()
            ->getValuesByLang());

        $valuesByLang = $situationRecord->getSource()->getValuesByLang();

        $this->assertEquals('NDW04', $valuesByLang['nl']);

        $this->assertNotNull($situationRecord->getValidity());

        $this->assertEquals('definedByValidityTimeSpec', $situationRecord->getValidity()
            ->getValidityStatus());
        $this->assertNotNull($situationRecord->getValidity()
            ->getValidityTimeSpecification());

        $this->assertNotNull($situationRecord->getValidity()
            ->getValidityTimeSpecification()
            ->getOverallStartTime());
        $this->assertEquals(strtotime('2022-05-12T14:43:00.000Z'), $situationRecord->getValidity()
            ->getValidityTimeSpecification()
            ->getOverallStartTime()
            ->getTimestamp());

        $this->assertNotNull($situationRecord->getValidity()
            ->getValidityTimeSpecification()
            ->getOverallEndTime());
        $this->assertEquals(strtotime('2022-05-12T14:49:00.000Z'), $situationRecord->getValidity()
            ->getValidityTimeSpecification()
            ->getOverallEndTime()
            ->getTimestamp());

        $this->assertNotNull($situationRecord->getGroupOfLocations());

        $this->assertNotNull($situationRecord->getGroupOfLocations()
            ->getLocationForDisplay());
        $this->assertEquals('52.4629378159954', $situationRecord->getGroupOfLocations()
            ->getLocationForDisplay()
            ->getLatitude());
        $this->assertEquals('4.812555912351', $situationRecord->getGroupOfLocations()
            ->getLocationForDisplay()
            ->getLongitude());

        $this->assertNotNull($situationRecord->getGroupOfLocations()
            ->getAlertCPoint());

        $this->assertEquals('8', $situationRecord->getGroupOfLocations()
            ->getAlertCPoint()
            ->getAlertCLocationCountryCode());
        $this->assertEquals('6.6', $situationRecord->getGroupOfLocations()
            ->getAlertCPoint()
            ->getAlertCLocationTableNumber());
        $this->assertEquals('A', $situationRecord->getGroupOfLocations()
            ->getAlertCPoint()
            ->getAlertCLocationTableVersion());

        $this->assertNotNull($situationRecord->getGroupOfLocations()
            ->getAlertCPoint()
            ->getAlertCDirection());
        $this->assertEquals('both', $situationRecord->getGroupOfLocations()
            ->getAlertCPoint()
            ->getAlertCDirection()
            ->getAlertCDirectionCoded());

        $this->assertNotNull($situationRecord->getGroupOfLocations()
            ->getAlertCPoint()
            ->getAlertCMethod2PrimaryPointLocation());
        $this->assertNotNull($situationRecord->getGroupOfLocations()
            ->getAlertCPoint()
            ->getAlertCMethod2PrimaryPointLocation()
            ->getAlertCLocation());
        $this->assertNotNull($situationRecord->getGroupOfLocations()
            ->getAlertCPoint()
            ->getAlertCMethod2PrimaryPointLocation()
            ->getAlertCLocation()
            ->getSpecificLocation());
        $this->assertEquals('10543', $situationRecord->getGroupOfLocations()
            ->getAlertCPoint()
            ->getAlertCMethod2PrimaryPointLocation()
            ->getAlertCLocation()
            ->getSpecificLocation());

        $this->assertNotNull($situationRecord->getGroupOfLocations()
            ->getPointByCoordinates());
        $this->assertNotNull($situationRecord->getGroupOfLocations()
            ->getPointByCoordinates()
            ->getPointCoordinates());
        $this->assertEquals('52.4629378159954', $situationRecord->getGroupOfLocations()
            ->getPointByCoordinates()
            ->getPointCoordinates()
            ->getLatitude());
        $this->assertEquals('4.812555912351', $situationRecord->getGroupOfLocations()
            ->getPointByCoordinates()
            ->getPointCoordinates()
            ->getLongitude());

        $this->assertEquals('approved', $situationRecord->getOperatorActionStatus());
        $this->assertEquals('mandatory', $situationRecord->getComplianceOption());
        $this->assertEquals('bridgeSwingInOperation', $situationRecord->getGeneralNetworkManagementType());
    }

    public function testParseSnapshot()
    {
        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-snapshot.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getExchange());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSupplierIdentification());

        $this->assertEquals('nl', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getCountry());
        $this->assertEquals('NLNDW', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getNationalIdentifier());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSubscription());
        $this->assertEquals('operatingMode3', $logicalModel->getExchange()
            ->getSubscription()
            ->getOperatingMode());
        $this->assertNotNull($logicalModel->getExchange()
            ->getSubscription()
            ->getSubscriptionStartTime());
        $this->assertEquals(strtotime('2022-03-17T10:46:26.016Z'), $logicalModel->getExchange()
            ->getSubscription()
            ->getSubscriptionStartTime()
            ->getTimestamp());
        $this->assertEquals('active', $logicalModel->getExchange()
            ->getSubscription()
            ->getSubscriptionState());
        $this->assertEquals('snapshot', $logicalModel->getExchange()
            ->getSubscription()
            ->getUpdateMethod());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSubscription()
            ->getTarget());
        $this->assertEmpty($logicalModel->getExchange()
            ->getSubscription()
            ->getTarget()
            ->getAddress());
        $this->assertEquals('HTTP', $logicalModel->getExchange()
            ->getSubscription()
            ->getTarget()
            ->getProtocol());

        $this->assertNotNull($logicalModel->getPayloadPublication());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getPublicationTime());
        $this->assertEquals(strtotime('2022-05-09T03:09:39.689Z'), $logicalModel->getPayloadPublication()
            ->getPublicationTime()
            ->getTimestamp());
        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getPublicationCreator());
        $this->assertEquals('nl', $logicalModel->getPayloadPublication()
            ->getPublicationCreator()
            ->getCountry());
        $this->assertEquals('NLNDW', $logicalModel->getPayloadPublication()
            ->getPublicationCreator()
            ->getNationalIdentifier());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getSituations());

        $situations = $logicalModel->getPayloadPublication()->getSituations();

        $this->assertCount(36, $situations);
    }

    public function testParseSnapshotLarge()
    {
        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-snapshot-large.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getExchange());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSupplierIdentification());

        $this->assertEquals('nl', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getCountry());
        $this->assertEquals('NLNDW', $logicalModel->getExchange()
            ->getSupplierIdentification()
            ->getNationalIdentifier());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSubscription());
        $this->assertEquals('operatingMode3', $logicalModel->getExchange()
            ->getSubscription()
            ->getOperatingMode());
        $this->assertNotNull($logicalModel->getExchange()
            ->getSubscription()
            ->getSubscriptionStartTime());
        $this->assertEquals(strtotime('2022-03-17T10:46:26.016Z'), $logicalModel->getExchange()
            ->getSubscription()
            ->getSubscriptionStartTime()
            ->getTimestamp());
        $this->assertEquals('active', $logicalModel->getExchange()
            ->getSubscription()
            ->getSubscriptionState());
        $this->assertEquals('snapshot', $logicalModel->getExchange()
            ->getSubscription()
            ->getUpdateMethod());

        $this->assertNotNull($logicalModel->getExchange()
            ->getSubscription()
            ->getTarget());
        $this->assertEmpty($logicalModel->getExchange()
            ->getSubscription()
            ->getTarget()
            ->getAddress());
        $this->assertEquals('HTTP', $logicalModel->getExchange()
            ->getSubscription()
            ->getTarget()
            ->getProtocol());

        $this->assertNotNull($logicalModel->getPayloadPublication());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getPublicationTime());
        $this->assertEquals(strtotime('2022-05-12T00:13:40.957Z'), $logicalModel->getPayloadPublication()
            ->getPublicationTime()
            ->getTimestamp());
        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getPublicationCreator());
        $this->assertEquals('nl', $logicalModel->getPayloadPublication()
            ->getPublicationCreator()
            ->getCountry());
        $this->assertEquals('NLNDW', $logicalModel->getPayloadPublication()
            ->getPublicationCreator()
            ->getNationalIdentifier());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getSituations());

        $situations = $logicalModel->getPayloadPublication()->getSituations();

        $this->assertCount(1618, $situations);
    }

    public function testParsePushMessageWithLifeCycleEnd()
    {
        $this->assertTrue(in_array('compress.zlib', stream_get_wrappers()));

        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-lifecycle-end.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getPayloadPublication());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getSituations());

        $situations = $logicalModel->getPayloadPublication()->getSituations();

        $this->assertCount(1, $situations);

        $situation = $situations[0];

        $this->assertNotNull($situation);

        $situationRecord = $situation->getSituationRecord();

        $this->assertNotNull($situationRecord->getManagement());
        $this->assertNotNull($situationRecord->getManagement()
            ->getLifeCycleManagement());
        $this->assertTrue($situationRecord->getManagement()
            ->getLifeCycleManagement()
            ->getEnd());

        $this->assertEquals('beingTerminated', $situationRecord->getOperatorActionStatus());
        $this->assertEquals('mandatory', $situationRecord->getComplianceOption());
        $this->assertEquals('bridgeSwingInOperation', $situationRecord->getGeneralNetworkManagementType());
    }

    public function testParsePushMessageWithLifeCycleCancel()
    {
        $this->assertTrue(in_array('compress.zlib', stream_get_wrappers()));

        $testFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'testfiles' . DIRECTORY_SEPARATOR . 'brugdata-lifecycle-cancel.xml.gz';

        $parser = new DatexFileParser();

        $logicalModel = $parser->parseFile($testFile);

        $this->assertNotNull($logicalModel);

        $this->assertNotNull($logicalModel->getPayloadPublication());

        $this->assertNotNull($logicalModel->getPayloadPublication()
            ->getSituations());

        $situations = $logicalModel->getPayloadPublication()->getSituations();

        $this->assertCount(1, $situations);

        $situation = $situations[0];

        $this->assertNotNull($situation);

        $situationRecord = $situation->getSituationRecord();

        $this->assertNotNull($situationRecord->getManagement());
        $this->assertNotNull($situationRecord->getManagement()
            ->getLifeCycleManagement());
        $this->assertTrue($situationRecord->getManagement()
            ->getLifeCycleManagement()
            ->getCancel());

        $this->assertEquals('approved', $situationRecord->getOperatorActionStatus());
        $this->assertEquals('mandatory', $situationRecord->getComplianceOption());
        $this->assertEquals('bridgeSwingInOperation', $situationRecord->getGeneralNetworkManagementType());
    }
}
