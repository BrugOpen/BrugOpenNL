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

            $connectionManager = $this->context->getDatabaseConnectionManager();
            $connection = $connectionManager->getConnection();
            $tableManager = new DatabaseTableManager($connection);

            $this->tableManager = $tableManager;
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

                            $location = $alertCLocation->getSpecificLocation();
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

        $matches = array();

        if (preg_match('/^[a-z0-9]+_(NL[a-z0-9]+)_[a-z0-9]+$/i', $situation->getId(), $matches)) {

            $location = $matches[1];
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

                $res = $tableManager->updateTable('bo_situation', $keys, $values);

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
}
