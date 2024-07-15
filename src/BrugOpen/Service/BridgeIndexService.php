<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Db\Service\DatabaseTableManager;
use BrugOpen\Db\Service\TableManager;

class BridgeIndexService
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     * @var TableManager
     */
    private $tableManager;

    /**
     *
     * @param Context $context
     */
    public function initialize($context)
    {
        $this->context = $context;
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
     * @param string $isrs
     * @return int|null
     */
    public function getBridgeIdByIsrs($isrs)
    {

        $bridgeId = null;

        if ($isrs) {

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                $criteria = array();
                $criteria['isrs_code'] = $isrs;

                $bridge = $tableManager->findRecord('bo_bridge_isrs', $criteria);

                if ($bridge) {

                    $bridgeId = $bridge['bridge_id'];

                }

            }

        }

        return $bridgeId;
    }

    /**
     * @param int $ndwLocationId
     * @return int|null
     */
    public function getBridgeIdByNdwLocationId($ndwLocationId)
    {
        $bridgeId = null;

        if ($ndwLocationId) {

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                $criteria = array();
                $criteria['ndw_id'] = $ndwLocationId;

                $bridge = $tableManager->findRecord('bo_bridge', $criteria);

                if ($bridge) {

                    $bridgeId = $bridge['id'];

                }

            }

        }

        return $bridgeId;

    }

    /**
     * @param int $bridgeId
     * @param string $isrs
     */
    public function addBridgeIsrs($bridgeId, $isrs)
    {

        if ($bridgeId && $isrs) {

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                $values = array();
                $values['isrs_code'] = $isrs;
                $values['bridge_id'] = $bridgeId;

                $now = date('Y-m-d H:i:s');
                $values['datetime_created'] = $now;
                $values['datetime_modified'] = $now;

                $tableManager->insertRecord('bo_bridge_isrs', $values);

            }

        }

    }

    /**
     * @param int $bridgeId
     * @param int $ndwLocationId
     */
    public function updateBridgeNdwLocationId($bridgeId, $ndwLocationId)
    {

    }
}
