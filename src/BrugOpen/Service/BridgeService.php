<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Model\Bridge;
use BrugOpen\Model\LatLng;

class BridgeService
{

    /**
     *
     * @var Context
     */
    private $context;

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
     * @return Bridge[]
     */
    public function getAllBridges()
    {

        if (!is_array($this->allBridges)) {

            $allBridges = array();

            $sql = 'SELECT * FROM bo_bridge';

            if ($results = $this->context->getDataStore()->executeQuery($sql)) {

                while ($row = $results->fetch_assoc()) {

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

}
