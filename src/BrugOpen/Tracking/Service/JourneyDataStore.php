<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Core\Context;
use BrugOpen\Tracking\Model\VesselJourney;

class JourneyDataStore
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var Log
     */
    private $log;

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
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
    }

    /**
     * Loads active VesselJourney objects on given date
     * @return VesselJourney[]
     */
    public function loadActiveJourneys($dateTime)
    {

        $journeysFile = $this->getActiveJourneyFile($dateTime);

        $journeys = $this->loadJourneys($journeysFile);

        return $journeys;
    }

    /**
     * Loads VesselJourney objects from jsonl file
     * @return VesselJourney[]
     */
    public function loadJourneys($journeysFile)
    {

        $journeys = array();

        if (is_file($journeysFile)) {

            $fp = fopen($journeysFile, 'r');

            if ($fp) {

                $dataService = new JourneyDataService();

                while ($line = fgets($fp)) {

                    $json = trim($line);

                    if ($json) {

                        $jsonData = json_decode($json, true);

                        $journey = $dataService->parseJourney($jsonData);

                        if ($journey) {

                            $journeys[] = $journey;
                        }
                    }
                }
            }
        }

        return $journeys;
    }

    /**
     *
     * @param VesselJourney $journey
     */
    public function storeJourney($journey)
    {

        $dataStore = $this->context->getDataStore();

        if ($journey->getId()) {

            $lastLocation = $journey->getLastLocation();

            if ($lastLocation && $lastLocation->getTime()) {

                $values = array();

                $values['last_timestamp'] = new \DateTime("@" . $lastLocation->getTime());
                $values['last_location'] = $lastLocation->getLat() . ',' . $lastLocation->getLon();

                $keys = array();
                $keys['id'] = $journey->getId();

                $dataStore->updateTable('bo_vessel_journey', $keys, $values);
            } else {

                echo 'cannot update journey';
                var_dump($journey);
                exit;
            }
        } else {

            $vessel = $journey->getVessel();

            $values = array();

            $values['mmsi'] = $vessel->getMmsi();

            $firstLocation = $journey->getFirstLocation();

            $values['first_timestamp'] = new \DateTime("@" . $firstLocation->getTime());
            $values['first_location'] = $firstLocation->getLat() . ',' . $firstLocation->getLon();

            // do not set last_timestamp and last_location

            $values['vessel_name'] = $vessel->getName();
            $values['vessel_callsign'] = $vessel->getCallsign();
            $values['vessel_dimensions'] = $vessel->getDimensions();
            $values['vessel_type'] = $vessel->getVesselType();

            $values['voyage_destination'] = $journey->getVoyageDestination();
            $values['voyage_eta'] = $journey->getVoyageEta();

            $journeyId = $dataStore->insertRecord('bo_vessel_journey', $values);

            if ($journeyId) {

                $journey->setId($journeyId);
            }
        }
    }

    public function getJourneyDataDir()
    {

        $journeysDir = $this->context->getAppRoot() . 'data/journeys/';

        return $journeysDir;
    }

    /**
     * Write journeys to jsonl file
     * @param string $fileName
     * @param VesselJourney[] $journeys
     */
    public function storeJourneys($fileName, $journeys)
    {

        $dataService = new JourneyDataService();

        $dirName = dirname($fileName);

        if (!is_dir($dirName)) {

            mkdir($dirName, 0755, true);
        }

        if (is_dir($dirName)) {

            $fp = fopen($fileName, 'w');

            if ($fp) {

                $logger = $this->getLog();

                if ($logger) {

                    $logger->info("Writing " . count($journeys) . ' journey' . (count($journeys) != 1 ? 's' : '') . ' to ' . basename($fileName));
                }

                foreach ($journeys as $journey) {

                    $journeyData = $dataService->toJsonData($journey);
                    $jouneyJson = json_encode($journeyData);

                    fwrite($fp, $jouneyJson);
                    fwrite($fp, "\n");
                }

                fclose($fp);
            }
        }
    }

    public function storeActiveJourneys($dateTime, $journeys)
    {

        $journeysFile = $this->getActiveJourneyFile($dateTime);
        $this->storeJourneys($journeysFile, $journeys);
    }

    /**
     * @param \DateTime $dateTime
     * @param VesselJourney[] $journeys
     */
    public function storeFinishedJourneys($dateTime, $journeys)
    {

        $journeysFile = $this->getFinishedJourneyFile($dateTime);

        $storeJourneys = array();

        if (is_file($journeysFile)) {

            // load existing journeys
            $existingJourneys = $this->loadJourneys($journeysFile);

            if ($existingJourneys) {

                foreach ($existingJourneys as $existingJourney) {

                    $journeyUpdated = false;

                    foreach ($journeys as $journey) {

                        if ($journey->getId() == $existingJourney->getId()) {

                            $journeyUpdated = true;
                            break;
                        }
                    }

                    if (!$journeyUpdated) {
                        $storeJourneys[] = $existingJourney;
                    }
                }
            }

            foreach ($journeys as $journey) {
                $storeJourneys[] = $journey;
            }
        } else {

            $storeJourneys = $journeys;
        }

        if ($storeJourneys) {
            $this->storeJourneys($journeysFile, $storeJourneys);
        }
    }

    public function getActiveJourneyFile($dateTime)
    {
        $journeyDir = $this->getJourneyDataDir();
        $fileName = $journeyDir . $dateTime->format('Y-m-d') . '-active.jsonl';
        return $fileName;
    }

    public function getFinishedJourneyFile($dateTime)
    {
        $journeyDir = $this->getJourneyDataDir();
        $fileName = $journeyDir . $dateTime->format('Y-m-d') . '.jsonl';
        return $fileName;
    }
}
