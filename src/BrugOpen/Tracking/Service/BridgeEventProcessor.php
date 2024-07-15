<?php
namespace BrugOpen\Tracking\Service;

use BrugOpen\Core\Context;
use BrugOpen\Model\BridgePassage;
use BrugOpen\Service\OperationIndexService;
use BrugOpen\Service\PassageService;
use BrugOpen\Tracking\Model\VesselJourney;

class BridgeEventProcessor
{

    /**
     * @var Context
     */
    private $context;

    /**
     * @var OperationIndexService
     */
    private $operationIndexService;

    /**
     * @var PassageService
     */
    private $passageService;

    public function initialize($context)
    {
        $this->context = $context;
    }

    /**
     * @return OperationIndexService
     */
    public function getOperationIndexService()
    {

        if ($this->operationIndexService == null) {

            if ($this->context) {

                $operationIndexService = $this->context->getService('BrugOpen.OperationIndexService');

                $this->operationIndexService = $operationIndexService;

            }

        }

        return $this->operationIndexService;

    }

    /**
     * @return PassageService
     */
    public function getPassageService()
    {

        if ($this->passageService == null) {

            if ($this->context) {

                $passageService = $this->context->getService('BrugOpen.PassageService');

                $this->passageService = $passageService;

            }

        }

        return $this->passageService;

    }

    /**
     * @param BridgePassage $bridgePassage
     * @param VesselJourney $journey
     */
    public function onBridgePassage($bridgePassage, $journey)
    {

        if (!$bridgePassage->getOperationId()) {

            // determine operationId

            $operationId = null;

            $bridgeId = $bridgePassage->getBridgeId();
            $datetimePassage = $bridgePassage->getDatetimePassage();
            $timestampPassage = $datetimePassage->getTimestamp();

            if ($timestampPassage && $bridgeId) {

                $operationIndexService = $this->getOperationIndexService();

                if ($operationIndexService) {

                    $lastStartedOperation = $operationIndexService->getLastStartedOperation($bridgeId, $timestampPassage);

                    if ($lastStartedOperation && $lastStartedOperation->getDateTimeStart()) {

                        $operationStartTime = $lastStartedOperation->getDateTimeStart()->getTimestamp();
                        $operationEndTime = $lastStartedOperation->getDateTimeEnd() ? $lastStartedOperation->getDateTimeEnd()->getTimestamp() : null;

                        $operationTimesMatch = false;

                        if ($operationEndTime) {

                            if ($operationEndTime > $timestampPassage) {

                                $operationTimesMatch = true;

                            }

                        } else {

                            // assume matching if operation is underway for max 30 minutes

                            $duration = $timestampPassage - $operationStartTime;

                            if ($duration < (60 * 30)) {

                                $operationTimesMatch = true;

                            }

                        }

                        if ($operationTimesMatch) {

                            if ($lastStartedOperation->getId()) {

                                $operationId = $lastStartedOperation->getId();

                            }

                        }

                    }

                }

            }

            if ($operationId) {

                $bridgePassage->setOperationId($operationId);

            }

            // update operation in database if not exists

            $passageService = $this->getPassageService();

            if ($passageService) {

                $mmsi = $bridgePassage->getMmsi();

                $existingVesselPassage = $passageService->findVesselPassage($mmsi, $bridgeId, $datetimePassage);

                if (!$existingVesselPassage) {

                    $direction = $bridgePassage->getDirection();
                    $vesselType = null;

                    if ($journey && $journey->getVessel()) {

                        $vesselType = $journey->getVessel()->getVesselType();

                    }

                    $passageService->insertPassage($mmsi, $bridgeId, $datetimePassage, $direction, $vesselType, $operationId);

                }

            }

        }

    }

}
