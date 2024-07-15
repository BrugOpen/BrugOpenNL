<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Core\Context;
use BrugOpen\Event\VesselJourneyEvent;
use BrugOpen\Event\VesselSegmentEvent;
use BrugOpen\Log\Log;
use BrugOpen\Model\VesselJourney;
use BrugOpen\Model\VesselLocation;

class VesselJourneyRecorder
{

    /**
     *
     * @var Log
     */
    private $log;

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var JourneyDataStore
     */
    private $journeyDataStore;

    /**
     *
     * @param Log $logger
     */
    public function __construct($logger)
    {
        $this->log = $logger;
    }

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
     * @return \BrugOpen\Service\JourneyDataStore
     */
    public function getJourneyDataStore()
    {

        if ($this->journeyDataStore == null) {

            $this->journeyDataStore = new JourneyDataStore();
            $this->journeyDataStore->initialize($this->context);

        }

        return $this->journeyDataStore;
    }

    /**
     *
     * @param JourneyDataStore $journeyDataStore
     */
    public function setJourneyDataStore($journeyDataStore)
    {
        $this->journeyDataStore = $journeyDataStore;
    }


    public function handleVesselJourneyEvent(VesselJourneyEvent $event)
    {

        $journeyDataStore = $this->getJourneyDataStore();

        $journey = $event->getJourney();

        if ($journey != null) {

            if ($journey->getId() > 0) {

                if ($event->getType() == VesselJourneyEvent::EVENT_TYPE_UPDATE) {

                    // no need to update database

                } else if ($event->getType() == VesselJourneyEvent::EVENT_TYPE_EXPIRE) {

                    $segmentCounts = $this->getSegmentCounts($journey);

                    if (count($segmentCounts) == 1) {

                        // delete journey as it has only been in 1 segment
                        $this->log->debug('Delete ended journey ' . $journey->getId() . ' with single segment');
                        $journeyDataStore->deleteJourney($journey);

                    } else {

                        $this->log->debug('Mark journey ' . $journey->getId() . ' expired');

                        // mark journey expired
                        $lastLocation = $this->determineLastJourneyLocation($journey);
                        $journey->setLastLocation($lastLocation);
                        $journeyDataStore->storeJourney($journey);

                    }

                } else if ($event->getType() == VesselJourneyEvent::EVENT_TYPE_END) {

                    $segmentCounts = $this->getSegmentCounts($journey);

                    if (count($segmentCounts) == 1) {

                        // delete journey as it has only been in 1 segment
                        $this->log->debug('Delete expired journey ' . $journey->getId() . ' with single segment');
                        $journeyDataStore->deleteJourney($journey);

                    } else {

                        $this->log->debug('Mark journey ' . $journey->getId() . ' ended');

                        // mark journey ended
                        $lastLocation = $this->determineLastJourneyLocation($journey);
                        $journey->setLastLocation($lastLocation);
                        $journeyDataStore->storeJourney($journey);

                    }

                } else if ($event->getType() == VesselJourneyEvent::EVENT_TYPE_FLAP) {

                    // delete journey as it is flapping
                    $this->log->debug('Delete flapping journey ' . $journey->getId());
                    $journeyDataStore->deleteJourney($journey);

                }

            } else {

                if ($event->getType() == VesselJourneyEvent::EVENT_TYPE_START) {

                    $journey->setFirstLocation($event->getCurrentLocation());

                    $journeyDataStore->storeJourney($journey);

                    $this->log->debug('Inserted journey ' . $journey->getId());

                }

            }

        }

    }

    /**
     *
     * @param VesselJourney $journey
     * @return array
     */
    public function getSegmentCounts($journey)
    {

        $segmentCounts = array();

        if ($journey) {

            $segments = $journey->getVesselSegments();

            if ($segments) {

                foreach ($segments as $segment) {

                    $segmentId = (int)$segment->getSegmentId();

                    if ($segmentId) {

                        if (array_key_exists($segmentId, $segmentCounts)) {

                            $segmentCounts[$segmentId]++;

                        } else {

                            $segmentCounts[$segmentId] = 1;

                        }

                    }

                }

            }

        }

        return $segmentCounts;

    }

    public function handleVesselSegmentEvent(VesselSegmentEvent $event)
    {

        $journeyDataStore = $this->getJourneyDataStore();

        $vesselSegment = $event->getVesselSegment();

        if ($vesselSegment) {

            if ($event->getType() == VesselSegmentEvent::EVENT_TYPE_ENTER) {

                $journeyDataStore->storeJourneySegment($vesselSegment);

            } else if ($event->getType() == VesselSegmentEvent::EVENT_TYPE_UPDATE) {

                $currentLocation = $event->getCurrentLocation();

                $vesselSegment->setLastLocation(array($currentLocation->getLat(), $currentLocation->getLon()));
                $vesselSegment->setLastTimestamp($event->getCurrentLocation()->getTime());

                $journeyDataStore->storeJourneySegment($vesselSegment);

            } else if ($event->getType() == VesselSegmentEvent::EVENT_TYPE_EXIT) {

                // no need to update previous segment in journey, last_timestamp will be up to date

            }

        }

    }

    /**
     *
     * @param VesselJourney $journey
     * @return VesselLocation
     */
    public function determineLastJourneyLocation($journey)
    {

        $lastLocation = null;

        $journeyIds = array();
        $journeyIds[] = $journey->getId();

        $lastVesselLocations = $this->journeyDataStore->loadLatestVesselSegments($journeyIds);

        if ($lastVesselLocations) {

            $mmsi = $journey->getMmsi();

            if (array_key_exists($mmsi, $lastVesselLocations)) {

                $lastSegment = $lastVesselLocations[$mmsi];

                $locationParts = $lastSegment->getLastLocation();

                $lastLocation = new VesselLocation();
                $lastLocation->setTime($lastSegment->getLastTimestamp());
                $lastLocation->setLat($locationParts[0]);
                $lastLocation->setLon($locationParts[1]);

            }

        }

        return $lastLocation;

    }

}
