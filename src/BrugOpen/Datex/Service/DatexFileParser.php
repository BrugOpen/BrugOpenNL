<?php
namespace BrugOpen\Datex\Service;

use BrugOpen\Datex\Model\AlertCDirection;
use BrugOpen\Datex\Model\AlertCLocation;
use BrugOpen\Datex\Model\AlertCMethod2PrimaryPointLocation;
use BrugOpen\Datex\Model\AlertCPoint;
use BrugOpen\Datex\Model\Exchange;
use BrugOpen\Datex\Model\FilterExitManagement;
use BrugOpen\Datex\Model\HeaderInformation;
use BrugOpen\Datex\Model\InternationalIdentifier;
use BrugOpen\Datex\Model\LifeCycleManagement;
use BrugOpen\Datex\Model\LogicalModel;
use BrugOpen\Datex\Model\Management;
use BrugOpen\Datex\Model\MultiLingualString;
use BrugOpen\Datex\Model\OverallPeriod;
use BrugOpen\Datex\Model\PayloadPublication;
use BrugOpen\Datex\Model\Point;
use BrugOpen\Datex\Model\PointByCoordinates;
use BrugOpen\Datex\Model\PointCoordinates;
use BrugOpen\Datex\Model\Situation;
use BrugOpen\Datex\Model\SituationRecord;
use BrugOpen\Datex\Model\Subscription;
use BrugOpen\Datex\Model\Target;
use BrugOpen\Datex\Model\Validity;
use DOMDocument;
use DOMNode;
use XMLReader;

class DatexFileParser
{

    /**
     *
     * @param string $file
     * @return LogicalModel|null
     */
    public function parseFile($file)
    {
        $logicalModel = null;

        $xmlReader = $this->createXmlReader($file);

        if ($xmlReader) {

            $atLogicalModelNode = false;

            while (true) {

                if (($xmlReader->nodeType == 1) && ($xmlReader->name == 'd2LogicalModel')) {

                    $atLogicalModelNode = true;
                    break;
                }

                if (! $xmlReader->read()) {
                    break;
                }
            }

            if ($atLogicalModelNode) {

                $logicalModel = new LogicalModel();

                while (true) {

                    if ($xmlReader->read()) {

                        if (($xmlReader->nodeType == 1) && ($xmlReader->name == 'exchange')) {

                            if ($exchangeNode = $xmlReader->expand()) {

                                if ($exchange = $this->parseExchangeNode($exchangeNode)) {

                                    $logicalModel->setExchange($exchange);
                                }
                            }

                            if (! $xmlReader->next()) {
                                break;
                            }
                        }

                        if (($xmlReader->nodeType == 1) && ($xmlReader->name == 'payloadPublication')) {

                            if ($payloadPublication = $this->parsePayloadPublicationNode($xmlReader)) {

                                $logicalModel->setPayloadPublication($payloadPublication);
                            }

                            if (! $xmlReader->next()) {
                                break;
                            }
                        }

                        if (! $xmlReader->read()) {
                            break;
                        }
                    } else {

                        break;
                    }
                }
            }
        }

        return $logicalModel;
    }

    /**
     *
     * @param string $file
     * @return NULL|\XMLReader
     */
    public function createXmlReader($file)
    {
        $xmlReader = null;

        if (is_file($file)) {

            if (substr($file, - 3) == '.gz') {

                $linkToXmlFile = "compress.zlib://" . $file;
                $xmlReader = new XMLReader();
                $xmlReader->open($linkToXmlFile, null, LIBXML_NOERROR | LIBXML_NOWARNING);
            } else if (substr($file, - 4) == '.xml') {

                $xmlReader = new XMLReader();
                $xmlReader->open($file);
            }
        }

        return $xmlReader;
    }

    public function parseNode()
    {
        $file = array();

        $xml = null;

        if ($xml != null) {

            if ($doc = $this->loadDocument($xml)) {

                if ($this->getSubNode($doc, 'payloadPublication')) {

                    if ($publicationTime = $this->getSubNodeValue($doc, 'publicationTime')) {
                        $file['publicationTime'] = $this->getTimeStamp($publicationTime);
                    }

                    if ($subscriptionNode = $this->getSubNode($doc, 'subscription')) {
                        if ($updateMethod = $this->getSubNodeValue($subscriptionNode, 'updateMethod')) {
                            $file['updateMethod'] = $updateMethod;
                        }
                    }

                    $situations = array();

                    $situationNodes = $doc->getElementsByTagName('situation');

                    foreach ($situationNodes as $situationNode) {

                        if ($situation = $this->loadSituationNode($situationNode)) {

                            $situations[] = $situation;
                        }
                    }

                    $file['situations'] = $situations;
                }
            }
        }

        return $file;
    }

    /**
     *
     * @param DOMNode $node
     * @param string $subnodeName
     * @return string|NULL
     */
    public function getSubNodeValue(DOMNode $node, $subnodeName)
    {
        $value = null;

        if ($node->hasChildNodes()) {

            for ($i = 0; $i < $node->childNodes->length; $i ++) {

                $childNode = $node->childNodes->item($i);

                if ($childNode->nodeType == XML_ELEMENT_NODE) {

                    if ($childNode->tagName == $subnodeName) {
                        $value = $childNode->nodeValue;
                        break;
                    }
                }
            }
        }
        return $value;
    }

    /**
     *
     * @param DOMNode $node
     * @param string $subnodeName
     * @return \DOMNode|NULL
     */
    public function getSubNode(DOMNode $node, $subnodeName)
    {
        $subNode = null;

        if ($node->hasChildNodes()) {

            for ($i = 0; $i < $node->childNodes->length; $i ++) {

                $childNode = $node->childNodes->item($i);

                if ($childNode->nodeType == XML_ELEMENT_NODE) {

                    if ($childNode->tagName == $subnodeName) {
                        $subNode = $childNode;
                        break;
                    }
                }
            }
        }

        return $subNode;
    }

    public function parseDateTimeValue($isoDate)
    {
        $dateTime = null;

        if ($isoDate != '') {
            $timeStamp = strtotime($isoDate);
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($timeStamp);
        }

        return $dateTime;
    }

    /**
     *
     * @param \DOMNode $node
     * @return Exchange
     */
    public function parseExchangeNode($node)
    {
        $exchange = new Exchange();

        $exchange->setDeliveryBreak($this->parseBooleanString($this->getSubNodeValue($node, 'deliveryBreak')));
        $exchange->setKeepAlive($this->parseBooleanString($this->getSubNodeValue($node, 'keepAlive')));
        $exchange->setRequestType($this->getSubNodeValue($node, 'requestType'));

        if ($supplierIdentificationNode = $this->getSubNode($node, 'supplierIdentification')) {

            $supplierIdentification = new InternationalIdentifier();

            $supplierIdentification->setCountry($this->getSubNodeValue($supplierIdentificationNode, 'country'));
            $supplierIdentification->setNationalIdentifier($this->getSubNodeValue($supplierIdentificationNode, 'nationalIdentifier'));

            $exchange->setSupplierIdentification($supplierIdentification);
        }

        if ($subscriptionNode = $this->getSubNode($node, 'subscription')) {

            $subscription = new Subscription();

            $subscription->setOperatingMode($this->getSubNodeValue($subscriptionNode, 'operatingMode'));
            $subscription->setSubscriptionStartTime($this->parseDateTimeValue($this->getSubNodeValue($subscriptionNode, 'subscriptionStartTime')));
            $subscription->setSubscriptionStopTime($this->parseDateTimeValue($this->getSubNodeValue($subscriptionNode, 'subscriptionStopTime')));
            $subscription->setSubscriptionState($this->getSubNodeValue($subscriptionNode, 'subscriptionState'));
            $subscription->setUpdateMethod($this->getSubNodeValue($subscriptionNode, 'updateMethod'));

            if ($targetNode = $this->getSubNode($subscriptionNode, 'target')) {

                $target = new Target();
                $target->setAddress($this->getSubNodeValue($targetNode, 'address'));
                $target->setProtocol($this->getSubNodeValue($targetNode, 'protocol'));

                $subscription->setTarget($target);
            }

            $exchange->setSubscription($subscription);
        }

        return $exchange;
    }

    /**
     *
     * @param \DOMNode $node
     * @return \BrugOpen\Datex\Model\PayloadPublication
     */
    public function parsePayloadPublicationNode($xmlReader)
    {
        $payloadPublication = new PayloadPublication();

        $situations = array();

        if ($xmlReader->hasAttributes) {
            $payloadPublication->setLang($xmlReader->getAttribute('lang'));
        }

        while (true) {

            if ($xmlReader->read()) {

                if ($xmlReader->nodeType == 1) {

                    if ($expandedNode = $xmlReader->expand()) {

                        if ($xmlReader->name == 'publicationTime') {

                            $payloadPublication->setPublicationTime($this->parseDateTimeValue($expandedNode->nodeValue));
                        } else if ($xmlReader->name == 'publicationCreator') {

                            $publicationCreator = new InternationalIdentifier();
                            $publicationCreator->setCountry($this->getSubNodeValue($expandedNode, 'country'));
                            $publicationCreator->setNationalIdentifier($this->getSubNodeValue($expandedNode, 'nationalIdentifier'));

                            $payloadPublication->setPublicationCreator($publicationCreator);
                        } else if ($xmlReader->name == 'situation') {

                            $situation = $this->parseSituationNode($expandedNode);
                            $situations[] = $situation;
                        }
                    } else {

                        break;
                    }
                }
            } else {

                break;
            }
        }

        if ($situations) {
            $payloadPublication->setSituations($situations);
        }

        return $payloadPublication;
    }

    public function parseSituationNode($node)
    {
        $situation = new Situation();

        $situation->setId($node->getAttribute('id'));
        $situation->setVersion($node->getAttribute('version'));
        $situation->setOverallSeverity($this->getSubNodeValue($node, 'overallSeverity'));
        $situation->setSituationVersionTime($this->parseDateTimeValue($this->getSubNodeValue($node, 'situationVersionTime')));

        if ($headerInformationNode = $this->getSubNode($node, 'headerInformation')) {

            $headerInformation = new HeaderInformation();
            $headerInformation->setConfidentiality($this->getSubNodeValue($headerInformationNode, 'confidentiality'));
            $headerInformation->setInformationStatus($this->getSubNodeValue($headerInformationNode, 'informationStatus'));

            $situation->setHeaderInformation($headerInformation);
        }

        if ($situationRecordNode = $this->getSubNode($node, 'situationRecord')) {

            $situationRecord = new SituationRecord();

            $situationRecord->setId($situationRecordNode->getAttribute('id'));
            $situationRecord->setVersion($situationRecordNode->getAttribute('version'));
            $situationRecord->setSituationRecordCreationTime($this->parseDateTimeValue($this->getSubNodeValue($situationRecordNode, 'situationRecordCreationTime')));
            $situationRecord->setSituationRecordVersionTime($this->parseDateTimeValue($this->getSubNodeValue($situationRecordNode, 'situationRecordVersionTime')));
            $situationRecord->setProbabilityOfOccurrence($this->getSubNodeValue($situationRecordNode, 'probabilityOfOccurrence'));

            if ($sourceNode = $this->getSubNode($situationRecordNode, 'source')) {

                if ($sourceNameNode = $this->getSubNode($sourceNode, 'sourceName')) {

                    $source = $this->parseMultiLingualValueString($sourceNameNode);

                    $situationRecord->setSource($source);
                }
            }

            if ($validityNode = $this->getSubNode($situationRecordNode, 'validity')) {

                $validity = new Validity();

                $validity->setValidityStatus($this->getSubNodeValue($validityNode, 'validityStatus'));

                if ($validityTimeSpecificationNode = $this->getSubNode($validityNode, 'validityTimeSpecification')) {

                    $validityTimeSpecification = new OverallPeriod();
                    $validityTimeSpecification->setOverallStartTime($this->parseDateTimeValue($this->getSubNodeValue($validityTimeSpecificationNode, 'overallStartTime')));
                    $validityTimeSpecification->setOverallEndTime($this->parseDateTimeValue($this->getSubNodeValue($validityTimeSpecificationNode, 'overallEndTime')));

                    $validity->setValidityTimeSpecification($validityTimeSpecification);
                }

                $situationRecord->setValidity($validity);
            }

            if ($groupOfLocationsNode = $this->getSubNode($situationRecordNode, 'groupOfLocations')) {

                $groupOfLocations = new Point();

                if ($locationForDisplayNode = $this->getSubNode($groupOfLocationsNode, 'locationForDisplay')) {

                    $locationForDisplay = new PointCoordinates();
                    $locationForDisplay->setLatitude($this->getSubNodeValue($locationForDisplayNode, 'latitude'));
                    $locationForDisplay->setLongitude($this->getSubNodeValue($locationForDisplayNode, 'longitude'));

                    $groupOfLocations->setLocationForDisplay($locationForDisplay);
                }

                if ($alertCPointNode = $this->getSubNode($groupOfLocationsNode, 'alertCPoint')) {

                    $alertCPoint = new AlertCPoint();

                    $alertCPoint->setAlertCLocationCountryCode($this->getSubNodeValue($alertCPointNode, 'alertCLocationCountryCode'));
                    $alertCPoint->setAlertCLocationTableNumber($this->getSubNodeValue($alertCPointNode, 'alertCLocationTableNumber'));
                    $alertCPoint->setAlertCLocationTableVersion($this->getSubNodeValue($alertCPointNode, 'alertCLocationTableVersion'));

                    if ($alertCDirectionNode = $this->getSubNode($alertCPointNode, 'alertCDirection')) {

                        $alertCDirection = new AlertCDirection();

                        $alertCDirection->setAlertCDirectionCoded($this->getSubNodeValue($alertCDirectionNode, 'alertCDirectionCoded'));

                        if ($alertCDirectionNamedNode = $this->getSubNode($alertCDirectionNode, 'alertCDirectionNamed')) {

                            $alertCDirection->setAlertCDirectionNamed($this->parseMultiLingualValueString($alertCDirectionNamedNode));
                        }

                        $alertCDirection->setAlertCDirectionSense($this->parseBooleanString($this->getSubNodeValue($alertCDirectionNode, 'alertCDirectionSense')));

                        $alertCPoint->setAlertCDirection($alertCDirection);
                    }

                    if ($alertCMethod2PrimaryPointLocationNode = $this->getSubNode($alertCPointNode, 'alertCMethod2PrimaryPointLocation')) {

                        $alertCMethod2PrimaryPointLocation = new AlertCMethod2PrimaryPointLocation();

                        if ($alertCLocationNode = $this->getSubNode($alertCMethod2PrimaryPointLocationNode, 'alertCLocation')) {

                            $alertCLocation = new AlertCLocation();

                            if ($alertCLocationNameNode = $this->getSubNode($alertCLocationNode, 'alertCLocationName')) {

                                $alertCLocation->setAlertCLocationName($this->parseMultiLingualValueString($alertCLocationNameNode));
                            }

                            $alertCLocation->setSpecificLocation($this->getSubNodeValue($alertCLocationNode, 'specificLocation'));

                            $alertCMethod2PrimaryPointLocation->setAlertCLocation($alertCLocation);
                        }

                        $alertCPoint->setAlertCMethod2PrimaryPointLocation($alertCMethod2PrimaryPointLocation);
                    }

                    $groupOfLocations->setAlertCPoint($alertCPoint);
                }

                if ($pointByCoordinatesNode = $this->getSubNode($groupOfLocationsNode, 'pointByCoordinates')) {

                    $pointByCoordinates = new PointByCoordinates();

                    $pointByCoordinates->setBearing($this->getSubNodeValue($pointByCoordinatesNode, 'bearing'));

                    if ($pointCoordinatesNode = $this->getSubNode($pointByCoordinatesNode, 'pointCoordinates')) {

                        $pointCoordinates = new PointCoordinates();
                        $pointCoordinates->setLatitude($this->getSubNodeValue($pointCoordinatesNode, 'latitude'));
                        $pointCoordinates->setLongitude($this->getSubNodeValue($pointCoordinatesNode, 'longitude'));

                        $pointByCoordinates->setPointCoordinates($pointCoordinates);
                    }

                    $groupOfLocations->setPointByCoordinates($pointByCoordinates);
                }
                $situationRecord->setGroupOfLocations($groupOfLocations);
            }

            if ($managementNode = $this->getSubNode($situationRecordNode, 'management')) {

                $management = new Management();

                if ($lifeCycleManagementNode = $this->getSubNode($managementNode, 'lifeCycleManagement')) {

                    $lifeCycleManagement = new LifeCycleManagement();
                    $lifeCycleManagement->setCancel($this->parseBooleanString($this->getSubNodeValue($lifeCycleManagementNode, 'cancel')));
                    $lifeCycleManagement->setEnd($this->parseBooleanString($this->getSubNodeValue($lifeCycleManagementNode, 'end')));

                    $management->setLifeCycleManagement($lifeCycleManagement);
                }

                if ($filterExitManagementNode = $this->getSubNode($managementNode, 'filterExitManagement')) {

                    $filterExitManagement = new FilterExitManagement();
                    $filterExitManagement->setCancel($this->parseBooleanString($this->getSubNodeValue($filterExitManagementNode, 'filterEnd')));
                    $filterExitManagement->setEnd($this->parseBooleanString($this->getSubNodeValue($filterExitManagementNode, 'filterOutOfRange')));

                    $management->setFilterExitManagement($filterExitManagement);
                }

                $situationRecord->setManagement($management);
            }

            $situationRecord->setOperatorActionStatus($this->getSubNodeValue($situationRecordNode, 'operatorActionStatus'));
            $situationRecord->setComplianceOption($this->getSubNodeValue($situationRecordNode, 'complianceOption'));
            $situationRecord->setGeneralNetworkManagementType($this->getSubNodeValue($situationRecordNode, 'generalNetworkManagementType'));

            $situation->setSituationRecord($situationRecord);
        }

        return $situation;
    }

    public function loadSituationNode($node)
    {
        $situation = array();

        $situation['id'] = $node->getAttribute('id');
        $situation['version'] = $node->getAttribute('version');

        $versionTime = $this->getSubNodeValue($node, 'situationRecordVersionTime');

        if (! $versionTime) {
            $versionTime = $this->getSubNodeValue($node, 'situationVersionTime');
        }

        if ($versionTime) {
            $situation['versionTime'] = $this->getTimeStamp($versionTime);
        }

        if ($probability = $this->getSubNodeValue($node, 'probabilityOfOccurrence')) {
            $situation['probability'] = $probability;
        }

        if ($location = $this->getSubNodeValue($node, 'specificLocation')) {
            $situation['specificLocation'] = $location;
        }

        if ($locationForDisplayNode = $this->getSubNode($node, 'locationForDisplay')) {

            if ($lat = $this->getSubNodeValue($locationForDisplayNode, 'latitude')) {

                if ($lng = $this->getSubNodeValue($locationForDisplayNode, 'longitude')) {

                    $situation['location'] = array();
                    $situation['location']['lat'] = $lat;
                    $situation['location']['lng'] = $lng;
                }
            }
        }

        if ($overallStartTime = $this->getSubNodeValue($node, 'overallStartTime')) {
            $situation['overallStartTime'] = $this->getTimeStamp($overallStartTime);
        }
        if ($overallEndTime = $this->getSubNodeValue($node, 'overallEndTime')) {
            $situation['overallEndTime'] = $this->getTimeStamp($overallEndTime);
        } else {
            $situation['overallEndTime'] = null;
        }

        if ($operatorActionStatus = $this->getSubNodeValue($node, 'operatorActionStatus')) {
            $situation['operatorActionStatus'] = $operatorActionStatus;
        }

        if ($lifeCycleManagementNode = $this->getSubNode($node, 'lifeCycleManagement')) {
            if ($this->getSubNodeValue($lifeCycleManagementNode, 'end') == 'true') {
                $situation['lifeCycleEnd'] = 'true';
            }
            if ($this->getSubNodeValue($lifeCycleManagementNode, 'cancel') == 'true') {
                $situation['lifeCycleCancel'] = 'true';
            }
        }

        return $situation;
    }

    public function parseMultiLingualValueString($node)
    {
        $multiLingualString = new MultiLingualString();

        $valuesByLang = array();

        if ($valuesNode = $this->getSubNode($node, 'values')) {

            if ($valuesNode->hasChildNodes()) {

                foreach ($valuesNode->childNodes as $valueNode) {

                    $lang = $valueNode->getAttribute('lang');
                    $value = $valueNode->nodeValue;

                    $valuesByLang[$lang] = $value;
                }
            }
        }

        $multiLingualString->setValuesByLang($valuesByLang);

        return $multiLingualString;
    }

    /**
     *
     * @param string $str
     * @return boolean|NULL
     */
    public function parseBooleanString($str)
    {
        $bool = null;

        if ($str == 'true') {
            $bool = true;
        } else if ($str == 'false') {
            $bool = false;
        }

        return $bool;
    }

    private function loadDocument($xml)
    {
        $doc = new DOMDocument();
        @$doc->loadXML($xml);
        return $doc;
    }
}
