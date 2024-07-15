<?php
namespace BrugOpen\Tracking\Service;

use BrugOpen\Model\BridgePassage;
use BrugOpen\Service\OperationIndexService;
use BrugOpen\Tracking\Event\SegmentEvent;
use BrugOpen\Tracking\Model\JourneySegment;

class JourneySegmentEventProcessor
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
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var BridgePassageCalculator
     */
    private $bridgePassageCalculator;

    /**
     * @var OperationIndexService
     */
    private $operationIndexService;

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
     *
     * @param \Psr\Log\LoggerInterface $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    /**
     *
     * @return \BrugOpen\Core\EventDispatcher
     */
    public function getEventDispatcher()
    {
        if ($this->eventDispatcher == null) {

            if ($this->context) {

                $this->eventDispatcher = $this->context->getEventDispatcher();

            }

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
     * @return BridgePassageCalculator
     */
    public function getBridgePassageCalculator()
    {

        if ($this->bridgePassageCalculator == null) {

            if ($this->context) {

                $bridgePassageCalculator = $this->context->getService('BrugOpen.BridgePassageCalculator');
                $this->bridgePassageCalculator = $bridgePassageCalculator;

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
     * @return OperationIndexService
     */
    public function getOperationIndexService()
    {
        return $this->operationIndexService;
    }

    /**
     * @param OperationIndexService $operationIndexService
     */
    public function setOperationIndexService($operationIndexService)
    {
        $this->operationIndexService = $operationIndexService;
    }

    /**
     * @param SegmentEvent $event
     */
    public function processSegmentEvent(SegmentEvent $event)
    {

        if ($event->getType() == SegmentEvent::EVENT_TYPE_ENTER) {

            /**
             * @var JourneySegment
             */
            $enteringJourneySegment = $event->getJourneySegment();

            /**
             * @var Segment
             */
            $enteringSegment = $event->getSegment();

            /**
             * @var JourneySegment
             */
            $exitingJourneySegment = null;

            $enteringSegmentId = null;

            if ($enteringSegment) {

                $enteringSegmentId = $enteringSegment->getId();

            }

            $journey = $event->getJourney();

            if ($journey) {

                $journeySegments = $journey->getJourneySegments();

                if ($journeySegments) {

                    $exitingJourneySegmentIndex = null;

                    $previousJourneySegment = $journeySegments[count($journeySegments) - 1];

                    if ($previousJourneySegment->getSegmentId() == $enteringSegmentId) {

                        // find before-last segment

                        if (count($journeySegments) > 1) {

                            $exitingJourneySegment = $journeySegments[count($journeySegments) - 2];
                            $exitingJourneySegmentIndex = count($journeySegments) - 1;

                        }

                    } else {

                        $exitingJourneySegment = $previousJourneySegment;
                        $exitingJourneySegmentIndex = count($journeySegments) - 1;

                    }

                    $previousJourneySegments = array();

                    if (($exitingJourneySegmentIndex !== null) && (count($journeySegments) > 1)) {

                        for ($i = 0; $i < $exitingJourneySegmentIndex; $i++) {

                            $previousJourneySegments[] = $journeySegments[$i];

                        }

                    }

                    $detectedBridgePassages = $this->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

                    if ($detectedBridgePassages) {

                        $logger = $this->getLog();

                        foreach ($detectedBridgePassages as $detectedBridgePassage) {

                            $detectedBridgePassage->setMmsi($journey->getVessel()->getMmsi());
                            $detectedBridgePassage->setVesselType($journey->getVessel()->getVesselType());

                            $bridgeId = $detectedBridgePassage->getBridgeId();
                            $datetimePassage = $detectedBridgePassage->getDatetimePassage();

                            $logger->info("Detected passage along bridge " . $bridgeId . ' on ' . $datetimePassage->format('Y-m-d H:i:s') . ' for ' . $journey->getVessel()->getMmsi());

                            if ($datetimePassage) {

                                $operationId = $this->determineOperationId($bridgeId, $datetimePassage);

                                if ($operationId) {

                                    $detectedBridgePassage->setOperationId($operationId);

                                }

                            }

                            // add passage to Journey
                            $journeyPassages = array();

                            if ($journey->getPassages()) {

                                $journeyPassages = $journey->getPassages();

                            }

                            $journeyPassages[] = $detectedBridgePassage;
                            $journey->setPassages($journeyPassages);

                            $eventDispatcher = $this->getEventDispatcher();

                            if ($eventDispatcher) {

                                // dispatch passage event
                                $eventDispatcher->postEvent('Journey.passage', array($detectedBridgePassage, $journey));

                            }

                        }

                    }

                }

            }

        }

    }

    /**
     * @param JourneySegment $exitingJourneySegment
     * @param JourneySegment $enteringJourneySegment
     * @param JourneySegment[] $previousJourneySegments
     * @return BridgePassage[]
     */
    public function determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments)
    {

        $bridgePassages = array();

        $bridgePassageCalculator = $this->getBridgePassageCalculator();

        if ($bridgePassageCalculator) {

            $bridgePassages = $bridgePassageCalculator->determineBridgePassages($exitingJourneySegment, $enteringJourneySegment, $previousJourneySegments);

        }

        return $bridgePassages;

    }

    /**
     * @param int $bridgeId
     * @param \DateTime $datetimePassage
     */
    public function determineOperationId($bridgeId, $datetimePassage)
    {

        $operationId = null;

        if ($bridgeId && $datetimePassage) {

            $timestamp = $datetimePassage->getTimestamp();

            $operationIndexService = $this->getOperationIndexService();

            if ($operationIndexService) {

                $lastStartedOperation = $operationIndexService->getLastStartedOperation($bridgeId, $timestamp);

                if ($lastStartedOperation) {

                    $passageTimeIsDuringOperation = false;

                    if ($lastStartedOperation->getDateTimeStart()) {

                        $timeSinceOperationStart = $timestamp - $lastStartedOperation->getDateTimeStart()->getTimestamp();

                        if ($timeSinceOperationStart >= 30) {

                            // passage is at least 30 seconds since operation start

                            if ($lastStartedOperation->getDateTimeEnd()) {

                                if ($timestamp < $lastStartedOperation->getDateTimeEnd()->getTimestamp()) {

                                    $passageTimeIsDuringOperation = true;

                                }

                            } else {

                                // at most 30 minutes after start

                                if ($timeSinceOperationStart < (60 * 30)) {

                                    $passageTimeIsDuringOperation = true;

                                }

                            }

                        }

                    }

                    if ($passageTimeIsDuringOperation) {

                        $operationId = (int)$lastStartedOperation->getId();

                    }

                }

            }

        }

        return $operationId;

    }

}
