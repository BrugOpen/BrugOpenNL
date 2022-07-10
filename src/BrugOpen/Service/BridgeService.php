<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Db\Service\DatabaseTableManager;
use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Model\Bridge;

class BridgeService
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var TableManager
     */
    private $tableManager;

    /**
     *
     * @var Bridge[]
     */
    private $allBridges;

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
     * @return \BrugOpen\Db\Service\TableManager
     */
    public function getTableManager()
    {
        if ($this->tableManager == null) {

            $connectionManager = $this->context->getDatabaseConnectionManager();
            $connection = $connectionManager->getConnection();
            $tableManager = new DatabaseTableManager($connection);

            $this->tableManager = $tableManager;
        }

        return $this->tableManager;
    }

    /**
     *
     * @param \BrugOpen\Db\Service\TableManager $tableManager
     */
    public function setTableManager($tableManager)
    {
        $this->tableManager = $tableManager;
    }

    /**
     * @return Bridge[]
     */
    public function getAllBridges()
    {

        if (!is_array($this->allBridges)) {

            $allBridges = array();

            $sql = 'SELECT * FROM bo_bridge';

            $records = array();

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                $records = $tableManager->findRecords('bo_bridge');

            }

            if ($records) {

                foreach ($records as $row) {

                    $bridgeId = (int)$row['id'];

                    $bridge = new Bridge();
                    $bridge->setId($bridgeId);
                    $bridge->setName($row['name']);
                    $bridge->setTitle($row['title']);
                    $bridge->setCity($row['city']);
                    $bridge->setCity2($row['city2']);

                    $lat = $row['ndw_lat'];
                    $lng = $row['ndw_lng'];

                    if (($lat != '') && ($lng != '')) {

                        $latLng = new LatLng((float)$lat, (float)$lng);
                        $bridge->setLatLng($latLng);

                    }

                    $allBridges[$bridgeId] = $bridge;

                }

            }

            $this->allBridges = $allBridges;

        }

        return $this->allBridges;

    }

    /**
     * @param int $ndwId
     * @param string $isrs
     * @param LatLng $latLng
     * @return Bridge
     */
    public function insertBridgeFromNdwData($ndwId, $isrs, $latLng)
    {

        $bridge = null;
        
        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $record = array();

            if ($ndwId) {

                $record['ndw_id'] = $ndwId;

            }

            if ($isrs) {

                $record['isrs_code'] = $isrs;

            }

            if ($latLng) {

                $record['ndw_lat'] = $latLng->getLat();
                $record['ndw_lng'] = $latLng->getLng();

            }

            $insertedId = $tableManager->insertRecord('bo_bridge', $record);

            if ($insertedId) {

                // create bridge

                $bridge = new Bridge();
                $bridge->setId((int)$insertedId);

                if ($isrs) {

                    $bridge->setIsrsCode($isrs);

                }

                if ($latLng) {

                    $bridge->setLatLng($latLng);

                }

            }

        }

        return $bridge;

    }

}
