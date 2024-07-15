<?php

namespace BrugOpen\Event;

interface VesselSegmentEventListener
{

    public function handleVesselJourneyEvent(VesselJourneyEvent $event);

    public function handleVesselSegmentEvent(VesselSegmentEvent $event);

}
