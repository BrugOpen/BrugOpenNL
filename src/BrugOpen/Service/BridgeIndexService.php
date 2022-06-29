<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;

class BridgeIndexService
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

    /**
     * @param string $isrs
     * @return int|null
     */
    public function getBridgeIdByIsrs($isrs)
    {

    }

    /**
     * @param int $ndwLocationId
     * @return int|null
     */
    public function getBridgeIdByNdwLocationId($ndwLocationId)
    {
        
    }

    /**
     * @param int $bridgeId
     * @param string $isrs
     */
    public function addBridgeIsrs($bridgeId, $isrs)
    {

    }

    /**
     * @param int $bridgeId
     * @param int $ndwLocationId
     */
    public function updateBridgeNdwLocationId($bridgeId, $ndwLocationId)
    {

    }
}
