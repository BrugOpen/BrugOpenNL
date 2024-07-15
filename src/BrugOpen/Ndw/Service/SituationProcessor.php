<?php
namespace BrugOpen\Ndw\Service;

use BrugOpen\Core\EventDispatcher;
use BrugOpen\Db\Service\DatabaseTableManager;
use BrugOpen\Db\Service\TableManager;

class SituationProcessor
{

    /**
     *
     * @var \BrugOpen\Core\Context
     */
    private $context;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     *
     * @var TableManager
     */
    private $tableManager;

    /**
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     *
     * @param \BrugOpen\Core\Context $context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }

    /**
     *
     * @return \BrugOpen\Db\Service\TableManager
     */
    public function getTableManager()
    {
        if ($this->tableManager == null) {

            $this->tableManager = $this->context->getService('BrugOpen.TableManager');

        }

        return $this->tableManager;
    }

    /**
     *
     * @param TableManager $tableManager
     */
    public function setTableManager($tableManager)
    {
        $this->tableManager = $tableManager;
    }

    /**
     *
     * @return \BrugOpen\Core\EventDispatcher
     */
    public function getEventDispatcher()
    {
        if ($this->eventDispatcher == null) {

            $this->eventDispatcher = $this->context->getEventDispatcher();
        }

        return $this->eventDispatcher;
    }

    /**
     *
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog()
    {
        if ($this->log == null) {

            $context = $this->context;
            $this->log = $context->getLogRegistry()->getLog($this);
        }

        return $this->log;
    }

    /**
     *
     * @param \Psr\Log\LoggerInterface $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\Situation $situation
     * @param \Datetime $publicationTime
     */
    public function processSituation($situation, $publicationTime)
    {
        $situationId = $situation->getId();

        $keys = array();
        $keys['id'] = $situationId;
        $keys['version'] = $situation->getVersion();

        $tableManager = $this->getTableManager();
        $log = $this->getLog();

        $existingSituation = $tableManager->findRecord('bo_situation', $keys);

        $probability = null;
        $datetimeStart = null;
        $datetimeEnd = null;
        $situationVersionTime = $situation->getSituationVersionTime();
        $status = null;

        $location = null;
        $pointCoordinates = null;

        $situationRecord = $situation->getSituationRecord();

        $notifyListeners = false;

        if ($situationRecord) {

            $groupOfLocations = $situationRecord->getGroupOfLocations();

            if ($groupOfLocations) {

                $alertCPoint = $groupOfLocations->getAlertCPoint();

                if ($alertCPoint) {

                    $primaryPointLocation = $alertCPoint->getAlertCMethod2PrimaryPointLocation();

                    if ($primaryPointLocation) {

                        $alertCLocation = $primaryPointLocation->getAlertCLocation();

                        if ($alertCLocation) {

                            if ($alertCLocation->getSpecificLocation()) {

                                $location = $alertCLocation->getSpecificLocation();

                            }

                        }

                    }

                }

                $pointByCoordinates = $groupOfLocations->getPointByCoordinates();

                if ($pointByCoordinates) {

                    $pointCoordinates = $pointByCoordinates->getPointCoordinates();
                }
            }

            if ($situationRecord->getProbabilityOfOccurrence()) {
                $probability = $situationRecord->getProbabilityOfOccurrence();
            }

            $validity = $situationRecord->getValidity();
            if ($validity) {

                $validityTimeSpecification = $validity->getValidityTimeSpecification();

                if ($validityTimeSpecification) {

                    $datetimeStart = $validityTimeSpecification->getOverallStartTime();

                    $datetimeEnd = $validityTimeSpecification->getOverallEndTime();
                }
            }

            $operatorActionStatus = $situationRecord->getOperatorActionStatus();

            if ($operatorActionStatus) {
                $status = $operatorActionStatus;
            }

            $management = $situationRecord->getManagement();

            if ($management) {

                $lifeCycleManagement = $management->getLifeCycleManagement();

                if ($lifeCycleManagement) {

                    if (! $datetimeEnd) {

                        if ($lifeCycleManagement->getEnd()) {

                            $datetimeEnd = $situation->getSituationVersionTime();
                        }
                    }

                    if ($lifeCycleManagement->getCancel()) {

                        $status = 'cancelled';
                    }
                }
            }
        }

        if ($location == null) {

            $matches = array();

            if (preg_match('/^[a-z0-9]+_(NL[a-z0-9]+)_[a-z0-9]+$/i', $situation->getId(), $matches)) {

                $location = $matches[1];
            }

        }

        if ($existingSituation) {

            if ($publicationTime->getTimestamp() > $existingSituation['last_publication']) {

                $values = array();
                $values['last_publication'] = $publicationTime->getTimestamp();
                $values['last_publication_time'] = $publicationTime;

                if ($datetimeEnd) {
                    $values['datetime_end'] = $datetimeEnd->getTimestamp();
                    $values['time_end'] = $datetimeEnd;
                }

                if ($probability) {
                    $values['probability'] = $probability;
                }

                if ($status) {
                    $values['status'] = $status;
                }

                $res = $tableManager->updateRecords('bo_situation', $values, $keys);

                if ($res) {

                    $notifyListeners = true;
                } else {

                    $log->error('Could not update situation ' . $situationId . ' version ' . $values['version']);
                }
            }
        } else {

            $values = $keys;

            $values['location'] = '';

            if ($location) {

                $values['location'] = $location;
            }

            if ($pointCoordinates) {

                $values['lat'] = $pointCoordinates->getLatitude();
                $values['lng'] = $pointCoordinates->getLongitude();
            }

            if ($datetimeStart) {

                $values['datetime_start'] = $datetimeStart->getTimestamp();
                $values['time_start'] = $datetimeStart;
            } else if ($situationVersionTime) {

                $values['datetime_start'] = $situationVersionTime->getTimestamp();
                $values['time_start'] = $situationVersionTime;
            }

            if ($datetimeEnd) {

                $values['datetime_end'] = $datetimeEnd->getTimestamp();
                $values['time_end'] = $datetimeEnd;
            }

            if ($status) {

                $values['status'] = $status;
            }

            if ($publicationTime) {

                $values['first_publication'] = $publicationTime->getTimestamp();
                $values['first_publication_time'] = $publicationTime;
                $values['last_publication'] = $publicationTime->getTimestamp();
                $values['last_publication_time'] = $publicationTime;
            }

            if ($probability) {
                $values['probability'] = $probability;
            }

            if ($situationVersionTime) {
                $values['datetime_version'] = $situationVersionTime->getTimestamp();
                $values['version_time'] = $situationVersionTime;
            }

            if ($keys['version'] == '1') {

                if ($probability == 'riskOf') {

                    // ignore immediately
                    $values['operation_id'] = 0;
                } else {

                    $notifyListeners = true;
                }
            } else {

                $notifyListeners = true;
            }

            $res = $tableManager->insertRecord('bo_situation', $values);

            if (! $res) {

                $log->error('Could not insert situation ' . $situationId . ' version ' . $values['version']);

                $notifyListeners = false;
            }
        }

        if ($notifyListeners) {

            $eventDispatcher = $this->getEventDispatcher();

            $eventDispatcher->postEvent('Ndw.Situation.update', array(
                $situationId
            ));
        }
    }

    /**
     * @param \DateTime $publicationDateTime
     */
    public function checkUnfinishedGoneOperations($publicationDateTime)
    {
        $tableManager = $this->getTableManager();

        // loop through all unfinished operations
        $keys = array();
        $keys['finished'] = 0;
        $unfinishedOperations = $tableManager->findRecords('bo_operation', $keys);

        foreach ($unfinishedOperations as $activeOperation) {

            $operationId = $activeOperation['id'];

            $situationId = $activeOperation['event_id'];

            if ($situationId == '') {

                continue;

            }

            $keys = array();
            $keys['id'] = $situationId;

            $situations = $tableManager->findRecords('bo_situation', $keys);

            /**
             * @var \DateTime
             */
            $lastPublicationDate = null;

            foreach ($situations as $situation) {

                if ($situation['last_publication_time']) {

                    if (($lastPublicationDate == null) || ($situation['last_publication_time']->getTimestamp() > $lastPublicationDate->getTimestamp())) {

                        $lastPublicationDate = $situation['last_publication_time'];

                    }

                }

            }

            if ($lastPublicationDate) {

                if ($lastPublicationDate->getTimestamp() < $publicationDateTime->getTimestamp()) {

                    // mark operation 'gone'

                    $this->getLog()->info('Marking operation ' . $operationId . ' gone');

                    $updateValues = array();

                    $updateValues['datetime_gone'] = $publicationDateTime->getTimestamp();
                    $updateValues['time_gone'] = $publicationDateTime;
                    $updateValues['finished'] = 1;

                    $hasTimeEnd = false;

                    if (array_key_exists('time_end', $activeOperation)) {

                        if ($activeOperation['time_end']) {

                            $hasTimeEnd = true;

                        }

                    }

                    if (!$hasTimeEnd) {

                        $updateValues['datetime_end'] = $publicationDateTime->getTimestamp();
                        $updateValues['time_end'] = $publicationDateTime;

                    }

                    $keys = array();
                    $keys['id'] = $operationId;

                    $tableManager->updateRecords('bo_operation', $updateValues, $keys);

                    $eventDispatcher = $this->getEventDispatcher();

                    if ($eventDispatcher) {

                        $eventDispatcher->postEvent('Operation.update', array($operationId));

                    }

                }

            }

        }

    }

    /**
     *
     * @return void
     */
    public function markUncertainSituationsIgnored()
    {
        $situationIdsWithoutOperationId = $this->findSituationIdsWithoutOperationId();

        if ($situationIdsWithoutOperationId) {

            $log = $this->getLog();

            $tableManager = $this->getTableManager();

            foreach ($situationIdsWithoutOperationId as $situationId) {

                $criteria = array();
                $criteria['id'] = $situationId;

                $situations = $tableManager->findRecords('bo_situation', $criteria);

                $actualOperationId = null;
                $anyCertainty = false;
                $numRiskOf = 0;

                foreach ($situations as $situation) {

                    if (array_key_exists('operation_id', $situation) && ($situation['operation_id'] > 0)) {

                        $actualOperationId = $situation['operation_id'];

                        break;
                    }

                    if (isset($situation['probability'])) {
                        if ($situation['probability'] == 'certain') {
                            $anyCertainty = true;
                        } else if ($situation['probability'] == 'probable') {
                            $anyCertainty = true;
                        } else if ($situation['probability'] == 'riskOf') {
                            $numRiskOf ++;
                        }
                    }
                }

                $updateOperationId = null;

                if ($actualOperationId > 0) {

                    // copy this to all situations without operation

                    $updateOperationId = $actualOperationId;
                } else {

                    if (! $anyCertainty) {

                        if ($numRiskOf == sizeof($situations)) {

                            // all situations are 'riskOf'
                            $updateOperationId = 0;
                        }
                    }
                }

                if ($updateOperationId !== null) {

                    $log->debug('Updating situations with id ' . $situationId . ' to operation ' . $updateOperationId);

                    foreach ($situations as $situation) {

                        if (! array_key_exists('operation_id', $situation) || ($situation['operation_id'] == '')) {

                            $keys = array();
                            $keys['id'] = $situation['id'];
                            $keys['version'] = $situation['version'];

                            $values = array();
                            $values['operation_id'] = $updateOperationId;

                            $log->debug('Updating situation ' . $situationId . ' version ' . $situation['version'] . ' to operation ' . $updateOperationId);

                            $tableManager->updateRecords('bo_situation', $values, $keys);
                        }
                    }
                }
            }
        }
    }

    public function findSituationIdsWithoutOperationId()
    {

        // collect distinct situation ids with any missing operation_id
        $situationIds = array();

        $tableManager = $this->getTableManager();

        $criteria = array();
        $criteria['operation_id'] = null;

        $fields = array();
        $fields[] = 'id';

        $records = $tableManager->findRecords('bo_situation', $criteria, $fields);

        if ($records) {

            foreach ($records as $record) {

                $situationId = $record['id'];
                $situationIds[$situationId] = $situationId;
            }

            $situationIds = array_values($situationIds);
        }

        return $situationIds;
    }
}
