<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;

class RenderDataService
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
    }

    public function getRenderData()
    {

        $renderData = null;

        $requestUri = $_SERVER['REQUEST_URI'];

        $staticPageRenderData = $this->getStaticPageRenderData($requestUri);

        if ($staticPageRenderData) {

            $renderData = $staticPageRenderData;
        }

        if ($renderData == null) {

            if (substr($requestUri, 0, 1) == '/') {

                $requestUri = substr($requestUri, 1);
            }

            while (substr($requestUri, -1) == '/') {

                $requestUri = substr($requestUri, 0, -1);
            }

            $urlParts = array();

            if ($requestUri != '') {

                $urlParts = explode('/', $requestUri);
            }

            $json = file_get_contents('http://localhost:3080');
            $parsedData = json_decode($json, true);

            if (is_array($parsedData)) {

                if (count($urlParts) > 0) {

                    if (count($urlParts) == 1) {

                        // check if known bridge
                        $requestedBridge = null;

                        foreach ($parsedData['bridges'] as $bridge) {

                            if ($bridge['name'] == $urlParts[0]) {

                                $bridge['cityText'] = $this->getBridgeCityText($bridge);
                                $requestedBridge = $bridge;
                                break;
                            }
                        }

                        if ($requestedBridge) {

                            $renderData = array();
                            $renderData['contentType'] = 'bridge';
                            $renderData['browserTitle'] = $requestedBridge['title'] . ' | Brugopen.nl';
                            $renderData['pageTitle'] = $requestedBridge['title'];
                            $renderData['ogTitle'] = $requestedBridge['title'] . $requestedBridge['cityText'];
                            $renderData['ogDescription'] = 'Bekijk actuele brugopeningen van de ' . $requestedBridge['title'] . ' en ontvang notificaties van brugopeningen in je browser op BrugOpen.nl';
                            $renderData['bridge'] = $requestedBridge;

                            // get body text
                            $renderData['body'] = $this->getBridgeBodyText($requestedBridge);

                            $renderData['lastOperations'] = $this->getLastOperations($bridge['lastOperations']);

                            // collect nearby bridges

                            $nearbyBridges = array();
                            $nearbyBridgesByName = array();

                            if (array_key_exists('nearbyBridges', $requestedBridge)) {

                                foreach ($requestedBridge['nearbyBridges'] as $nearbyBridge) {

                                    $nearbyBridgesByName[$nearbyBridge[0]] = $nearbyBridge[1];
                                }
                            }

                            foreach ($nearbyBridgesByName as $name => $distance) {

                                foreach ($parsedData['bridges'] as $tmpBridge) {

                                    if ($tmpBridge['name'] == $name) {

                                        $tmpBridge['cityText'] = $this->getBridgeCityText($tmpBridge);

                                        if ($distance > 1) {

                                            $distance = number_format($distance, 1, '.', '') . 'km';
                                        } else {

                                            $distance = round($distance * 10) * 100 . 'm';
                                        }

                                        $nearbyBridge = array();
                                        $nearbyBridge['distance'] = $distance;
                                        $nearbyBridge['bridge'] = $tmpBridge;

                                        $nearbyBridges[] = $nearbyBridge;

                                        if (count($nearbyBridges) == count($nearbyBridgesByName)) {
                                            break;
                                        }
                                    }
                                }

                                if (count($nearbyBridges) == count($nearbyBridgesByName)) {
                                    break;
                                }
                            }

                            $renderData['nearbyBridges'] = $nearbyBridges;
                            $renderData['now'] = time();
                        }
                    }
                } else {

                    $renderData = array();
                    $renderData['contentType'] = 'home';
                    $renderData['browserTitle'] = 'Brugopen.nl';
                    $renderData['pageTitle'] = 'Brugopen.nl';

                    $bridges = array();

                    $now = time();

                    foreach ($parsedData['bridges'] as $bridge) {

                        $lastStartedOperation = null;
                        $nextStartingOperation = null;

                        if ($bridge['lastOperations']) {

                            foreach ($bridge['lastOperations'] as $operation) {

                                if ($operation['start'] > 0) {

                                    if ($operation['end'] > 0) {

                                        if ($operation['end'] > $now) {

                                            $durationSecs = $now - $operation['start'];
                                        } else {

                                            $durationSecs = $operation['end'] - $operation['start'];
                                        }
                                    } else {

                                        $durationSecs = $now - $operation['start'];
                                    }

                                    if ($operation['start'] > $now) {

                                        // operation not yet started
                                        if (($operation['end'] > 0) && ($operation['end'] > $operation['start'])) {

                                            $durationSecs = $operation['end'] - $operation['start'];
                                        }
                                    }

                                    $start = date('G:i', $operation['start']);

                                    if (($now - $operation['start']) > (3600 * 24)) {

                                        $start = date('d-m', $operation['start']);
                                    }

                                    $duration = '';

                                    if ($durationSecs > 0) {

                                        $durationSecs = ($durationSecs > 60) ? round($durationSecs / 60) * 60 : 60;

                                        $duration = $this->getTextualDuration($durationSecs);
                                    }

                                    $operation['duration'] = str_replace(' ', "\xc2\xa0", $duration);

                                    if ($operation['start'] > $now) {

                                        $nextStartingOperation = $operation;
                                    } else {

                                        if ($lastStartedOperation == null) {

                                            if ($operation['certainty'] == 3) {

                                                $operation['startTime'] = $start;
                                                $lastStartedOperation = $operation;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $bridge['cityText'] = $this->getBridgeCityText($bridge);
                        $bridge['lastStartedOperation'] = $lastStartedOperation;
                        $bridge['nextStartingOperation'] = $nextStartingOperation;

                        if ($lastStartedOperation) {

                            $bridgeName = $bridge['name'];

                            $bridges[$bridgeName] = $bridge;
                        }
                    }

                    ksort($bridges);

                    $openBridges = array();
                    $openingBridges = array();

                    foreach ($bridges as $bridge) {

                        if ($bridge['lastStartedOperation']) {

                            $lastStartedOperation = $bridge['lastStartedOperation'];

                            if (!($lastStartedOperation['end'] && $lastStartedOperation['end'] < $now)) {

                                $openBridges[] = $bridge;
                            }
                        }

                        if ($bridge['nextStartingOperation']) {

                            $openingBridges[] = $bridge;
                        }
                    }

                    $renderData['openBridges'] = $openBridges;
                    $renderData['openingBridges'] = $openingBridges;
                    $renderData['bridges'] = $bridges;
                    $renderData['now'] = $now;
                }
            } else {

                $renderData = array();
                $renderData['contentType'] = 'error';
                $renderData['pageTitle'] = 'Fout';
            }
        }

        if ($renderData == null) {

            $renderData = array();
            $renderData['contentType'] = '404';
            $renderData['pageTitle'] = 'Pagina niet gevonden';
        }

        $jsFile = $this->context->getAppRoot() . 'html/assets/scripts/BrugOpen.js';

        if (is_file($jsFile)) {

            $renderData['jsVersion'] = filemtime($jsFile);
        }

        return $renderData;
    }

    public function getBridgeCityText($bridge)
    {

        $cityPart = '';
        if ($bridge['city'] != '') {
            if (isset($bridge['city2']) && ($bridge['city2'] != '')) {
                $cityPart = ' tussen ' . $bridge['city'] . ' en ' . $bridge['city2'];
            } else {
                $cityPart = ' in ' . $bridge['city'];
            }
        }
        return $cityPart;
    }

    public function getBridgeBodyText($bridge)
    {

        $bodyText = '';

        $cityPart = '';
        if ($bridge['city'] != '') {
            if ($bridge['city2'] != '') {
                $cityPart = ' tussen ' . $bridge['city'] . ' en ' . $bridge['city2'];
            } else {
                $cityPart = ' in ' . $bridge['city'];
            }
        }

        $bodyText .= 'De ' . $bridge['title'] . $cityPart;

        $now = time();

        $durationSecs = 0;

        $lastOperation = null;
        $nextStartingOperation = null;
        $lastStartedOperation = null;

        if ($bridge['lastOperations']) {

            foreach ($bridge['lastOperations'] as $operation) {

                if ($operation['ended']) {

                    if ($lastStartedOperation == null) {

                        $lastStartedOperation = $operation;
                    }
                } else {

                    $nextStartingOperation = $operation;
                }
            }
        }

        $lastOperation = $nextStartingOperation ? $nextStartingOperation : $lastStartedOperation;

        $nowOpen = false;

        if ($lastOperation['end'] > 0) {

            if ($lastOperation['end'] > $now) {
                $durationSecs = $now - $lastOperation['start'];
                if ($lastOperation['start'] < $now) {
                    $nowOpen = true;
                }
            } else {
                $durationSecs = $lastOperation['end'] - $lastOperation['start'];
            }
        } else if ($lastOperation['datetime_gone'] > 0) {
            $durationSecs = $lastOperation['datetime_gone'] - $lastOperation['start'];
        } else if ($lastOperation['finished'] == 0) {
            $durationSecs = $now - $lastOperation['start'];
            $nowOpen = true;
        }

        $duration = '';

        if ($nowOpen) {

            $durationSecs = ($now - $lastOperation['start']);

            $bodyText .= ' is open sinds ';

            if (($durationSecs > 0) && ($durationSecs < (3600 * 24))) {
                $dateTimeStart = date('H:i', $lastOperation['start']);
                $bodyText .= $dateTimeStart;
            } else {
                $dateTimeStart = date('d-m', $lastOperation['start']);
                $bodyText .= $dateTimeStart;
            }
        } else {

            if ($lastOperation['start'] < $now) {
                $bodyText .= ' was het laatst open ';
            } else {
                $bodyText .= ' gaat ' . ($lastOperation['certainty'] < 3 ? 'mogelijk ' : '') . ' open ';
            }

            if ((time() - $lastOperation['start']) < (3600 * 24)) {
                $dateTimeStart = date('H:i', $lastOperation['start']);
                $bodyText .= ' om ' . $dateTimeStart;
            } else {
                $dateTimeStart = date('d-m', $lastOperation['start']);
                $bodyText .= ' op ' . $dateTimeStart;
            }
        }

        if ($durationSecs > 0) {

            $duration = str_replace(' ', "\xc2\xa0", $this->getTextualDuration($durationSecs));

            if ($nowOpen) {
                $bodyText .= ' (' . $duration . ')';
            } else {
                $bodyText .= ' gedurende ' . str_replace('dgn', 'dagen', $duration);
            }
        }

        if ($nowOpen) {

            if ($lastOperation['end'] > 0) {

                if ($lastOperation['end'] > $now) {

                    // expected end date is known and in future
                    if (($now - $lastOperation['end']) < (3600)) {
                        // end date is within an hour
                        $dateTimeEnd = date('H:i', $lastOperation['end']);
                        $bodyText .= ' en zal dicht gaan om ' . $dateTimeEnd;
                    }
                }
            }
        }

        $bodyText .= '.';

        $numOpenings = $bridge['lastWeekStats']['num'];
        $averageSecsOpen = $bridge['lastWeekStats']['avgTime'];
        $numOpeningsInOchtendSpits = $bridge['lastWeekStats']['numMorning'];
        $numOpeningsInAvondSpits = $bridge['lastWeekStats']['numEvening'];
        $numOpeningsInSpits = $numOpeningsInOchtendSpits + $numOpeningsInAvondSpits;

        if ($averageSecsOpen > 0) {

            $duration = str_replace(' ', "\xc2\xa0", $this->getTextualDuration($averageSecsOpen));
        }

        $bodyText .= ' In de afgelopen week is deze brug ' . $numOpenings . ' keer open geweest en was gemiddeld ' . $duration . ' open.';

        if ($numOpeningsInSpits > 0) {

            if ($numOpeningsInOchtendSpits == $numOpeningsInSpits) {

                $bodyText .= ' De brug is afgelopen week ' . $numOpeningsInOchtendSpits . ' keer in de ochtendspits open geweest.';
            } else if ($numOpeningsInAvondSpits == $numOpeningsInSpits) {

                $bodyText .= ' De brug is afgelopen week ' . $numOpeningsInAvondSpits . ' keer in de avondspits open geweest.';
            } else {

                $bodyText .= ' De brug is afgelopen week ' . $numOpeningsInSpits . ' keer in de spits open geweest, waarvan ' . $numOpeningsInOchtendSpits . ' keer in de ochtendspits en ' . $numOpeningsInAvondSpits . ' keer in de avondspits.';
            }
        } else {

            $bodyText .= ' De brug is afgelopen week alleen buiten de spits open geweest.';
        }

        return $bodyText;
    }

    public function getTextualDuration($secs)
    {

        if ($secs > 0) {

            if ($secs > (24 * 3600 * 1.5)) {
                $duration = round($secs / (24 * 3600)) . ' dgn';
            } else if ($secs > (24 * 3600)) {
                $duration = floor($secs / (24 * 3600)) . ' dag';
            } else if ($secs > 3600) {
                $duration = floor($secs / (3600)) . ' uur';
            } else if ($secs > 600) {
                $duration = round($secs / 60) . ' min';
            } else if ($secs > 60) {
                $duration = str_replace(',0', '', number_format((round($secs / 30) / 2), 1, ',', '')) . ' min';
            } else {
                $duration = '1 min';
            }
        }

        return $duration;
    }

    public function getLastOperations($operations)
    {

        $lastOperations = array();

        $allStartedToday = true;

        $now = time();

        foreach ($operations as $operation) {

            $dateTimeStart = $operation['start'];

            if ($dateTimeStart > $now) {
                continue;
            }

            $startedToday = false;
            if (($now - $dateTimeStart) < (3600 * 24)) {
                $startedToday = true;
            }

            if (!$startedToday) {
                $allStartedToday = false;
                break;
            }
        }

        foreach ($operations as $operation) {

            $from = '';
            $until = '';
            $duration = '';

            $nowOpen = false;

            $dateTimeStart = $operation['start'];

            if ($dateTimeStart > $now) {
                continue;
            }

            $startedToday = false;
            if ((time() - $dateTimeStart) < (3600 * 24)) {
                $startedToday = true;
            }

            $until = '';

            if ($operation['end'] > 0) {

                if ($operation['end'] > $now) {

                    if ($operation['start'] < $now) {
                        $nowOpen = true;
                    }
                }

                $durationSecs = $operation['end'] - $operation['start'];
                $dateTimeEnd = $operation['end'];

                if ($startedToday) {
                    $until = date('H:i', $dateTimeEnd);
                } else {
                    $until = date('H:i', $dateTimeEnd);
                }
            } else {

                $durationSecs = $now - $operation['start'];
                $nowOpen = true;
            }

            if ($allStartedToday) {
                $from = date('H:i', $dateTimeStart);
            } else {
                $from = date('d-m H:i', $dateTimeStart);
            }

            if ($durationSecs > 0) {

                $durationSecs = ($durationSecs > 60) ? round($durationSecs / 60) * 60 : 60;

                $duration = $this->getTextualDuration($durationSecs);
            }

            $info = '';

            $vesselTypes = array();
            if (isset($operation['vesselTypes']) && (count($operation['vesselTypes']) > 0)) {

                foreach ($operation['vesselTypes'] as $vesselType) {
                    if ($vesselType != '') {
                        $vesselTypes[] = strtolower($vesselType);
                    }
                }
            }
            if (count($vesselTypes) > 0) {
                $info = ' ' . implode(', ', $vesselTypes);
            } else {
                $info = '?';
            }

            $operation['from'] = $from;
            $operation['until'] = $until;
            $operation['duration'] = $duration;
            $operation['info'] = $info;
            $operation['nowOpen'] = $nowOpen;

            $lastOperations[] = $operation;

            if (count($lastOperations) == 10) {
                break;
            }
        }

        return $lastOperations;
    }

    /**
     *
     * @param string $requestUri
     * @return array|null
     */
    public function getStaticPageRenderData($requestUri)
    {
        return null;
    }
}
