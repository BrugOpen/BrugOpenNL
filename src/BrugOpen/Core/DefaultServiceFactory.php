<?php

namespace BrugOpen\Core;

use BrugOpen\Ndw\Service\NdwQueueProcessor;
use BrugOpen\Ndw\Service\SituationEventProcessor;
use BrugOpen\Ndw\Service\SituationProcessor;
use BrugOpen\Projection\Service\OperationProjectionEventProcessor;
use BrugOpen\Projection\Service\OperationProjectionService;
use BrugOpen\Service\BridgeIndexService;
use BrugOpen\Service\BridgeService;
use BrugOpen\Service\OperationIndexService;
use BrugOpen\Service\PassageService;
use BrugOpen\Tracking\Service\BridgeEventProcessor;
use BrugOpen\Tracking\Service\BridgePassageCalculator;
use BrugOpen\Tracking\Service\JourneyDataStore;
use BrugOpen\Tracking\Service\JourneySegmentEventProcessor;
use BrugOpen\Tracking\Service\VesselPositionProcessor;
use BrugOpen\Tracking\Service\WaterwayService;

class DefaultServiceFactory implements ServiceFactory
{

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Core\ServiceFactory::createService()
     */
    public function createService($serviceName, $context)
    {
        $service = null;

        if ($serviceName == 'BrugOpen.BridgePassageCalculator') {

            $bridgePassageCalculator = new BridgePassageCalculator();
            $bridgePassageCalculator->initialize($context);

            $service = $bridgePassageCalculator;
        } else if ($serviceName == 'BrugOpen.BridgeService') {

            $bridgeService = new BridgeService();
            $bridgeService->initialize($context);

            $service = $bridgeService;
        } else if ($serviceName == 'BrugOpen.NdwQueueProcessor') {

            $queueProcessor = new NdwQueueProcessor($context);

            $bridgeIndexService = new BridgeIndexService();
            $bridgeIndexService->initialize($context);

            $situationEventProcessor = new SituationEventProcessor($context);

            $context->getEventDispatcher()->addObserver('Ndw.Situation.update', array($situationEventProcessor, 'onSituationUpdate'));

            $situationProcessor = new SituationProcessor($context);

            $queueProcessor->setSituationProcessor($situationProcessor);

            $service = $queueProcessor;
        } else if ($serviceName == 'BrugOpen.JourneyDataStore') {

            $journeyDataStore = new JourneyDataStore();
            $journeyDataStore->initialize($context);

            $service = $journeyDataStore;
        } else if ($serviceName == 'BrugOpen.OperationIndexService') {

            $operationIndexService = new OperationIndexService();
            $operationIndexService->initialize($context);

            $service = $operationIndexService;
        } else if ($serviceName == 'BrugOpen.PassageService') {

            $passageService = new PassageService();
            $passageService->initialize($context);

            $service = $passageService;
        } else if ($serviceName == 'BrugOpen.VesselPositionProcessor') {

            $vesselPositionProcessor = new VesselPositionProcessor();
            $vesselPositionProcessor->initialize($context);

            $journeySegmentEventProcessor = new JourneySegmentEventProcessor();
            $journeySegmentEventProcessor->initialize($context);

            $context->getEventDispatcher()->addObserver('VesselJourneySegment.update', array($journeySegmentEventProcessor, 'processSegmentEvent'));

            $bridgeEventProcessor = new BridgeEventProcessor();
            $bridgeEventProcessor->initialize($context);

            $context->getEventDispatcher()->addObserver('Journey.passage', array($bridgeEventProcessor, 'onBridgePassage'));

            $service = $vesselPositionProcessor;
        } else if ($serviceName == 'BrugOpen.OperationProjectionService') {

            $operationProjectionService = new OperationProjectionService();
            $operationProjectionService->initialize($context);

            $operationProjectionEventProcessor = new OperationProjectionEventProcessor($context);

            $context->getEventDispatcher()->addObserver('OperationProjection.update', array($operationProjectionEventProcessor, 'onOperationProjectionUpdated'));

            $service = $operationProjectionService;
        } else if ($serviceName == 'BrugOpen.WaterwayService') {

            $waterwayService = new WaterwayService();
            $waterwayService->initialize($context);

            $service = $waterwayService;
        }

        return $service;
    }
}
