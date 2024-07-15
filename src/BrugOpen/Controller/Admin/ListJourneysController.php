<?php
namespace BrugOpen\Controller\Admin;

use BrugOpen\Core\Context;
use BrugOpen\Core\ContextAware;
use BrugOpen\Tracking\Service\JourneyDataService;
use BrugOpen\Tracking\Service\JourneyDataStore;

class ListJourneysController implements ContextAware
{

    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    public function execute()
    {

        $siteRoot = $this->context->getAppRoot();

        $templatesDir = $siteRoot . 'templates' . DIRECTORY_SEPARATOR;

        $loader = new \Twig_Loader_Filesystem($templatesDir);

        $cacheDir = $siteRoot . 'cache' . DIRECTORY_SEPARATOR . 'twig';

        $twig = new \Twig_Environment($loader, [
            'cache' => $cacheDir,
            'auto_reload' => true
        ]);

        date_default_timezone_set('Europe/Amsterdam');

        $template = $twig->load('admin/start.twig');

        $date = '2023-01-01';

        if (array_key_exists('date', $_REQUEST)) {

            $date = $_REQUEST['date'];

        }

        $state = 'finished';

        if (array_key_exists('state', $_REQUEST)) {

            $state = $_REQUEST['state'];

        }

        $dateTime = new \DateTime($date . ' 00:00:00');

        $onlyMmsi = null;

        if (array_key_exists('mmsi', $_REQUEST)) {

            $onlyMmsi = $_REQUEST['mmsi'];

        }

        $journeyDataStore = new JourneyDataStore();
        $journeyDataStore->initialize($this->context);

        $journeyFiles = array();

        if (($state == 'finished') || ($state == 'both')) {

            $journeyFiles['finished'] = $journeyDataStore->getFinishedJourneyFile($dateTime);

        }

        if (($state == 'active') || ($state == 'both')) {

            $journeyFiles['active'] = $journeyDataStore->getActiveJourneyFile($dateTime);

        }

        foreach ($journeyFiles as $tmpState => $journeyFile) {

            if (is_file($journeyFile)) {

                $loadedJourneys = $journeyDataStore->loadJourneys($journeyFile);

                if ($loadedJourneys) {

                    $journeyDataService = new JourneyDataService();

                    foreach ($loadedJourneys as $loadedJourney) {

                        if ($onlyMmsi != null) {

                            if (!($loadedJourney->getVessel() && ($loadedJourney->getVessel()->getMmsi() == $onlyMmsi))) {
                                continue;
                            }

                        }

                        $journey = $journeyDataService->toJsonData($loadedJourney);

                        // calculate time

                        $segments = $loadedJourney->getJourneySegments();
                        $startTime = $segments[0]->getFirstTimestamp();
                        $endTime = $segments[count($segments) - 1]->getLastTimestamp();
                        $duration = $endTime - $startTime;

                        $journey['duration'] = $duration;

                        // calculate distance

                        $distance = 0;

                        $lastLocation = null;

                        foreach ($segments as $segment) {

                            if ($lastLocation) {

                                $connectingDistance = $lastLocation->getDistance($segment->getFirstLocation());

                                if ($connectingDistance) {

                                    $distance += $connectingDistance;

                                }

                            }

                            $segmentDistance = $segment->getFirstLocation()->getDistance($segment->getLastLocation());

                            if ($segmentDistance > 0) {

                                $distance += $segmentDistance;

                            }

                            $lastLocation = $segment->getLastLocation();

                        }

                        if ($distance > 1000) {

                            // var_dump($loadedJourney, $distance);exit;

                        }

                        $journey['distance'] = $distance;

                        $journey['state'] = $tmpState;

                        $journeyData[] = $journey;

                    }

                }

            }

        }

        echo $template->render(['contentType' => 'list_journeys', 'date' => $date, 'mmsi' => $onlyMmsi, 'state' => $state, 'journeys' => $journeyData]);

    }

}