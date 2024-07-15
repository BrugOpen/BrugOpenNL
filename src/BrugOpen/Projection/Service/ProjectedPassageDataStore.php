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
            $values['situation_id'] = $passageProjection->getSituationId();
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
                                $obsoleteProjection->setSituationId($lastProjection['situation_id']);
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
}
