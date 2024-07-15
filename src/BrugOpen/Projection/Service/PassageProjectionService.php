<?php

namespace BrugOpen\Projection\Service;

use BrugOpen\Core\Context;
use BrugOpen\Db\Service\TableManager;
use BrugOpen\Model\Bridge;
use BrugOpen\Model\BridgePassage;
use BrugOpen\Projection\Model\ProjectedBridgePassage;
use BrugOpen\Service\BridgeService;
use BrugOpen\Service\PassageService;
use BrugOpen\Tracking\Model\VesselJourney;
use BrugOpen\Tracking\Service\BridgePassageCalculator;
use BrugOpen\Tracking\Service\JourneyArchiveStore;
use BrugOpen\Tracking\Service\JourneyReconstructor;
use Psr\Log\LoggerInterface;

class PassageProjectionService
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @var TableManager
     */
    private $tableManager;

    /**
     * @var JourneyReconstructor
     */
    private $journeyReconstructor;

    /**
     * @var JourneyProjector
     */
    private $journeyProjector;

    /**
     * @var JourneyArchiveStore
     */
    private $journeyArchiveStore;

    /**
     * @var ProjectedPassageDataStore
     */
    private $projectedPassageDataStore;

    /**
     * @var BridgePassageCalculator
     */
    private $bridgePassageCalculator;

    /**
     * @var BridgeService
     */
    private $bridgeService;

    /**
     * @var PassageService
     */
    private $passageService;

    /**
     *
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
    }

    /**
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog()
    {
        if ($this->log == null) {

            $context = $this->context;
            if ($context != null) {

                $this->log = $context->getLogRegistry()->getLog($this);
            }
        }

        return $this->log;
    }

    /**
     * @param LoggerInterface $loggerInterface
     */
    public function setLog($loggerInterface)
    {
        $this->log = $loggerInterface;
    }

    /**
     * @return TableManager
     */
    public function getTableManager()
    {
        if ($this->tableManager == null) {

            if ($this->context != null) {

                $this->tableManager = $this->context->getService('BrugOpen.TableManager');
            }
        }

        return $this->tableManager;
    }

    /**
     * @param TableManager $tableManager
     */
    public function setTableManager($tableManager)
    {
        $this->tableManager = $tableManager;
    }

    /**
     * @return JourneyReconstructor
     */
    public function getJourneyReconstructor()
    {
        if ($this->journeyReconstructor == null) {

            if ($this->context) {
                $journeyReconstructor = new JourneyReconstructor();
                $journeyReconstructor->initialize($this->context);
                $this->journeyReconstructor = $journeyReconstructor;
            }
        }
        return $this->journeyReconstructor;
    }

    /**
     * @param JourneyReconstructor $journeyReconstructor
     */
    public function setJourneyReconstructor($journeyReconstructor)
    {
        $this->journeyReconstructor = $journeyReconstructor;
    }

    /**
     * @return JourneyProjector
     */
    public function getJourneyProjector()
    {
        if ($this->journeyProjector == null) {

            if ($this->context) {
                $journeyProjector = new JourneyProjector();
                $journeyProjector->initialize($this->context);
                $this->journeyProjector = $journeyProjector;
            }
        }

        return $this->journeyProjector;
    }

    /**
     * @param JourneyProjector $journeyProjector
     */
    public function setJourneyProjector($journeyProjector)
    {
        $this->journeyProjector = $journeyProjector;
    }

    /**
     * @return JourneyArchiveStore
     */
    public function getJourneyArchiveStore()
    {

        if ($this->journeyArchiveStore == null) {

            if ($this->context) {

                $journeyArchiveStore = new JourneyArchiveStore();
                $journeyArchiveStore->initialize($this->context);

                $this->journeyArchiveStore = $journeyArchiveStore;
            }
        }

        return $this->journeyArchiveStore;
    }

    /**
     * @return ProjectedPassageDataStore
     */
    public function getProjectedPassageDataStore()
    {

        if ($this->projectedPassageDataStore == null) {

            $projectedPassageDataStore = new ProjectedPassageDataStore();
            $projectedPassageDataStore->initialize($this->context);

            $this->projectedPassageDataStore = $projectedPassageDataStore;
        }

        return $this->projectedPassageDataStore;
    }

    /**
     * @return BridgePassageCalculator
     */
    public function getBridgePassageCalculator()
    {
        if ($this->bridgePassageCalculator == null) {

            if ($this->context != null) {

                $this->bridgePassageCalculator = $this->context->getService('BrugOpen.BridgePassageCalculator');
            }
        }

        return $this->bridgePassageCalculator;
    }

    /**
     * @param BridgePassageCalculator $bridgePassageCalculator
     */
    public function setBridgePassageCalculator($bridgePassageCalculator)
    {
        $this->bridgePassageCalculator = $bridgePassageCalculator;
    }

    /**
     * @return BridgeService
     */
    public function getBridgeService()
    {
        if ($this->bridgeService == null) {

            if ($this->context != null) {

                $this->bridgeService = $this->context->getService('BrugOpen.BridgeService');
            }
        }
        return $this->bridgeService;
    }

    /**
     * @param BridgeService $bridgeService
     */
    public function setBridgeService($bridgeService)
    {
        $this->bridgeService = $bridgeService;
    }

    /**
     * @return PassageService
     */
    public function getPassageService()
    {
        if ($this->passageService == null) {

            if ($this->context != null) {

                $this->passageService = $this->context->getService('BrugOpen.PassageService');
            }
        }
        return $this->passageService;
    }

    /**
     * @param PassageService $passageService
     */
    public function setPassageService($passageService)
    {
        $this->passageService = $passageService;
    }

    /**
     * @param VesselJourney[] $activeJourneys
     */
    public function updatePassageProjections($activeJourneys)
    {

        $logger = $this->getLog();

        $numActiveJourneys = count($activeJourneys);

        $projectedPassageDataStore = $this->getProjectedPassageDataStore();

        $datetimeProjection = new \DateTime();

        if ($numActiveJourneys) {

            $logger->info('Updating ' . $numActiveJourneys . ' active journey' . ($numActiveJourneys != 1 ? 's' : ''));

            $numJourneysProcessed = 0;

            foreach ($activeJourneys as $activeJourney) {

                $projectedBridgePassages = $this->createPassageProjections($activeJourney);

                if ($projectedBridgePassages) {

                    foreach ($projectedBridgePassages as $projectedBridgePassage) {

                        $datetimeProjectedPassage = $projectedBridgePassage->getDatetimeProjectedPassage();

                        if ($datetimeProjectedPassage->getTimestamp() >= $datetimeProjection->getTimestamp()) {

                            $bridgeId = $projectedBridgePassage->getBridgeId();

                            $passageProjection = $projectedBridgePassage;
                            $passageProjection->setJourneyId($activeJourney->getId());
                            $passageProjection->setDatetimeProjection($datetimeProjection);

                            $logger->info('Storing projected passage on bridge ' . $bridgeId . ' for journey ' . $activeJourney->getId());
                            $projectedPassageDataStore->storePassageProjection($passageProjection);
                        }
                    }
                }

                $numJourneysProcessed++;

                if ($numJourneysProcessed % 10 == 0) {

                    $logger->debug('Processed ' . $numJourneysProcessed . ' journeys');
                }
            }

            $logger->info('Done processing ' . $numJourneysProcessed . ' journeys');

            // clean up obsolete passages
            $obsoleteProjections = $projectedPassageDataStore->findObsoletePassageProjections($datetimeProjection);

            if ($obsoleteProjections) {

                foreach ($obsoleteProjections as $obsoleteProjection) {

                    $journeyId = $obsoleteProjection->getJourneyId();
                    $bridgeId = $obsoleteProjection->getBridgeId();

                    $logger->info('Marking passage on bridge ' . $bridgeId . ' for journey ' . $journeyId . ' as gone');

                    // create additional 'gone' projection
                    $goneProjection = new ProjectedBridgePassage();
                    $goneProjection->setJourneyId($journeyId);
                    $goneProjection->setBridgeId($bridgeId);
                    $goneProjection->setDatetimeProjection($datetimeProjection);

                    $projectedPassageDataStore->storePassageProjection($goneProjection);
                }
            }
        }
    }

    /**
     * @param VesselJourney $activeJourney
     * @return ProjectedBridgePassage[]
     */
    public function createPassageProjections($activeJourney)
    {

        $projectedBridgePassages = array();

        $logger = $this->getLog();

        $currentSegmentId = null;
        $previousSegmentId = null;
        $timestamp = null;
        $latLng = null;

        $segments = $activeJourney->getJourneySegments();

        if (count($segments) > 1) {

            $currentSegmentId = $segments[count($segments) - 1]->getSegmentId();
            $previousSegmentId = $segments[count($segments) - 2]->getSegmentId();
            $timestamp = $segments[count($segments) - 1]->getLastTimestamp();
            $latLng = $segments[count($segments) - 1]->getLastLocation();
        }

        if ($currentSegmentId && $previousSegmentId && $timestamp && $latLng) {

            $mmsi = $activeJourney->getVessel()->getMmsi();

            $journeyArchiveStore = $this->getJourneyArchiveStore();
            $journeyReconstructor = $this->getJourneyReconstructor();
            $journeyProjector = $this->getJourneyProjector();

            $numPastJourneys = 0;

            if ($journeyArchiveStore) {

                $logger->debug('Loading past journeys for ' . $mmsi);

                $pastJourneys = $journeyArchiveStore->loadVesselJourneys($mmsi);
                $numPastJourneys = count($pastJourneys);

                $logger->debug('Found ' . $numPastJourneys . ' past journey' . ($numPastJourneys != 1 ? 's' : '') .  ' for ' . $mmsi);
            }

            if ($numPastJourneys) {

                $matchingRouteJourneys = array();

                foreach ($pastJourneys as $pastJourney) {

                    $allSegmentsConnected = $journeyReconstructor->journeySegmentsConnected($pastJourney);

                    if (!$allSegmentsConnected) {
                        // $logger->debug('Reconstructing full journey for ' . $activeJourney->getId());
                        $journeyReconstructor->reconstructFullJourney($pastJourney);
                    }
                }

                $segmentIds = array($previousSegmentId, $currentSegmentId);

                $logger->debug('Determining matching routes in segments ' . $previousSegmentId  . ' -> ' . $currentSegmentId . ',' . ' for ' . $mmsi);

                $matchingRouteJourneys = $journeyProjector->findMatchingRouteJourneys($segmentIds, $pastJourneys);

                $numMatchingRouteJourneys = count($matchingRouteJourneys);

                $logger->debug('Found ' . $numMatchingRouteJourneys . ' matching route journey' . ($numMatchingRouteJourneys != 1 ? 's' : '') . ' for ' . $activeJourney->getId());

                if ($numMatchingRouteJourneys) {

                    $reliability = 0.95;

                    $logger->debug('Projecting journey segments for ' . $activeJourney->getId());

                    $projectedSegmentIds = $journeyProjector->projectJourneySegments($previousSegmentId, $currentSegmentId, $matchingRouteJourneys, $reliability);

                    $logger->debug('Finding matching past route journeys for ' . $activeJourney->getId());

                    $matchingRouteJourneys = $journeyProjector->findMatchingRouteJourneys($projectedSegmentIds, $pastJourneys);

                    // prepend current segment id
                    $passageSegmentIds = $projectedSegmentIds;
                    array_unshift($passageSegmentIds, $currentSegmentId);

                    $projectedBridgePassages = $journeyProjector->projectBridgePassages($passageSegmentIds, $timestamp, $latLng, $matchingRouteJourneys);
                }
            }

            if (!$projectedBridgePassages) {

                // find longest single track, project passages based on current journey

                $journeySegments = $activeJourney->getJourneySegments();

                $allSegmentsConnected = $journeyReconstructor->journeySegmentsConnected($activeJourney);

                if (!$allSegmentsConnected) {
                    $journeySegments = $journeyReconstructor->reconstructJourneySegments($activeJourney);
                }

                $journeySegmentIds = array();

                foreach ($journeySegments as $segment) {

                    $journeySegmentIds[] = $segment->getSegmentId();
                }

                if (count($journeySegmentIds) > 1) {

                    // collect current journey segment ids

                    $logger->debug('Projecting journey segments for ' . $activeJourney->getId() . ' based on current journey');

                    $singleTrackSegmentIds = $journeyProjector->findLongestSingleTrack($journeySegmentIds);

                    if ($singleTrackSegmentIds) {

                        // determine bridges that will be passed during that track

                        // prepend current segment id
                        $projectedSegmentIds = $singleTrackSegmentIds;
                        array_unshift($projectedSegmentIds, $currentSegmentId);

                        $projectedBridges = $this->determinePassedBridges($projectedSegmentIds);

                        if ($projectedBridges) {

                            // determine current speed and deviation
                            $currentSpeedData = $journeyProjector->determineCurrentSpeed($segments);

                            if ($currentSpeedData) {

                                // create passage projections

                                foreach ($projectedBridges as $bridge) {

                                    $bridgeId = $bridge->getId();

                                    $speed = $currentSpeedData[0];
                                    $speedStandardDeviation = $currentSpeedData[1];

                                    $routeToBridge = $journeyProjector->projectRouteToBridge($bridge, $latLng, $projectedSegmentIds);

                                    if ($routeToBridge) {

                                        $distance = $routeToBridge->getLineLength();

                                        if ($distance > 0) {

                                            $speedMeterPerSecond = $speed * 1000 / 3600;
                                            $timeNeeded = round($distance / $speedMeterPerSecond);

                                            if ($timeNeeded > 0) {

                                                $datetimeProjectedPassage = new \DateTime('@' . ($timestamp + $timeNeeded));

                                                $timeStandardDeviation = (int)round(($speedStandardDeviation / $speed) * $timeNeeded);
                                                $operationProbability = $this->determineOperationProbability($activeJourney, $bridgeId, $datetimeProjectedPassage);

                                                $projectedBridgePassage = new ProjectedBridgePassage();
                                                $projectedBridgePassage->setJourneyId($activeJourney->getId());
                                                $projectedBridgePassage->setBridgeId($bridgeId);
                                                $projectedBridgePassage->setDatetimeProjectedPassage($datetimeProjectedPassage);
                                                $projectedBridgePassage->setStandardDeviation($timeStandardDeviation);
                                                $projectedBridgePassage->setOperationProbability($operationProbability);

                                                $projectedBridgePassages[] = $projectedBridgePassage;
                                            }
                                        }
                                    } else {

                                        $logger->error('Unable to determine bridge passage time for bridge ' . $bridgeId . ' for ' . $activeJourney->getId());
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $projectedBridgePassages;
    }

    /**
     * @param int[] $segmentIds
     * @return Bridge[]
     */
    public function determinePassedBridges($segmentIds)
    {

        $passedBridges = array();

        $bridgePassageCalculator = $this->getBridgePassageCalculator();

        if ($bridgePassageCalculator) {

            $allSegmentIds = array_values($segmentIds);

            if (count($allSegmentIds) > 1) {

                for ($i = 0; $i <= (count($allSegmentIds) - 2); $i++) {

                    $previousSegmentId = $allSegmentIds[$i];
                    $nextSegmentId = $allSegmentIds[$i + 1];

                    $connectedBridge = $bridgePassageCalculator->getConnectedBridge($previousSegmentId, $nextSegmentId);

                    if ($connectedBridge) {

                        $passedBridges[] = $connectedBridge;
                    }
                }
            }
        }

        return $passedBridges;
    }

    /**
     * @param VesselJourney $journey
     * @param int $bridgeId
     * @param \DateTime $datetimeProjectedPassage
     * @return float[]
     */
    public function determineOperationProbability($journey, $bridgeId, $datetimeProjectedPassage)
    {

        $operationProbability = null;

        if ($journey && $bridgeId) {

            // check if vessel has already passed that bridge before
            $mmsi = null;

            if ($journey->getVessel()) {
                $mmsi = $journey->getVessel()->getMmsi();
            }

            if ($mmsi) {
                $operationProbability = $this->determineOperationProbabilityByPreviousPassages($mmsi, $bridgeId, $datetimeProjectedPassage);
            }

            if ($operationProbability == null) {

                // find minimum required clearance
                $minClearanceRequired = $this->determineMinimumRequiredClearanceFromJourney($journey);

                if ($minClearanceRequired !== null) {

                    $bridge = $this->getBridgeById($bridgeId);

                    if ($bridge) {

                        $bridgeClearance = $bridge->getClearance();

                        if ($bridgeClearance) {

                            if ($minClearanceRequired >= $bridgeClearance) {
                                $operationProbability = 1;
                            }
                        }
                    }
                }
            }
        }

        return $operationProbability;
    }

    /**
     * @param string $mmsi
     * @param int $bridgeId
     * @param \DateTime $datetimeProjectedPassage
     * @return float|null
     */
    public function determineOperationProbabilityByPreviousPassages($mmsi, $bridgeId, $datetimeProjectedPassage)
    {

        $operationProbability = null;

        /**
         * @var BridgePassage[]
         */
        $previousPassages = null;

        $passageService = $this->getPassageService();

        if ($passageService) {

            $lastMonth = new \DateTime('@' . ($datetimeProjectedPassage->getTimestamp() - (3600 * 24 * 30)));

            $lastMonthPassages = $passageService->findVesselPassagesByBridge($mmsi, array($bridgeId), $lastMonth);

            if ($lastMonthPassages) {

                if (array_key_exists($bridgeId, $lastMonthPassages)) {

                    $previousPassages = $lastMonthPassages[$bridgeId];
                }
            }

            if (!$previousPassages) {

                $lastYear = new \DateTime('@' . ($datetimeProjectedPassage->getTimestamp() - (3600 * 24 * 365)));

                $lastYearPassages = $passageService->findVesselPassagesByBridge($mmsi, array($bridgeId), $lastYear);

                if ($lastYearPassages) {
                    if (array_key_exists($bridgeId, $lastYearPassages)) {

                        $previousPassages = $lastYearPassages[$bridgeId];
                    }
                }
            }
        }

        $operationIds = array();

        if ($previousPassages) {

            $numPassages = count($previousPassages);
            $numPassagesWithSingleOperation = 0;
            $numPassagesWithoutOperation = 0;

            // collect operation ids
            foreach ($previousPassages as $previousPassage) {

                if ($previousPassage->getOperationId()) {
                    $operationId = $previousPassage->getOperationId();
                    $operationIds[$operationId] = $operationId;
                } else {
                    $numPassagesWithoutOperation++;
                }
            }

            if ($operationIds) {

                // determine passages with single operation
                $passagesByOperation = $passageService->findPassagesByOperation($operationIds);
                if ($passagesByOperation) {

                    foreach ($operationIds as $operationId) {

                        if (array_key_exists($operationId, $passagesByOperation)) {

                            if (count($passagesByOperation[$operationId]) == 1) {

                                if ($passagesByOperation[$operationId][0]->getMmsi() == $mmsi) {

                                    $numPassagesWithSingleOperation++;
                                }
                            }
                        }
                    }
                }
            }

            if (($numPassagesWithoutOperation / $numPassages) >= 0.5) {

                $operationProbability = 1 - ($numPassagesWithoutOperation / $numPassages);
            } else if ($numPassagesWithSingleOperation > 0) {

                $operationProbability = $numPassagesWithSingleOperation / $numPassages;
            }
        }

        return $operationProbability;
    }

    /**
     * @param VesselJourney $journey
     * @return float|null
     */
    public function determineMinimumRequiredClearanceFromJourney($journey)
    {

        // determine mmsi
        $mmsi = null;
        if ($journey && $journey->getVessel()) {
            $mmsi = $journey->getVessel()->getMmsi();
        }

        // find passages in journey with operation
        $operationsByBridge = array();
        $operationIds = array();

        if ($journey && $journey->getPassages()) {

            foreach ($journey->getPassages() as $passage) {

                $operationId = $passage->getOperationId();

                if ($operationId) {
                    $bridgeId = $passage->getBridgeId();
                    if ($bridgeId) {
                        $operationsByBridge[$bridgeId][] = $operationId;
                        $operationIds[] = $operationId;
                    }
                }
            }
        }

        // load all passages for collected operations
        $passagesByOperation = array();

        if ($operationIds) {

            $passageService = $this->getPassageService();

            if ($passageService) {

                $passagesByOperation = $passageService->findPassagesByOperation($operationIds);
            }
        }

        $bridgesWithSinglePassage = array();

        if ($operationsByBridge && $passagesByOperation) {

            // determine which bridges had a single pass for one of their operation
            foreach (array_keys($operationsByBridge) as $bridgeId) {

                foreach ($operationsByBridge[$bridgeId] as $operationId) {

                    if (array_key_exists($operationId, $passagesByOperation)) {

                        if (count($passagesByOperation[$operationId]) == 1) {

                            if ($passagesByOperation[$operationId][0]->getMmsi() == $mmsi) {
                                $bridgesWithSinglePassage[] = $bridgeId;
                            }
                        }
                    }
                }
            }
        }

        // only process bridges with single passage per operation

        $minClearanceRequired = null;

        if ($bridgesWithSinglePassage) {

            $allBridges = array();

            $bridgeService = $this->getBridgeService();

            if ($bridgeService) {
                $allBridges = $bridgeService->getAllBridges();
            }

            if ($allBridges) {

                foreach ($bridgesWithSinglePassage as $bridgeId) {

                    $bridge = null;

                    if (array_key_exists($bridgeId, $allBridges)) {
                        $bridge = $allBridges[$bridgeId];
                    }

                    if ($bridge) {

                        $bridgeClearance = null;
                        if ($bridge->getClearance()) {
                            $bridgeClearance = $bridge->getClearance();
                        }

                        if ($bridgeClearance) {

                            if (($minClearanceRequired == null) || ($bridgeClearance > $minClearanceRequired)) {
                                $minClearanceRequired = $bridgeClearance;
                            }
                        }
                    }
                }
            }
        }

        return $minClearanceRequired;
    }

    /**
     * @param int $bridgeId
     * @return Bridge|null
     */
    public function getBridgeById($bridgeId)
    {

        $bridge = null;

        if ($bridgeId) {

            $bridgeService = $this->getBridgeService();

            if ($bridgeService) {

                $allBridges = $bridgeService->getAllBridges();

                if ($allBridges) {

                    if (array_key_exists($bridgeId, $allBridges)) {

                        $bridge = $allBridges[$bridgeId];
                    }
                }
            }
        }
        return $bridge;
    }

    public function bridgeHasClearance($bridgeId, $clearance)
    {
        $bridgeHasClearance = null;

        if ($bridgeId && $clearance) {

            $bridge = $this->getBridgeById($bridgeId);

            if ($bridge) {

                if ($bridge->getClearance() !== null) {

                    if ($clearance < $bridge->getClearance()) {
                        $bridgeHasClearance = true;
                    } else {
                        $bridgeHasClearance = false;
                    }
                }
            }
        }

        return $bridgeHasClearance;
    }
}
