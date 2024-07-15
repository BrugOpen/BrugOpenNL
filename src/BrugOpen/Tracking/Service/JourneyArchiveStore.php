<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Core\Context;
use BrugOpen\Tracking\Model\VesselJourney;

class JourneyArchiveStore
{

    /**
     * @var Context
     */
    private $context;

    /**
     * @var \MongoDB\Collection
     */
    private $mongoJourneyCollection;

    /**
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
    }

    /**
     * @return \MongoDB\Collection
     */
    public function getMongoJourneyCollection()
    {

        if ($this->mongoJourneyCollection == null) {

            $context = $this->context;

            if ($context) {

                $mongoDsn = $context->getConfig()->getParam('mongo.dsn');

                $client = new \MongoDB\Client($mongoDsn);

                /**
                 * @var MongoDB\Collection
                 */
                $mongoJourneyCollection = $client->selectCollection('brugopen', 'journeys');

                $this->mongoJourneyCollection = $mongoJourneyCollection;
            }
        }

        return $this->mongoJourneyCollection;
    }

    /**
     * @param string $mmsi
     * @return VesselJourney[]
     */
    public function loadVesselJourneys($mmsi)
    {

        $journeys = array();

        $journeyDocuments = array();

        if ($mmsi) {

            $collection = $this->getMongoJourneyCollection();

            if ($collection) {

                $journeyDocuments = $collection->find(array('mmsi' => $mmsi));

                $journeyDataService = new JourneyDataService();

                foreach ($journeyDocuments as $journeyDocument) {

                    $journeyJson = json_encode($journeyDocument);

                    $jsonData = null;

                    if ($journeyJson) {
                        $jsonData = json_decode($journeyJson, true);
                    }

                    $journey = null;

                    if ($jsonData) {

                        if (array_key_exists('_id', $jsonData) && !array_key_exists('id', $jsonData)) {
                            $jsonData['id'] = $jsonData['_id'];
                        }

                        $journey = $journeyDataService->parseJourney($jsonData);
                    }

                    if ($journey) {
                        $journeys[] = $journey;
                    }
                }
            }
        }

        return $journeys;
    }
}
