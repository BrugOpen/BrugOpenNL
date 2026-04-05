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
use BrugOpen\Datex\Model\MessageContainer;
use BrugOpen\Datex\Model\MultiLingualString;
use BrugOpen\Datex\Model\OverallPeriod;
use BrugOpen\Datex\Model\Payload;
use BrugOpen\Datex\Model\PayloadPublication;
use BrugOpen\Datex\Model\Point;
use BrugOpen\Datex\Model\PointByCoordinates;
use BrugOpen\Datex\Model\PointCoordinates;
use BrugOpen\Datex\Model\PointLocation;
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

            $xmlReader->close();
        }

        return $logicalModel;
    }

    /**
     *
     * @param string $file
     * @return MessageContainer|null
     */
    public function parseV3File($file)
    {
        $messageContainer = null;

        $xmlReader = $this->createXmlReader($file);

        if ($xmlReader) {

            $atMessageContainerNode = false;

            while (true) {

                if ($xmlReader->nodeType == 1) {

                    if (($xmlReader->localName == 'messageContainer') || ($xmlReader->localName == 'putDataInput') || ($xmlReader->localName == 'putSnapshotDataInput')) {

                        $atMessageContainerNode = true;
                        break;
                    }
                }

                if (! $xmlReader->read()) {
                    break;
                }
            }

            if ($atMessageContainerNode) {

                $messageContainer = new MessageContainer();

                while (true) {

                    if ($xmlReader->read()) {

                        if (($xmlReader->nodeType == 1) && ($xmlReader->localName == 'payload')) {

                            $payload = $this->parsePayloadNode($xmlReader);
                            $messageContainer->setPayload($payload);

                            continue;
                        }
                        if (($xmlReader->nodeType == 1) && ($xmlReader->localName == 'exchangeInformation')) {

                            if ($exchangeNode = $xmlReader->expand()) {

                                $exchange = $this->parseExchangeInformationNode($exchangeNode);
                                $messageContainer->setExchangeInformation($exchange);
                            }
                        }
                    } else {

                        break;
                    }
                }
            }

            $xmlReader->close();
        }

        return $messageContainer;
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

            if (substr($file, -3) == '.gz') {

                $linkToXmlFile = "compress.zlib://" . $file;
                $xmlReader = new XMLReader();
                $xmlReader->open($linkToXmlFile, null, LIBXML_NOERROR | LIBXML_NOWARNING);
            } else if (substr($file, -4) == '.xml') {

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
                        $file['publicationTime'] = $this->parseDateTimeValue($publicationTime);
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
    public function getSubNodeValue(DOMNode $node, $subnodeName, $useLocalName = false)
    {
        $value = null;

        $subNode = $this->getSubNode($node, $subnodeName, $useLocalName);

        if ($subNode) {
            $value = $subNode->nodeValue;
        }

        return $value;
    }

    /**
     *
     * @param \DOMNode $node
     * @param string $subnodeName
     * @return \DOMElement|NULL
     */
    public function getSubNode(\DOMNode $node, $subnodeName, $useLocalName = false)
    {
        $subNode = null;

        if ($node->hasChildNodes()) {

            for ($i = 0; $i < $node->childNodes->length; $i++) {

                $childNode = $node->childNodes->item($i);

                if ($childNode->nodeType == XML_ELEMENT_NODE) {

                    /**
                     * @var \DOMElement $childElement
                     */
                    $childElement = $childNode;

                    if ($useLocalName) {
                        if ($childElement->localName == $subnodeName) {
                            $subNode = $childElement;
                            break;
                        }
                    } else {
                        if ($childElement->tagName == $subnodeName) {
                            $subNode = $childElement;
                            break;
                        }
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

            // The strtotime function does not support the nanosecond part of the date, so we need to remove it before parsing the date.
            if (strpos($isoDate, '.') !== false) {
                $isoDate = substr($isoDate, 0, strpos($isoDate, '.')) . 'Z';
            }

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

    /**
     * @param \DOMNode $node
     * @return \BrugOpen\Datex\Model\Payload
     */
    public function parsePayloadNode($xmlReader)
    {
        $payload = new Payload();

        $situations = array();

        // if ($xmlReader->hasAttributes) {
        //     $payload->setLang($xmlReader->getAttribute('lang'));
        // }

        while (true) {

            if ($xmlReader->read()) {

                if ($xmlReader->nodeType == 1) {

                    if ($expandedNode = $xmlReader->expand()) {

                        if ($xmlReader->localName == 'publicationTime') {

                            $payload->setPublicationTime($this->parseDateTimeValue($expandedNode->nodeValue));
                        } else if ($xmlReader->localName == 'publicationCreator') {

                            $publicationCreator = new InternationalIdentifier();
                            $publicationCreator->setCountry($this->getSubNodeValue($expandedNode, 'country'));
                            $publicationCreator->setNationalIdentifier($this->getSubNodeValue($expandedNode, 'nationalIdentifier'));

                            $payload->setPublicationCreator($publicationCreator);
                        } else if ($xmlReader->localName == 'situation') {

                            $situation = $this->parseSituationNode($expandedNode);
                            $situations[] = $situation;
                        }
                    } else {

                        break;
                    }
                } else if ($xmlReader->nodeType == \XMLReader::END_ELEMENT) {

                    if ($xmlReader->localName == 'payload') {
                        break;
                    }
                }
            } else {

                break;
            }
        }

        if ($situations) {
            $payload->setSituations($situations);
        }

        return $payload;
    }

    public function parseSituationNode($node)
    {
        $situation = new Situation();

        $situation->setId($node->getAttribute('id'));
        $situation->setVersion($node->getAttribute('version'));
        $situation->setOverallSeverity($this->getSubNodeValue($node, 'overallSeverity', true));
        $situation->setSituationVersionTime($this->parseDateTimeValue($this->getSubNodeValue($node, 'situationVersionTime', true)));

        if ($headerInformationNode = $this->getSubNode($node, 'headerInformation', true)) {

            $headerInformation = new HeaderInformation();
            $headerInformation->setConfidentiality($this->getSubNodeValue($headerInformationNode, 'confidentiality', true));
            $headerInformation->setInformationStatus($this->getSubNodeValue($headerInformationNode, 'informationStatus', true));

            $situation->setHeaderInformation($headerInformation);
        }

        if ($situationRecordNode = $this->getSubNode($node, 'situationRecord', true)) {

            $situationRecord = new SituationRecord();

            $situationRecord->setId($situationRecordNode->getAttribute('id'));
            $situationRecord->setVersion($situationRecordNode->getAttribute('version'));
            $situationRecord->setSituationRecordCreationTime($this->parseDateTimeValue($this->getSubNodeValue($situationRecordNode, 'situationRecordCreationTime', true)));
            $situationRecord->setSituationRecordVersionTime($this->parseDateTimeValue($this->getSubNodeValue($situationRecordNode, 'situationRecordVersionTime', true)));
            $situationRecord->setProbabilityOfOccurrence($this->getSubNodeValue($situationRecordNode, 'probabilityOfOccurrence', true));

            if ($sourceNode = $this->getSubNode($situationRecordNode, 'source', true)) {

                if ($sourceNameNode = $this->getSubNode($sourceNode, 'sourceName', true)) {

                    $source = $this->parseMultiLingualValueString($sourceNameNode);

                    $situationRecord->setSource($source);
                }
            }

            if ($validityNode = $this->getSubNode($situationRecordNode, 'validity', true)) {

                $validity = new Validity();

                $validity->setValidityStatus($this->getSubNodeValue($validityNode, 'validityStatus', true));

                if ($validityTimeSpecificationNode = $this->getSubNode($validityNode, 'validityTimeSpecification', true)) {

                    $validityTimeSpecification = new OverallPeriod();
                    $validityTimeSpecification->setOverallStartTime($this->parseDateTimeValue($this->getSubNodeValue($validityTimeSpecificationNode, 'overallStartTime', true)));
                    $validityTimeSpecification->setOverallEndTime($this->parseDateTimeValue($this->getSubNodeValue($validityTimeSpecificationNode, 'overallEndTime', true)));

                    $validity->setValidityTimeSpecification($validityTimeSpecification);
                }

                $situationRecord->setValidity($validity);
            }

            if ($groupOfLocationsNode = $this->getSubNode($situationRecordNode, 'groupOfLocations', true)) {

                $groupOfLocations = new Point();

                if ($locationForDisplayNode = $this->getSubNode($groupOfLocationsNode, 'locationForDisplay', true)) {

                    $locationForDisplay = new PointCoordinates();
                    $locationForDisplay->setLatitude($this->getSubNodeValue($locationForDisplayNode, 'latitude', true));
                    $locationForDisplay->setLongitude($this->getSubNodeValue($locationForDisplayNode, 'longitude', true));

                    $groupOfLocations->setLocationForDisplay($locationForDisplay);
                }

                if ($alertCPointNode = $this->getSubNode($groupOfLocationsNode, 'alertCPoint', true)) {

                    $alertCPoint = new AlertCPoint();

                    $alertCPoint->setAlertCLocationCountryCode($this->getSubNodeValue($alertCPointNode, 'alertCLocationCountryCode', true));
                    $alertCPoint->setAlertCLocationTableNumber($this->getSubNodeValue($alertCPointNode, 'alertCLocationTableNumber', true));
                    $alertCPoint->setAlertCLocationTableVersion($this->getSubNodeValue($alertCPointNode, 'alertCLocationTableVersion', true));

                    if ($alertCDirectionNode = $this->getSubNode($alertCPointNode, 'alertCDirection', true)) {

                        $alertCDirection = new AlertCDirection();

                        $alertCDirection->setAlertCDirectionCoded($this->getSubNodeValue($alertCDirectionNode, 'alertCDirectionCoded', true));

                        if ($alertCDirectionNamedNode = $this->getSubNode($alertCDirectionNode, 'alertCDirectionNamed', true)) {

                            $alertCDirection->setAlertCDirectionNamed($this->parseMultiLingualValueString($alertCDirectionNamedNode));
                        }

                        $alertCDirection->setAlertCDirectionSense($this->parseBooleanString($this->getSubNodeValue($alertCDirectionNode, 'alertCDirectionSense', true)));

                        $alertCPoint->setAlertCDirection($alertCDirection);
                    }

                    if ($alertCMethod2PrimaryPointLocationNode = $this->getSubNode($alertCPointNode, 'alertCMethod2PrimaryPointLocation', true)) {

                        $alertCMethod2PrimaryPointLocation = new AlertCMethod2PrimaryPointLocation();

                        if ($alertCLocationNode = $this->getSubNode($alertCMethod2PrimaryPointLocationNode, 'alertCLocation', true)) {

                            $alertCLocation = new AlertCLocation();

                            if ($alertCLocationNameNode = $this->getSubNode($alertCLocationNode, 'alertCLocationName', true)) {

                                $alertCLocation->setAlertCLocationName($this->parseMultiLingualValueString($alertCLocationNameNode));
                            }

                            $alertCLocation->setSpecificLocation($this->getSubNodeValue($alertCLocationNode, 'specificLocation', true));

                            $alertCMethod2PrimaryPointLocation->setAlertCLocation($alertCLocation);
                        }

                        $alertCPoint->setAlertCMethod2PrimaryPointLocation($alertCMethod2PrimaryPointLocation);
                    }

                    $groupOfLocations->setAlertCPoint($alertCPoint);
                }

                if ($pointByCoordinatesNode = $this->getSubNode($groupOfLocationsNode, 'pointByCoordinates', true)) {

                    $pointByCoordinates = new PointByCoordinates();

                    $pointByCoordinates->setBearing($this->getSubNodeValue($pointByCoordinatesNode, 'bearing', true));

                    if ($pointCoordinatesNode = $this->getSubNode($pointByCoordinatesNode, 'pointCoordinates', true)) {

                        $pointCoordinates = new PointCoordinates();
                        $pointCoordinates->setLatitude($this->getSubNodeValue($pointCoordinatesNode, 'latitude', true));
                        $pointCoordinates->setLongitude($this->getSubNodeValue($pointCoordinatesNode, 'longitude', true));

                        $pointByCoordinates->setPointCoordinates($pointCoordinates);
                    }

                    $groupOfLocations->setPointByCoordinates($pointByCoordinates);
                }
                $situationRecord->setGroupOfLocations($groupOfLocations);
            }

            $locationReferenceNode = $this->getSubNode($situationRecordNode, 'locationReference', true);

            if ($locationReferenceNode) {

                $locationReference = $this->parseLocationReferenceNode($locationReferenceNode);

                $situationRecord->setLocationReference($locationReference);
            }

            if ($managementNode = $this->getSubNode($situationRecordNode, 'management', true)) {

                $management = new Management();

                if ($lifeCycleManagementNode = $this->getSubNode($managementNode, 'lifeCycleManagement', true)) {

                    $lifeCycleManagement = new LifeCycleManagement();
                    $lifeCycleManagement->setCancel($this->parseBooleanString($this->getSubNodeValue($lifeCycleManagementNode, 'cancel', true)));
                    $lifeCycleManagement->setEnd($this->parseBooleanString($this->getSubNodeValue($lifeCycleManagementNode, 'end', true)));

                    $management->setLifeCycleManagement($lifeCycleManagement);
                }

                if ($filterExitManagementNode = $this->getSubNode($managementNode, 'filterExitManagement', true)) {

                    // $filterExitManagement = new FilterExitManagement();
                    // $filterExitManagement->setCancel($this->parseBooleanString($this->getSubNodeValue($filterExitManagementNode, 'filterEnd', true)));
                    // $filterExitManagement->setEnd($this->parseBooleanString($this->getSubNodeValue($filterExitManagementNode, 'filterOutOfRange', true)));

                    // $management->setFilterExitManagement($filterExitManagement);
                }

                $situationRecord->setManagement($management);
            }

            $situationRecord->setOperatorActionStatus($this->getSubNodeValue($situationRecordNode, 'operatorActionStatus', true));
            $situationRecord->setComplianceOption($this->getSubNodeValue($situationRecordNode, 'complianceOption', true));
            $situationRecord->setGeneralNetworkManagementType($this->getSubNodeValue($situationRecordNode, 'generalNetworkManagementType', true));

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
            $situation['versionTime'] = $this->parseDateTimeValue($versionTime);
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
            $situation['overallStartTime'] = $this->parseDateTimeValue($overallStartTime);
        }
        if ($overallEndTime = $this->getSubNodeValue($node, 'overallEndTime')) {
            $situation['overallEndTime'] = $this->parseDateTimeValue($overallEndTime);
        } else {
            $situation['overallEndTime'] = null;
        }

        if ($operatorActionStatus = $this->getSubNodeValue($node, 'operatorActionStatus', true)) {
            $situation['operatorActionStatus'] = $operatorActionStatus;
        }

        if ($lifeCycleManagementNode = $this->getSubNode($node, 'lifeCycleManagement', true)) {
            if ($this->getSubNodeValue($lifeCycleManagementNode, 'end', true) == 'true') {
                $situation['lifeCycleEnd'] = 'true';
            }
            if ($this->getSubNodeValue($lifeCycleManagementNode, 'cancel', true) == 'true') {
                $situation['lifeCycleCancel'] = 'true';
            }
        }

        return $situation;
    }

    public function parseLocationReferenceNode($locationReferenceNode)
    {
        $locationReference = new PointLocation();

        // <sit:locationReference xsi:type="loc:PointLocation">
        //   <loc:externalReferencing>
        //     <loc:externalLocationCode>NLZAA002360561800012</loc:externalLocationCode>
        //     <loc:externalReferencingSystem>RIS-index</loc:externalReferencingSystem>
        //   </loc:externalReferencing>
        //   <loc:supplementaryPositionalDescription>
        //     <loc:carriageway>
        //       <loc:carriageway>mainCarriageway</loc:carriageway>
        //     </loc:carriageway>
        //   </loc:supplementaryPositionalDescription>
        //   <loc:pointByCoordinates>
        //     <loc:pointCoordinates>
        //       <loc:latitude>52.42779</loc:latitude>
        //       <loc:longitude>4.834402</loc:longitude>
        //     </loc:pointCoordinates>
        //   </loc:pointByCoordinates>
        //   <loc:alertCPoint xsi:type="loc:AlertCMethod2Point">
        //     <loc:alertCLocationCountryCode>8</loc:alertCLocationCountryCode>
        //     <loc:alertCLocationTableNumber>6.13</loc:alertCLocationTableNumber>
        //     <loc:alertCLocationTableVersion>A</loc:alertCLocationTableVersion>
        //     <loc:alertCDirection>
        //       <loc:alertCDirectionCoded>positive</loc:alertCDirectionCoded>
        //       <loc:alertCAffectedDirection>both</loc:alertCAffectedDirection>
        //     </loc:alertCDirection>
        //     <loc:alertCMethod2PrimaryPointLocation>
        //       <loc:alertCLocation>
        //         <loc:specificLocation>22220</loc:specificLocation>
        //       </loc:alertCLocation>
        //     </loc:alertCMethod2PrimaryPointLocation>
        //   </loc:alertCPoint>
        // </sit:locationReference>

        $externalReferencingNode = $this->getSubNode($locationReferenceNode, 'externalReferencing', true);
        if ($externalReferencingNode) {

            $externalReferencing = new \BrugOpen\Datex\Model\ExternalReferencing();

            $externalReferencing->setExternalLocationCode($this->getSubNodeValue($externalReferencingNode, 'externalLocationCode', true));
            $externalReferencing->setExternalReferencingSystem($this->getSubNodeValue($externalReferencingNode, 'externalReferencingSystem', true));

            $locationReference->setExternalReferencing($externalReferencing);
        }

        $supplementaryPositionalDescriptionNode = $this->getSubNode($locationReferenceNode, 'supplementaryPositionalDescription', true);
        if ($supplementaryPositionalDescriptionNode) {

            $supplementaryPositionalDescription = trim($supplementaryPositionalDescriptionNode->nodeValue);

            if ($supplementaryPositionalDescription != '') {
                $locationReference->setSupplementaryPositionalDescription($supplementaryPositionalDescription);
            }
        }

        if ($pointByCoordinatesNode = $this->getSubNode($locationReferenceNode, 'pointByCoordinates', true)) {

            $pointByCoordinates = new PointByCoordinates();

            $pointByCoordinates->setBearing($this->getSubNodeValue($pointByCoordinatesNode, 'bearing', true));

            if ($pointCoordinatesNode = $this->getSubNode($pointByCoordinatesNode, 'pointCoordinates', true)) {

                $pointCoordinates = new PointCoordinates();
                $pointCoordinates->setLatitude($this->getSubNodeValue($pointCoordinatesNode, 'latitude', true));
                $pointCoordinates->setLongitude($this->getSubNodeValue($pointCoordinatesNode, 'longitude', true));

                $pointByCoordinates->setPointCoordinates($pointCoordinates);
            }

            $locationReference->setPointByCoordinates($pointByCoordinates);
        }

        if ($alertCPointNode = $this->getSubNode($locationReferenceNode, 'alertCPoint', true)) {

            $alertCPoint = new AlertCPoint();

            $alertCPoint->setAlertCLocationCountryCode($this->getSubNodeValue($alertCPointNode, 'alertCLocationCountryCode', true));
            $alertCPoint->setAlertCLocationTableNumber($this->getSubNodeValue($alertCPointNode, 'alertCLocationTableNumber', true));
            $alertCPoint->setAlertCLocationTableVersion($this->getSubNodeValue($alertCPointNode, 'alertCLocationTableVersion', true));

            if ($alertCDirectionNode = $this->getSubNode($alertCPointNode, 'alertCDirection', true)) {

                $alertCDirection = new AlertCDirection();

                $alertCDirection->setAlertCDirectionCoded($this->getSubNodeValue($alertCDirectionNode, 'alertCDirectionCoded', true));

                if ($alertCDirectionNamedNode = $this->getSubNode($alertCDirectionNode, 'alertCDirectionNamed', true)) {

                    $alertCDirection->setAlertCDirectionNamed($this->parseMultiLingualValueString($alertCDirectionNamedNode));
                }

                $alertCDirection->setAlertCDirectionSense($this->parseBooleanString($this->getSubNodeValue($alertCDirectionNode, 'alertCDirectionSense', true)));

                $alertCPoint->setAlertCDirection($alertCDirection);
            }

            if ($alertCMethod2PrimaryPointLocationNode = $this->getSubNode($alertCPointNode, 'alertCMethod2PrimaryPointLocation', true)) {

                $alertCMethod2PrimaryPointLocation = new AlertCMethod2PrimaryPointLocation();

                if ($alertCLocationNode = $this->getSubNode($alertCMethod2PrimaryPointLocationNode, 'alertCLocation', true)) {

                    $alertCLocation = new AlertCLocation();

                    if ($alertCLocationNameNode = $this->getSubNode($alertCLocationNode, 'alertCLocationName', true)) {

                        $alertCLocation->setAlertCLocationName($this->parseMultiLingualValueString($alertCLocationNameNode));
                    }

                    $alertCLocation->setSpecificLocation($this->getSubNodeValue($alertCLocationNode, 'specificLocation', true));
                }

                $alertCMethod2PrimaryPointLocation->setAlertCLocation($alertCLocation);
            }

            $locationReference->setAlertCPoint($alertCPoint);
        }

        return $locationReference;
    }

    public function parseMultiLingualValueString($node)
    {
        $multiLingualString = new MultiLingualString();

        $valuesByLang = array();

        if ($valuesNode = $this->getSubNode($node, 'values', true)) {

            if ($valuesNode->hasChildNodes()) {

                foreach ($valuesNode->childNodes as $valueNode) {

                    if ($valueNode->nodeType == XML_ELEMENT_NODE) {

                        /**
                         * @var \DOMElement $valueElement
                         */
                        $valueElement = $valueNode;

                        $lang = $valueElement->getAttribute('lang');
                        $value = $valueElement->nodeValue;

                        $valuesByLang[$lang] = $value;
                    }
                }
            }
        }

        $multiLingualString->setValuesByLang($valuesByLang);

        return $multiLingualString;
    }

    /**
     * @param \DOMNode $node
     * @return \BrugOpen\Datex\Model\ExchangeInformation
     */
    public function parseExchangeInformationNode($node)
    {
        $exchangeInformation = new \BrugOpen\Datex\Model\ExchangeInformation();

        // Parse <exchangeContext>
        $exchangeContextNode = $this->getSubNode($node, 'exchangeContext', true);
        if ($exchangeContextNode) {
            $exchangeContext = $this->parseExchangeContextNode($exchangeContextNode);
            $exchangeInformation->setExchangeContext($exchangeContext);
        }

        // Parse <dynamicInformation>
        $dynamicInformationNode = $this->getSubNode($node, 'dynamicInformation', true);
        if ($dynamicInformationNode) {
            $dynamicInformation = $this->parseDynamicInformationNode($dynamicInformationNode);
            $exchangeInformation->setDynamicInformation($dynamicInformation);
        }

        return $exchangeInformation;
    }

    /**
     *
     * @param \DOMNode $node
     * @return \BrugOpen\Datex\Model\ExchangeContext
     */
    public function parseExchangeContextNode($node)
    {
        $exchangeContext = new \BrugOpen\Datex\Model\ExchangeContext();
        $exchangeContext->setCodedExchangeProtocol($this->getSubNodeValue($node, 'codedExchangeProtocol', true));
        $exchangeContext->setExchangeSpecificationVersion($this->getSubNodeValue($node, 'exchangeSpecificationVersion', true));

        // Parse <supplierOrCisRequester>/<internationalIdentifier>
        if ($supplierNode = $this->getSubNode($node, 'supplierOrCisRequester', true)) {
            if ($identifierNode = $this->getSubNode($supplierNode, 'internationalIdentifier', true)) {
                $internationalIdentifier = new \BrugOpen\Datex\Model\InternationalIdentifier();
                $internationalIdentifier->setCountry($this->getSubNodeValue($identifierNode, 'country', true));
                $internationalIdentifier->setNationalIdentifier($this->getSubNodeValue($identifierNode, 'nationalIdentifier', true));

                $agent = new \BrugOpen\Datex\Model\Agent();
                $agent->setInternationalIdentifier($internationalIdentifier);
                $exchangeContext->setSupplierOrCisRequester($agent);
            }
        }

        return $exchangeContext;
    }

    /**
     *
     * @param \DOMNode $node
     * @return \BrugOpen\Datex\Model\DynamicInformation
     */
    public function parseDynamicInformationNode($node)
    {
        $dynamicInformation = new \BrugOpen\Datex\Model\DynamicInformation();
        $dynamicInformation->setExchangeStatus($this->getSubNodeValue($node, 'exchangeStatus', true));
        $timestamp = $this->getSubNodeValue($node, 'messageGenerationTimestamp', true);
        $dynamicInformation->setMessageGenerationTimestamp($this->parseDateTimeValue($timestamp));
        return $dynamicInformation;
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
