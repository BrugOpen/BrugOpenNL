<?php

namespace BrugOpen\Projection\Model;

use BrugOpen\Tracking\Model\JourneySegment;

class ProjectedJourney
{

    /**
     * @var string
     */
    private $mmsi;

    /**
     * @var \DateTime
     */
    private $datetimeProjection;

    /**
     * @var JourneySegment[]
     */
    private $lastActualJourneySegments;

    /**
     * @var float
     */
    private $probability;

    /**
     * @var ProjectedJourneySegment[]
     */
    private $journeySegments;

    /**
     * @var ProjectedBridgePassage[]
     */
    private $bridgePassages;
}
