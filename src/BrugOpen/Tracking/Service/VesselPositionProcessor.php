<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Geo\Model\LatLngBounds;
use BrugOpen\Tracking\Event\JourneyEvent;
use BrugOpen\Tracking\Event\SegmentEvent;
use BrugOpen\Tracking\Model\AISRecord;
use BrugOpen\Tracking\Model\JourneySegment;
use BrugOpen\Tracking\Model\Vessel;
use BrugOpen\Tracking\Model\VesselJourney;
use BrugOpen\Tracking\Model\WaterwaySegment;

class VesselPositionProcessor
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
     *
     * @var WaterwaySegment[]
     */
    private $waterwaySegments = array();

    /**
     *
     * @var LatLngBounds[]
     */
    private $waterwaySegmentBounds = array();

    /**
     *
     * @var LatLngBounds
     */
    private $waterwaySegmentOuterBounds;

    /**
     * Current vessel positions
     * @var JourneySegment[]
     */
    private $journeySegments = array();

    /**
     * Current vessel journeys
     * @var VesselJourney[]
     */
    private $journeys = array();

    /**
     * @var VesselJourney[]
     */
    private $endedJourneys = array();

    public function initialize($context)
    {
        $this->context = $context;

        $waterwayService = $context->getService('BrugOpen.WaterwayService');

        $segments = $waterwayService->loadWaterwaySegments();
        $this->initalizeWaterwaySegments($segments);

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
     *
     * @param WaterwaySegment[] $segments
     */
    public function initalizeWaterwaySegments($segments)
    {

        $this->waterwaySegments = array();
        $this->waterwaySegmentBounds = array();

        $outerBoundsContents = array();

        foreach ($segments as $segment) {

            $segmentId = $segment->getId();

            if ($segmentId) {

                $this->waterwaySegments[$segmentId] = $segment;

                $bounds = $segment->getBounds();

                if ($bounds) {

                    $this->waterwaySegmentBounds[$segmentId] = $bounds;

                    $outerBoundsContents[] = $bounds->getNorthEast();
                    $outerBoundsContents[] = $bounds->getSouthWest();

                }

            }

        }

        $outerBounds = new LatLngBounds($outerBoundsContents);

        $this->waterwaySegmentOuterBounds = $outerBounds;

    }

    /**
     * @return LatLngBounds[]
     */
    public function getWaterwaySegmentBounds()
    {
        return $this->waterwaySegmentBounds;
    }

    /**
     * @return LatLngBounds
     */
    public function getWaterwaySegmentOuterBounds()
    {
        return $this->waterwaySegmentOuterBounds;
    }

    /**
     *
     * @param VesselJourney[] $journeys
     */
    public function initializeJourneys($journeys)
    {

        $journeySegments = array();
        $initializedJourneys = array();

        if ($journeys) {

            foreach ($journeys as $journey) {

                $lastSegment = null;

                $segments = $journey->getJourneySegments();

                if ($segments) {

                    foreach ($segments as $journeySegment) {

                        if (($lastSegment == null) || ($journeySegment->getLastTimestamp() > $lastSegment->getLastTimestamp())) {

                            $lastSegment = $journeySegment;

                        }

                    }

                }

                if ($lastSegment) {

                    $lastSegment->setJourney($journey);

                    $mmsi = $journey->getVessel()->getMmsi();
                    $journeySegments[$mmsi] = $lastSegment;
                    $initializedJourneys[$mmsi] = $journey;

                }

            }

        }

        $this->journeys = $initializedJourneys;
        $this->journeySegments = $journeySegments;

    }

    public function processVesselPosition(AISRecord $aisRecord)
    {

        $timeStart = microtime(true);

        $mmsi = $aisRecord->getMmsi();
        $latLng = $aisRecord->getLocation();
        $timestamp = $aisRecord->getTimestamp();

        $journeyTimeout = 60 * 30; // 30 minutes

        $log = $this->getLog();

        /**
         * @var WaterwaySegment
         */
        $lastSegment = null;

        /**
         * @var VesselJourney
         */
        $lastJourney = null;

        /**
         * @var JourneySegment
         */
        $lastJourneySegment = null;

        /**
         * @var WaterwaySegment
         */
        $currentSegment = null;

        /**
         * @var VesselJourney
         */
        $currentJourney = null;

        /**
         * @var JourneySegment
         */
        $currentJourneySegment = null;

        /**
         * @var Vessel
         */
        $vessel = null;

        $stillInLastSegment = false;
        $journeyTimedOut = false;
        $isFlapping = false;
        $flapStarted = false;

        if (array_key_exists($mmsi, $this->journeySegments)) {

            $lastJourneySegment = $this->journeySegments[$mmsi];
            $lastLocation = $lastJourneySegment->getJourney()->getLastLocation();

            $lastSegmentId = $this->journeySegments[$mmsi]->getSegmentId();

            if (array_key_exists($lastSegmentId, $this->waterwaySegments)) {

                $lastSegment = $this->waterwaySegments[$lastSegmentId];

            }

            if ($lastJourneySegment) {

                if ($lastJourneySegment->getJourney()) {

                    $lastJourney = $lastJourneySegment->getJourney();

                }

            }

        }

        if ($lastJourneySegment) {

            if ($aisRecord->getTimestamp() > $lastJourneySegment->getLastTimestamp()) {

                if ($lastSegment->getPolygon()->isPointInPolygon($latLng)) {

                    // vessel is still in last known segment

                    $currentSegment = $lastSegment;

                    $stillInLastSegment = true;

                    $timeInSegment = ($timestamp - $lastJourneySegment->getFirstTimestamp());

                    if (($timeInSegment > ($journeyTimeout)) && (sizeof($lastJourney->getJourneySegments()) > 1)) {

                        $journeyTimedOut = true;

                    }

                }

            }

        }

        if ($currentSegment == null) {

            $isInOuterBounds = $this->waterwaySegmentOuterBounds->isInBounds($latLng);

            if ($isInOuterBounds) {

                // collect matching segments

                /**
                 * @var int[]
                 */
                $matchingSegmentBounds = array();

                /**
                 * @var WaterwaySegment[]
                 */
                $matchingSegments = array();

                foreach ($this->waterwaySegmentBounds as $segmentId => $segmentBounds) {

                    $isInSegmentBounds = $segmentBounds->isInBounds($latLng);

                    if ($isInSegmentBounds) {

                        $matchingSegmentBounds[] = $segmentId;

                    }

                }

                foreach ($matchingSegmentBounds as $segmentId) {

                    $segment = $this->waterwaySegments[$segmentId];

                    $isInSegment = $segment->getPolygon()->isPointInPolygon($latLng);

                    if ($isInSegment) {
                        $matchingSegments[] = $segment;
                    }

                }

                if (sizeof($matchingSegments) == 1) {

                    $currentSegment = $matchingSegments[0];

                }

            }

        }

        if ($lastJourney && $currentSegment) {

            // check if flapping (2 times in 2 segments and no other segments)

            // TODO check if flap started in current segment (was there only 1 time)

            if (count($lastJourney->getJourneySegments()) > 2) {

                $segmentIdCounts = array();

                foreach ($lastJourney->getJourneySegments() as $tmpSegment) {

                    $tmpSegmentId = (int)$tmpSegment->getSegmentId();

                    if ($tmpSegmentId) {

                        if (array_key_exists($tmpSegmentId, $segmentIdCounts)) {

                            $segmentIdCounts[$tmpSegmentId]++;

                        } else {

                            $segmentIdCounts[$tmpSegmentId] = 1;

                        }

                    }

                    if (count($segmentIdCounts) == 2) {

                        // check if journey is flapping between 2 segments (2 times in each)

                        $counts = array_values($segmentIdCounts);

                        if (($counts[0] == 2) && ($counts[1] == 2)) {

                            $isFlapping = true;
                            break;

                        }

                    }

                }

                // check if journey is flapping in and out of same segment

                if (count($segmentIdCounts) == 1) {

                    $isFlapping = true;

                }

            }

        }

        if ($lastSegment || $currentSegment) {

            $vessel = new Vessel();
            $vessel->setMmsi($mmsi);
            $vessel->setName($aisRecord->getName());
            $vessel->setCallsign($aisRecord->getCallsign());
            $vessel->setDimensions($aisRecord->getDimensions());
            $vessel->setVesselType($aisRecord->getType());

        }

        if ($lastJourney) {

            // check if aisRecord timestamp is newer than last journey timestamp

            if ($timestamp > $lastJourneySegment->getLastTimestamp()) {

                if ($stillInLastSegment) {

                    $lastJourney->setVoyageDestination($aisRecord->getDestination());
                    $lastJourney->setVoyageEta($aisRecord->getEta());
                    $lastJourney->setLastLocation($latLng);
                    $lastJourney->setLastTimestamp($timestamp);

                    $timeInSegment = ($timestamp - $lastJourneySegment->getFirstTimestamp());

                    if ($journeyTimedOut) {

                        // vessel is in current segment for more than 30 minutes

                        $log->debug('Vessel ' . $mmsi . ' spent too long in segment ' . $lastSegmentId . ' and is no longer in journey ' . $lastJourney->getId());

                        if (sizeof($lastJourney->getJourneySegments()) > 1) {

                            // journey started in other segment, end journey

                            $journeyEventType = JourneyEvent::EVENT_TYPE_END;

                            $event = new JourneyEvent();
                            $event->setMmsi($mmsi);
                            $event->setType($journeyEventType);
                            $event->setJourney($lastJourney);
                            $event->setVessel($vessel);
                            $event->setCurrentLocation($latLng);

                            $this->notifyJourneyEvent($event);

                            // remove journey from current journeys
                            unset($this->journeys[$mmsi]);

                            // remove vessel from current segments
                            unset($this->journeySegments[$mmsi]);

                            // add journey to ended journeys
                            $this->endedJourneys[] = $lastJourney;

                        } else {

                            // journey never left this segment, expire journey

                            $journeyEventType = JourneyEvent::EVENT_TYPE_EXPIRE;

                            $event = new JourneyEvent();
                            $event->setMmsi($mmsi);
                            $event->setType($journeyEventType);
                            $event->setJourney($lastJourney);
                            $event->setVessel($vessel);
                            $event->setCurrentLocation($latLng);

                            $this->notifyJourneyEvent($event);

                            // keep journey 'active' to detect flapping in and out of single segment

                        }

                    } else {

                        $log->debug('Vessel ' . $mmsi . ' is now on ' . $latLng->toString() . ' and still in segment ' . $lastSegmentId);

                        // update current segment data

                        $currentJourneySegment = $lastJourneySegment;

                        $currentJourneySegment->setLastLocation($latLng);

                        // lastTimestamp will be set below

                        // journey is still active

                        $currentJourney = $lastJourney;

                        // segment event will be triggered below

                    }

                } else {

                    // vessel is no longer in last known segment

                    if ($currentSegment) {

                        $log->debug('Vessel ' . $mmsi . ' is in segment ' . $currentSegment->getId() . ' and no longer in segment ' . $lastSegmentId);

                    } else {

                        $log->debug('Vessel ' . $mmsi . ' is now on ' . $latLng->toString() . ' and no longer in segment ' . $lastSegmentId);

                    }

                    $timeInSegment = ($aisRecord->getTimestamp() - $lastJourneySegment->getFirstTimestamp());
                    $timeSinceLastTimestamp = ($aisRecord->getTimestamp() - $lastJourneySegment->getLastTimestamp());

                    $endLastJourney = false;

                    if (($timeInSegment > $journeyTimeout) && (count($lastJourney->getJourneySegments()) == 1)) {

                        // vessel was in last segment for more than 30 minutes

                        // TODO only end journey if at least 2 segments

                        $endLastJourney = true;

                        if ($currentSegment) {

                            $log->debug('Vessel ' . $mmsi . ' started new journey on ' . $timestamp . ' after long time in single segment');

                        }

                    } else if ($timeSinceLastTimestamp > $journeyTimeout) {

                        // vessel was not seen for long time

                        $endLastJourney = true;

                        $log->debug('Vessel ' . $mmsi . ' started new journey on ' . $timestamp . ' after not seen since ' . $lastJourneySegment->getLastTimestamp());

                    }

                    if ($endLastJourney) {

                        $log->info('Ending journey ' . $lastJourney->getId() . ' for vessel ' . $mmsi);

                        $journeyEventType = JourneyEvent::EVENT_TYPE_END;

                        $event = new JourneyEvent();
                        $event->setMmsi($mmsi);
                        $event->setType($journeyEventType);
                        $event->setJourney($lastJourney);
                        $event->setVessel($vessel);
                        $event->setCurrentLocation($latLng);

                        $this->notifyJourneyEvent($event);

                        // remove journey from current journeys
                        unset($this->journeys[$mmsi]);

                        // remove vessel from current segments
                        unset($this->journeySegments[$mmsi]);

                        // add journey to ended journeys
                        $this->endedJourneys[] = $lastJourney;

                        $newJourney = null;

                        if ($currentSegment) {

                            // create new lastJourney

                            $newJourney = new VesselJourney();
                            $newJourney->setVessel($lastJourney->getVessel());

                            // check if last segment must be included in new journey

                            if (($timestamp - $lastJourneySegment->getLastTimestamp()) < $journeyTimeout) {

                                // include last segment in new journey

                                $log->info('Creating new journey for vessel ' . $mmsi . ' starting on ' . $lastJourneySegment->getLastTimestamp());

                                $newJourney->setId($mmsi . '-' . $lastJourneySegment->getLastTimestamp());

                                $newLastJourneySegment = new JourneySegment();
                                $newLastJourneySegment->setJourney($newJourney);
                                $newLastJourneySegment->setFirstLocation($lastJourneySegment->getLastLocation());
                                $newLastJourneySegment->setFirstTimestamp($lastJourneySegment->getLastTimestamp());
                                $newLastJourneySegment->setLastLocation($lastJourneySegment->getLastLocation());
                                $newLastJourneySegment->setLastTimestamp($lastJourneySegment->getLastTimestamp());
                                $newLastJourneySegment->setSegmentId($lastJourneySegment->getSegmentId());

                                $lastJourneySegment = $newLastJourneySegment;

                                $newJourney->setJourneySegments(array($newLastJourneySegment));

                                // trigger new journey event

                                $event = new JourneyEvent();
                                $event->setType(JourneyEvent::EVENT_TYPE_START);
                                $event->setMmsi($mmsi);
                                $event->setJourney($newJourney);
                                $event->setVessel($vessel);
                                $event->setCurrentLocation($lastJourneySegment->getLastLocation());
                                $event->setSegment($lastSegment);

                                $this->notifyJourneyEvent($event);

                                $this->journeySegments[$mmsi] = $lastJourneySegment;

                            } else {

                                // just start new journey

                                $lastSegment = null;

                                $log->info('Creating new journey for vessel ' . $mmsi . ' starting on ' . $timestamp);

                                $journeyId = $mmsi . '-' . $timestamp;
                                $newJourney->setId($journeyId);

                                // trigger new journey event

                                $event = new JourneyEvent();
                                $event->setType(JourneyEvent::EVENT_TYPE_START);
                                $event->setMmsi($mmsi);
                                $event->setJourney($newJourney);
                                $event->setVessel($vessel);
                                $event->setCurrentLocation($latLng);
                                $event->setSegment($currentSegment);

                                $this->notifyJourneyEvent($event);

                            }

                        }

                        if ($newJourney) {

                            $lastJourney = $newJourney;

                            $this->journeys[$mmsi] = $newJourney;

                        }

                    }

                    if ($isFlapping && $flapStarted) {

                        $log->debug('Trigger journey ' . $lastJourney->getId() . ' flap for vessel ' . $mmsi);

                        $journeyEventType = JourneyEvent::EVENT_TYPE_FLAP;

                        $event = new JourneyEvent();
                        $event->setMmsi($mmsi);
                        $event->setType($journeyEventType);
                        $event->setJourney($lastJourney);
                        $event->setVessel($vessel);
                        $event->setCurrentLocation($latLng);

                        $this->notifyJourneyEvent($event);

                    } else {

                        // trigger segment exit

                        $segment = $this->waterwaySegments[$lastSegmentId];

                        $vessel = new Vessel();
                        $vessel->setMmsi($mmsi);
                        $vessel->setName($aisRecord->getName());
                        $vessel->setCallsign($aisRecord->getCallsign());
                        $vessel->setDimensions($aisRecord->getDimensions());
                        $vessel->setVesselType($aisRecord->getType());

                        $lastJourney->setVoyageDestination($aisRecord->getDestination());
                        $lastJourney->setVoyageEta($aisRecord->getEta());

                        $event = new SegmentEvent();
                        $event->setType(SegmentEvent::EVENT_TYPE_EXIT);
                        $event->setMmsi($mmsi);
                        $event->setVesselSegment($lastJourneySegment);
                        $event->setVessel($vessel);
                        $event->setJourney($lastJourney);
                        $event->setPreviousLocation($lastLocation);
                        $event->setSegment($segment);
                        $event->setCurrentLocation($latLng);
                        $event->setCurrentTimestamp($timestamp);

                        $this->notifySegmentEvent($event);

                        if ($currentSegment || (count($lastJourney->getJourneySegments()) == 1)) {

                            // journey is still active

                            $currentJourney = $lastJourney;

                            $currentJourney->setLastLocation($latLng);
                            $currentJourney->setLastTimestamp($timestamp);

                            // trigger journey event

                            $event = new JourneyEvent();
                            $event->setType(JourneyEvent::EVENT_TYPE_UPDATE);
                            $event->setMmsi($mmsi);
                            $event->setJourney($currentJourney);
                            $event->setVessel($vessel);
                            $event->setCurrentLocation($latLng);
                            $event->setCurrentTimestamp($timestamp);
                            $event->setSegment($currentSegment);

                            $this->notifyJourneyEvent($event);

                        } else if (($currentSegment == null) && (count($lastJourney->getJourneySegments()) > 1)) {

                            // vessel is not in known segment and journey has at least 2 segments

                            // trigger journey end event

                            $event = new JourneyEvent();
                            $event->setType(JourneyEvent::EVENT_TYPE_END);
                            $event->setMmsi($mmsi);
                            $event->setJourney($lastJourney);
                            $event->setVessel($vessel);
                            $event->setCurrentLocation($latLng);
                            $event->setCurrentTimestamp($timestamp);

                            $this->notifyJourneyEvent($event);

                            // remove journey from current journeys
                            unset($this->journeys[$mmsi]);

                            // remove vessel from current segments
                            unset($this->journeySegments[$mmsi]);

                            // add journey to ended journeys
                            $this->endedJourneys[] = $lastJourney;

                        }

                    }

                }

            } else {

                $log->debug('Record for ' . $mmsi . ' is not newer');

                // assume journey is still active

                $currentJourney = $lastJourney;
                $currentJourneySegment = $lastJourneySegment;

            }

        }

        if ($currentSegment) {

            if ($currentJourney == null) {

                // create new journey

                // trigger journey start event

                $log->info('Creating new journey for vessel ' . $mmsi . ' starting on ' . $aisRecord->getTimestamp());

                $vessel = new Vessel();
                $vessel->setMmsi($mmsi);
                $vessel->setName($aisRecord->getName());
                $vessel->setCallsign($aisRecord->getCallsign());
                $vessel->setDimensions($aisRecord->getDimensions());
                $vessel->setVesselType($aisRecord->getType());

                $journeyId = $mmsi . '-' . $timestamp;

                $currentJourney = new VesselJourney();
                $currentJourney->setId($journeyId);
                $currentJourney->setVessel($vessel);
                $currentJourney->setFirstLocation($latLng);
                $currentJourney->setFirstTimestamp($timestamp);
                $currentJourney->setVoyageDestination($aisRecord->getDestination());
                $currentJourney->setVoyageEta($aisRecord->getEta());
                $currentJourney->setLastLocation($latLng);
                $currentJourney->setLastTimestamp($timestamp);

                $event = new JourneyEvent();
                $event->setType(JourneyEvent::EVENT_TYPE_START);
                $event->setMmsi($mmsi);
                $event->setJourney($currentJourney);
                $event->setVessel($vessel);
                $event->setCurrentLocation($latLng);
                $event->setSegment($currentSegment);

                $this->notifyJourneyEvent($event);

                $this->journeys[$mmsi] = $currentJourney;

            }

            $segmentId = $currentSegment->getId();

            $segment = $this->waterwaySegments[$segmentId];

            $vessel = new Vessel();
            $vessel->setMmsi($mmsi);
            $vessel->setName($aisRecord->getName());
            $vessel->setCallsign($aisRecord->getCallsign());
            $vessel->setDimensions($aisRecord->getDimensions());
            $vessel->setVesselType($aisRecord->getType());

            // send vessel segment enter event

            if ($currentJourneySegment == null) {

                // create new journey segment

                $currentJourneySegment = new JourneySegment();
                $currentJourneySegment->setJourney($currentJourney);
                $currentJourneySegment->setSegmentId($currentSegment->getId());
                $currentJourneySegment->setFirstLocation($latLng);
                $currentJourneySegment->setFirstTimestamp($timestamp);
                $currentJourneySegment->setLastLocation($latLng);
                $currentJourneySegment->setLastTimestamp($timestamp);

                // send segment enter event
                $journeyVesselSegments = array();

                if ($currentJourney->getJourneySegments()) {

                    $journeyVesselSegments = $currentJourney->getJourneySegments();

                }

                $journeyVesselSegments[] = $currentJourneySegment;
                $currentJourney->setJourneySegments($journeyVesselSegments);

                $event = new SegmentEvent();
                $event->setType(SegmentEvent::EVENT_TYPE_ENTER);
                $event->setMmsi($mmsi);
                $event->setVesselSegment($currentJourneySegment);
                $event->setVessel($vessel);
                $event->setSegment($segment);
                $event->setJourney($currentJourney);
                $event->setCurrentLocation($latLng);
                $event->setCurrentTimestamp($timestamp);

                $this->notifySegmentEvent($event);

            } else {

                // update current journey segment

                if ($timestamp > $currentJourneySegment->getLastTimestamp()) {

                    $currentJourneySegment->setLastLocation($latLng);
                    $currentJourneySegment->setLastTimestamp($timestamp);

                    // send segment update event

                    $event = new SegmentEvent();
                    $event->setType(SegmentEvent::EVENT_TYPE_UPDATE);
                    $event->setMmsi($mmsi);
                    $event->setVesselSegment($currentJourneySegment);
                    $event->setVessel($vessel);
                    $event->setSegment($segment);
                    $event->setJourney($currentJourney);
                    $event->setCurrentLocation($latLng);
                    $event->setCurrentTimestamp($timestamp);

                    $this->notifySegmentEvent($event);

                }

            }

            if ($currentJourneySegment) {

                $this->journeySegments[$mmsi] = $currentJourneySegment;

            }

        } else {

            // vessel is currently not in a known segment

            if ($currentJourney) {


            } else {

                // vessel is outside any known segment and has no active journey

            }

        }

        $timeStop = microtime(true);

        $timeSpent = $timeStop - $timeStart;

        if ($timeSpent > 1) {

            $log->warning('Processing record for ' . $mmsi . ' on ' . $timestamp . ' took ' . number_format($timeSpent, 2) . ' sec');

        }

    }

    /**
     * @param \DateTime $processingTime
     */
    public function expireInactiveJourneys($processingTime)
    {

        $processingTimestamp = $processingTime->getTimestamp();
        $expireBefore = $processingTimestamp - (30 * 60);

        $expireTime = new \DateTime('@' . $expireBefore);

        $expiredJourneys = $this->getExpiredJourneys($expireTime);

        if ($expiredJourneys) {

            foreach ($expiredJourneys as $expiredJourney) {

                $this->log->info('Expiring inactive journey ' . $expiredJourney->getId());

                // trigger journey end
                $mmsi = $expiredJourney->getVessel() ? $expiredJourney->getVessel()->getMmsi() : null;

                $event = new JourneyEvent();
                $event->setType(JourneyEvent::EVENT_TYPE_EXPIRE);
                $event->setMmsi($mmsi);
                $event->setJourney($expiredJourney);
                $event->setCurrentTimestamp($processingTimestamp);
                $event->setVessel($expiredJourney->getVessel());

                $this->notifyJourneyEvent($event);

                if ($mmsi) {

                    // remove journey from current journeys
                    unset($this->journeys[$mmsi]);

                    // remove vessel from current segments
                    unset($this->journeySegments[$mmsi]);

                }

                // add journey to ended journeys
                $this->endedJourneys[] = $expiredJourney;

            }

        }

    }

    /**
     * @param \DateTime $expireTime
     * @return VesselJourney[]
     */
    public function getExpiredJourneys($expireTime)
    {

        $expiredJourneys = array();

        $expireTimestamp = $expireTime->getTimestamp();

        foreach ($this->journeys as $journey) {

            $isExpired = true;

            if ($journey->getLastTimestamp() >= $expireTimestamp) {

                $isExpired = false;

            }

            if ($isExpired != false) {

                $segments = $journey->getJourneySegments();

                if ($segments) {

                    foreach ($segments as $segment) {

                        if ($segment->getLastTimestamp() >= $expireTimestamp) {

                            $isExpired = false;
                            break;

                        }

                    }

                }

            }

            if ($isExpired) {

                $expiredJourneys[] = $journey;

            }

        }

        return $expiredJourneys;

    }

    public function notifyJourneyEvent($event)
    {

        if ($event != null) {

            $eventDispatcher = $this->getEventDispatcher();

            if ($eventDispatcher) {

                $eventDispatcher->postEvent('VesselJourney.update', array($event));

            }

        }

    }

    public function notifySegmentEvent($event)
    {

        if ($event != null) {

            $eventDispatcher = $this->getEventDispatcher();

            if ($eventDispatcher) {

                $eventDispatcher->postEvent('VesselJourneySegment.update', array($event));

            }

        }

    }

    public function cleanup()
    {

        $this->log->debug('Cleaning up inactive journeys');

        // find latest event timestamp

        $lastTimestamp = null;

        foreach ($this->journeySegments as $vesselSegment) {

            if ($timestamp = $vesselSegment->getLastTimestamp()) {

                if ($timestamp > $lastTimestamp) {

                    $lastTimestamp = $timestamp;

                }

            }

        }

        if ($lastTimestamp > 0) {

            $removeVessels = array();

            foreach ($this->journeySegments as $mmsi => $vesselSegment) {

                if ($timestamp = $vesselSegment->getLastTimestamp()) {

                    $timeSinceLastTimestamp = $lastTimestamp - $timestamp;

                    if ($timeSinceLastTimestamp >= (60 * 30)) {

                        $removeVessels[$mmsi] = $mmsi;

                    }

                }

            }

            foreach ($removeVessels as $mmsi) {

                $journey = $this->journeySegments[$mmsi]->getJourney();

                $vessel = $journey->getVessel();

                $this->log->debug('Vessel ' . $mmsi . ' was not seen for too long and is no longer in journey ' . $journey->getId());

                $journeyEventType = JourneyEvent::EVENT_TYPE_EXPIRE;

                $event = new JourneyEvent();
                $event->setMmsi($mmsi);
                $event->setType($journeyEventType);
                $event->setJourney($journey);
                $event->setVessel($vessel);

                $this->notifyJourneyEvent($event);

                // remove journey from current journeys
                unset($this->journeys[$mmsi]);

                // remove vessel from current segments
                unset($this->journeySegments[$mmsi]);

            }

        }

    }

    /**
     * @return VesselJourney[]
     */
    public function getCurrentJourneys()
    {
        return $this->journeys;
    }

    /**
     * @return VesselJourney[]
     */
    public function getEndedJourneys()
    {
        return $this->endedJourneys;
    }

}
