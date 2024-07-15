<?php

namespace BrugOpen\Rws\Service;

use BrugOpen\Core\Context;
use BrugOpen\Db\Service\TableManager;

class BridgeDataService
{

    /**
     * @var Context
     */
    private $context;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @var TableManager
     */
    private $tableManager;

    /**
     * @var int
     */
    private $currentGeoGeneration;

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
     * @return TableManager
     */
    public function getTableManager()
    {
        if ($this->tableManager == null) {

            if ($this->context != null) {

                $this->tableManager = $this->context->getService('BrugOpen.TableManager');
            }
        }

        return $this->tableManager;
    }

    /**
     * @param TableManager $tableManager
     */
    public function setTableManager($tableManager)
    {
        $this->tableManager = $tableManager;
    }

    /**
     * @return int
     */
    public function getCurrentGeoGeneration()
    {

        if ($this->currentGeoGeneration == null) {

            $currentGeoGeneration = null;

            $cacheFile = $this->getGenerationCacheFile();

            if (!is_file($cacheFile)) {
                $this->updateCurrentGeoGenerationIfNeeded();
            }

            if (is_file($cacheFile)) {

                $json = file_get_contents($cacheFile);

                if ($json) {

                    $parsedJson = json_decode($json, true);

                    if ($parsedJson) {

                        $currentGeoGeneration = $parsedJson['GeoGeneration'];
                    }
                }
            }

            $this->currentGeoGeneration = $currentGeoGeneration;
        }

        return $this->currentGeoGeneration;
    }

    public function updateCurrentGeoGenerationIfNeeded()
    {
        $rwsClient = new FairwayServiceClient();

        $cacheFileNeedsUpdate = true;

        $currentGeoGeneration = $rwsClient->getCurrentGeoGeneration();
        $cachedGeneration = $this->getCurrentGeoGeneration();

        if ($currentGeoGeneration) {

            if ($currentGeoGeneration['GeoGeneration'] == $cachedGeneration) {
                $cacheFileNeedsUpdate = false;
            }
        }

        if ($cacheFileNeedsUpdate) {
            $this->updateCurrentGeoGenerationCache($currentGeoGeneration);
            $this->currentGeoGeneration = $currentGeoGeneration['GeoGeneration'];
        }
    }

    public function updateCurrentGeoGenerationCache($currentGeoGeneration)
    {

        $logger = $this->getLog();

        if ($currentGeoGeneration) {

            $cacheFile = $this->getGenerationCacheFile();
            $cacheDir = dirname($cacheFile);

            if (!is_dir($cacheDir)) {

                mkdir($cacheDir, 755, true);
            }

            if (is_dir($cacheDir)) {

                $logger->info('Updating geo generation cache file');
                $json = json_encode($currentGeoGeneration);
                file_put_contents($cacheFile, $json);
            } else {

                $logger->error('Could not create cache dir for geo generation cache file');
            }
        } else {

            $logger->error('Could not get current geo generation');
        }
    }

    public function updateBridgeIndex()
    {

        $logger = $this->getLog();

        $logger->info("Updating bridge index started");

        $tableManager = $this->getTableManager();

        $rwsClient = new FairwayServiceClient();

        $batchSize = 100;

        $geoGeneration = $this->getCurrentGeoGeneration();

        $offset = 0;

        do {

            break;
            $batch = array();

            $logger->info("Loading " . $batchSize . ' bridges starting from ' . $offset);

            $response = $rwsClient->listBridges($geoGeneration, $batchSize, $offset);

            if ($response) {

                $resultGeneration = $response['GeoGeneration'];

                if ($resultGeneration != $geoGeneration) {
                    // generation changed while iterating, start over
                    $this->updateCurrentGeoGenerationIfNeeded();
                    $geoGeneration = $resultGeneration;
                    $offset = 0;
                    continue;
                }

                $items = $response['Result'];

                if ($items) {

                    foreach ($items as $bridge) {

                        if (!$bridge['CanOpen']) {
                            continue;
                        }

                        $bridgeId = $bridge['Id'];
                        $bridgeGeoGeneration = $bridge['GeoGeneration'];

                        if ($bridgeId && $bridgeGeoGeneration) {

                            $cacheFile = $this->getObjectCacheFile('bridge', $bridgeGeoGeneration, $bridgeId);

                            if (!is_file($cacheFile)) {
                                $logger->info('Updating cache file for bridge ' . $bridgeId);
                                $this->updateObjectCache('bridge', $bridgeGeoGeneration, $bridgeId, $bridge);
                            }

                            // update index in database if needed
                            $criteria = array();
                            $criteria['geo_generation'] = $geoGeneration;
                            $criteria['bridge_id'] = $bridgeId;

                            $record = $tableManager->findRecord('bo_rws_bridge', $criteria);

                            if ($record) {

                                // record exists in index

                            } else {

                                // try to find by any generation

                                $criteria = array();
                                $criteria['bridge_id'] = $bridgeId;

                                $record = $tableManager->findRecord('bo_rws_bridge', $criteria);

                                if ($record) {

                                    // update generation

                                    $keys = array();
                                    $keys['id'] = $record['id'];

                                    $values = array();
                                    $values['geo_generation'] = $geoGeneration;
                                    $values['bridge_generation'] = $bridgeGeoGeneration;
                                    $values['bridge_name'] = $bridge['Name'];
                                    $values['city'] = $bridge['City'];
                                    $values['isrs'] = null;
                                    $values['datetime_modified'] = date('Y-m-d H:i:s');

                                    $tableManager->updateRecords('bo_rws_bridge', $values, $keys);
                                } else {

                                    // insert index
                                    $values = array();
                                    $values['geo_generation'] = $geoGeneration;
                                    $values['bridge_id'] = $bridgeId;
                                    $values['bridge_generation'] = $bridgeGeoGeneration;
                                    $values['bridge_name'] = $bridge['Name'];
                                    $values['city'] = $bridge['City'];
                                    $values['datetime_created'] = date('Y-m-d H:i:s');
                                    $values['datetime_modified'] = date('Y-m-d H:i:s');

                                    $tableManager->insertRecord('bo_rws_bridge', $values);
                                }
                            }
                        }
                    }

                    $batch = $items;
                }
            }

            $offset += $batchSize;
        } while (count($batch) > 0);


        // update isrs where empty

        $offset = 0;

        do {

            break;

            $batch = array();

            $logger->info("Checking " . $batchSize . ' bridges ISRS starting from ' . $offset);

            $orders = array('id');

            $records = $tableManager->findRecords('bo_rws_bridge', null, null, $orders, $batchSize, $offset);

            if ($records) {

                $batch = $records;
            }

            foreach ($batch as $bridge) {

                $bridgeId = $bridge['bridge_id'];
                $bridgeGeoGeneration = $bridge['bridge_generation'];
                $isrs = $bridge['isrs'];

                if (!$isrs) {

                    $bridgeObject = $this->getObject('bridge', $bridgeId);

                    if ($bridgeObject) {

                        $isrsId = $bridgeObject['IsrsId'];

                        $isrsObject = $this->getObject('isrs', $isrsId);

                        if ($isrsObject) {

                            $isrs = $isrsObject['Code'];
                        }
                    }

                    if ($isrs) {

                        $logger->info('Found ISRS ' . $isrs . ' for bridge ' . $bridgeId);

                        $keys = array();
                        $keys['id'] = $bridge['id'];
                        $values['isrs'] = $isrs;
                        $values['datetime_modified'] = date('Y-m-d H:i:s');

                        $tableManager->updateRecords('bo_rws_bridge', $values, $keys);
                    }
                }
            }

            $offset += $batchSize;
        } while (count($batch) > 0);

        // update clearance where empty

        $offset = 0;

        do {

            $batch = array();

            $logger->info("Checking " . $batchSize . ' bridges clearance starting from ' . $offset);

            $orders = array('id');

            $records = $tableManager->findRecords('bo_rws_bridge', null, null, $orders, $batchSize, $offset);

            if ($records) {

                $batch = $records;
            }

            foreach ($batch as $bridge) {

                $bridgeId = $bridge['bridge_id'];
                $bridgeGeoGeneration = $bridge['bridge_generation'];
                $clearance = $bridge['clearance'];

                if (!$clearance) {

                    $openingsRelationObject = $rwsClient->getObjectRelation($geoGeneration, 'bridge', $bridgeId, 'opening');

                    if ($openingsRelationObject) {

                        $openingIds = array();

                        if (array_key_exists('Result', $openingsRelationObject) && $openingsRelationObject['Result']) {

                            $openingIds = $openingsRelationObject['Result'];
                        }

                        $maxClearance = null;

                        foreach ($openingIds as $openingId) {

                            $openingObject = $rwsClient->getObject($geoGeneration, 'opening', $openingId);

                            $clearance = null;

                            if ($openingObject) {

                                $clearance = $openingObject['ClearanceHeightClosed'];
                            }

                            if ($clearance) {

                                if (($maxClearance == null) || ($clearance > $maxClearance)) {

                                    $maxClearance = $clearance;
                                }
                            }
                        }

                        if ($maxClearance !== null) {

                            // update bridge
                            $logger->info('Found clearance ' . $maxClearance . ' for bridge ' . $bridgeId);

                            $keys = array();
                            $keys['id'] = $bridge['id'];
                            $values['clearance'] = $maxClearance;
                            $values['datetime_modified'] = date('Y-m-d H:i:s');

                            $tableManager->updateRecords('bo_rws_bridge', $values, $keys);
                        }
                    }

                    $bridgeObject = $this->getObject('bridge', $bridgeId);

                    if ($bridgeObject) {

                        $isrsId = $bridgeObject['IsrsId'];

                        $isrsObject = $this->getObject('isrs', $isrsId);

                        if ($isrsObject) {

                            $isrs = $isrsObject['Code'];
                        }
                    }

                    if ($isrs) {

                        $logger->info('Found ISRS ' . $isrs . ' for bridge ' . $bridgeId);

                        $keys = array();
                        $keys['id'] = $bridge['id'];
                        $values['isrs'] = $isrs;
                        $values['datetime_modified'] = date('Y-m-d H:i:s');

                        $tableManager->updateRecords('bo_rws_bridge', $values, $keys);
                    }
                }
            }

            $offset += $batchSize;
        } while (count($batch) > 0);

        // TODO remove records with older geo_generation

        $logger->info("Updating bridge index finished");
    }

    public function getCacheDir()
    {
        $cacheDir = $this->context->getAppRoot() . 'cache' . DIRECTORY_SEPARATOR . 'rws' . DIRECTORY_SEPARATOR;
        return $cacheDir;
    }

    public function updateObjectCache($geoType, $geoGeneration, $id, $data)
    {
        $cacheFile = $this->getObjectCacheFile($geoType, $geoGeneration, $id);
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        if (is_dir($cacheDir)) {
            $json = json_encode($data);
            file_put_contents($cacheFile, $json);
        }
    }

    public function getObject($geoType, $id)
    {
        $object = null;

        $geoGeneration = $this->getCurrentGeoGeneration();

        $rwsClient = new FairwayServiceClient();

        $tableManager = $this->getTableManager();

        // determine objectGeoGenration

        $objectGeoGeneration = null;

        $criteria = array();
        $criteria['geo_generation'] = $geoGeneration;
        $criteria['object_type'] = $geoType;
        $criteria['object_id'] = $id;

        $record = $tableManager->findRecord('bo_rws_object', $criteria);

        if ($record) {

            $objectGeoGeneration = $record['object_generation'];
        } else {

            // find object by any geo generation

            $criteria = array();
            $criteria['object_type'] = $geoType;
            $criteria['object_id'] = $id;

            $record = $tableManager->findRecord('bo_rws_object', $criteria);

            if ($record) {

                // update geo generation
                $keys = array();
                $keys['id'] = $record['id'];

                $values = array();
                $values['geo_generation'] = $geoGeneration;
                $values['datetime_modified'] = date('Y-m-d H:i:s');

                $tableManager->updateRecords('bo_rws_object', $values, $keys);
            } else {

                // download object from current geo generation

                $object = $rwsClient->getObject($geoGeneration, $geoType, $id);

                if ($object) {

                    $objectGeoGeneration = $object['GeoGeneration'];

                    if ($objectGeoGeneration) {

                        // create index
                        $values = array();
                        $values['geo_generation'] = $geoGeneration;
                        $values['object_type'] = $geoType;
                        $values['object_id'] = $id;
                        $values['object_generation'] = $objectGeoGeneration;
                        $values['datetime_created'] = date('Y-m-d H:i:s');
                        $values['datetime_modified'] = date('Y-m-d H:i:s');

                        $tableManager->insertRecord('bo_rws_object', $values);

                        $cacheFile = $this->getObjectCacheFile($geoType, $objectGeoGeneration, $id);

                        if (!is_file($cacheFile)) {

                            // update cache immediately
                            $this->updateObjectCache($geoType, $objectGeoGeneration, $id, $object);
                        }
                    }
                }
            }
        }

        if (!$object) {

            if ($objectGeoGeneration) {

                $cacheFile = $this->getObjectCacheFile($geoType, $objectGeoGeneration, $id);

                if (!is_file($cacheFile)) {

                    $object = $rwsClient->getObject($geoGeneration, $geoType, $id);

                    if ($object) {
                        $this->updateObjectCache($geoType, $objectGeoGeneration, $id, $object);
                    }
                }
            }
        }

        if (!$object) {
            if ($cacheFile) {
                if (is_file($cacheFile)) {
                    $json = file_get_contents($cacheFile);
                    if ($json) {
                        $object = json_decode($json, true);
                    }
                }
            }
        }

        return $object;
    }

    /**
     * @param string $geoType
     * @param int $geoGeneration
     * @param int $id
     * @return string
     */
    public function getObjectCacheFile($geoType, $geoGeneration, $id)
    {
        $cacheDir = $this->getCacheDir();

        $cacheFile = $cacheDir . $geoType . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $geoGeneration . '.json';
        return $cacheFile;
    }

    /**
     * @return string
     */
    public function getGenerationCacheFile()
    {
        $cacheDir = $this->getCacheDir();
        $cacheFile = $cacheDir . 'generation.json';
        return $cacheFile;
    }
}
