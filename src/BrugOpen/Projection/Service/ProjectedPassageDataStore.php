<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Db\Model\Criterium;
use BrugOpen\Db\Model\CriteriumFieldComparison;
use BrugOpen\Projection\Model\ProjectedBridgePassage;

class ProjectedPassageDataStore
{

    /**
     * @var Context
     */
    private $context;

    /**
     *
     * @var TableManager
     */
    private $tableManager;

    /**
     * @param Context $context
     */
    public function initialize($context)
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
     * @param ProjectedBridgePassage $passageProjection
     */
    public function storePassageProjection($passageProjection)
    {

        $journeyId = $passageProjection->getJourneyId();
        $bridgeId = $passageProjection->getBridgeId();
        $tableManager = $this->getTableManager();

        if ($journeyId && $bridgeId && $tableManager) {

            $criteria = array();
            $criteria['journey_id'] = $journeyId;
            $criteria['bridge_id'] = $bridgeId;

            $operationProbability = null;
            if ($passageProjection->getOperationProbability() !== null) {
                $operationProbability = number_format($passageProjection->getOperationProbability(), 3, '.', '');
            }

            $values = array();
            // $values['version'] = $version;
            $values['datetime_passage'] = $passageProjection->getDatetimeProjectedPassage();
            $values['standard_deviation'] = $passageProjection->getStandardDeviation();
            $values['operation_probability'] = $operationProbability;
            $values['datetime_projection'] = $passageProjection->getDatetimeProjection();

            $values = array_merge($criteria, $values);
            $tableManager->insertRecord('bo_passage_projection', $values);
        }
    }

    /**
     * @param \DateTime $datetimeProjection
     * @return ProjectedBridgePassage[]
     */
    public function findObsoletePassageProjections($datetimeProjection)
    {

        $obsoleteProjections = array();

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $criteria = array();
            $criteria[] = new CriteriumFieldComparison('datetime_passage', Criterium::OPERATOR_GT, $datetimeProjection);

            $records = $tableManager->findRecords('bo_passage_projection', $criteria);

            if ($records) {

                foreach ($records as $record) {

                    $journeyId = $record['journey_id'];
                    $bridgeId = $record['bridge_id'];

                    // find last passage projection for this bridge in this journey

                    $criteria = array();
                    $criteria['journey_id'] = $journeyId;
                    $criteria['bridge_id'] = $bridgeId;

                    $orders = array();
                    $orders[] = array('datetime_projection', 'DESC');

                    $lastProjections = $tableManager->findRecords('bo_passage_projection', $criteria, null, $orders, 1);

                    if ($lastProjections) {

                        $lastProjection = $lastProjections[0];

                        $isObsolete = false;

                        if ($lastProjection['datetime_passage']) {

                            if ($lastProjection['datetime_passage']->getTimestamp() > $datetimeProjection->getTimestamp()) {

                                if ($lastProjection['datetime_projection']->getTimestamp() < $datetimeProjection->getTimestamp()) {

                                    $isObsolete = true;
                                }
                            }
                        }

                        if ($isObsolete) {

                            $projectionKey = $journeyId . '-' . $bridgeId;

                            if (!array_key_exists($projectionKey, $obsoleteProjections)) {

                                $obsoleteProjection = new ProjectedBridgePassage();

                                $obsoleteProjection->setJourneyId($journeyId);
                                $obsoleteProjection->setBridgeId($bridgeId);
                                $obsoleteProjection->setDatetimeProjectedPassage($lastProjection['datetime_passage']);
                                $obsoleteProjection->setStandardDeviation($lastProjection['standard_deviation']);
                                $obsoleteProjection->setOperationProbability($lastProjection['operation_probability']);
                                $obsoleteProjection->setDatetimeProjection($lastProjection['datetime_projection']);

                                $obsoleteProjections[$projectionKey] = $obsoleteProjection;
                            }
                        }
                    }
                }
            }
        }

        return $obsoleteProjections;
    }

    /**
     * @return ProjectedBridgePassage[]
     */
    public function loadCurrentPassageProjections()
    {
        $passageProjections = array();

        $tableManager = $this->getTableManager();

        $lastId = null;
        $limit = 1000;
        $onlySince = time() - 60;
        do {

            $orders = array(array('id', 'DESC'));
            $criteria = array();
            if ($lastId !== null) {
                $criteria[] = new CriteriumFieldComparison('id', Criterium::OPERATOR_LT, $lastId);
            }
            $records = $tableManager->findRecords('bo_passage_projection', null, $criteria, $orders, $limit);

            $lastId = null;

            if ($records) {

                foreach ($records as $record) {

                    $lastId = $record['id'];

                    $datetimeProjection = $record['datetime_projection'];
                    if ($datetimeProjection === null) {
                        continue;
                    }
                    if ($datetimeProjection->getTimestamp() < $onlySince) {
                        $lastId = null;
                        break;
                    }

                    $passageProjection = new ProjectedBridgePassage();
                    $passageProjection->setId($record['id']);
                    $passageProjection->setJourneyId($record['journey_id']);
                    $passageProjection->setBridgeId($record['bridge_id']);
                    $passageProjection->setDatetimeProjectedPassage($record['datetime_passage']);
                    $passageProjection->setStandardDeviation($record['standard_deviation']);
                    $passageProjection->setOperationProbability($record['operation_probability']);
                    $passageProjection->setDatetimeProjection($record['datetime_projection']);
                    $passageProjections[] = $passageProjection;

                    if ($datetimeProjection->getTimestamp() > $onlySince) {
                        $onlySince = $datetimeProjection->getTimestamp();
                    }
                }
            }
        } while ($lastId !== null);

        return $passageProjections;
    }
}
