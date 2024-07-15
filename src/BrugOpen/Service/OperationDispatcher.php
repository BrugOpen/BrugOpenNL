<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;
use BrugOpen\Db\Service\TableManager;

class OperationDispatcher
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
     * @var string[]
     */
    private $endpointUrls;

    /**
     * @var TableManager
     */
    private $tableManager;

    public function __construct($context)
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
     *
     * @param \Psr\Log\LoggerInterface $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    /**
     * @return \BrugOpen\Db\Service\TableManager
     */
    public function getTableManager()
    {

        if ($this->tableManager == null) {

            if ($this->context) {

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
     * @return string[]
     */
    public function getEndpointUrls()
    {

        if ($this->endpointUrls == null) {

            $endpointUrls = array();

            if ($this->context) {

                $config = $this->context->getConfig();

                if ($config) {

                    $configParam = $config->getParam('operationdispatcher.urls');

                    if ($configParam) {

                        $configParam = str_replace(',', ' ', $configParam);

                        $paramParts = explode(' ', $configParam);

                        foreach ($paramParts as $paramPart) {

                            $trimmedPart = trim($paramPart);

                            if ($trimmedPart != '') {

                                $endpointUrls[] = $trimmedPart;

                            }

                        }

                    }

                }

            }

            $this->endpointUrls = $endpointUrls;

        }

        return $this->endpointUrls;

    }

    /**
     * @param string[] $endpointUrls
     */
    public function setEndpointUrls($endpointUrls)
    {
        $this->endpointUrls = $endpointUrls;
    }

    /**
     * @param int $operationId
     */
    public function onOperationUpdate($operationId)
    {

        $operation = null;
        $bridgeName = null;
        $endpointUrls = $this->getEndpointUrls();

        if ($operationId) {

            $tableManager = $this->getTableManager();

            if ($tableManager) {

                $operation = $tableManager->findRecord('bo_operation', array('id' => $operationId));

                if ($operation) {

                    $bridgeId = $operation['bridge_id'];

                    if ($bridgeId) {

                        $bridge = $tableManager->findRecord('bo_bridge', array('id' => $bridgeId));

                        if ($bridge) {

                            if ($bridge['name']) {

                                if (($bridge['active'] == '') || ($bridge['active'] == '1')) {

                                    $bridgeName = $bridge['name'];

                                }

                            }

                        }

                    }

                }

            }

        }

        if ($operation && $bridgeName && $endpointUrls) {

            $params = $this->getOperationNotificationData($operation, $bridgeName);

            if ($params) {

                foreach ($endpointUrls as $url) {

                    $this->doPost($url, $params);

                }

            }

        }

    }

    /**
     * @param array $operation
     * @param string $bridgeName
     * @return array
     */
    public function getOperationNotificationData($operation, $bridgeName)
    {

        $params = array();
        $params['type'] = 'operation';
        $params['id'] = (int)$operation['id'];
        $params['bridge'] = $bridgeName;

        $start = null;

        if (array_key_exists('time_start', $operation) && is_object($operation['time_start'])) {

            $start = $operation['time_start']->getTimestamp();

        }

        $end = null;

        if (array_key_exists('time_end', $operation) && is_object($operation['time_end'])) {
            $end = $operation['time_end']->getTimestamp();
        } else if (array_key_exists('time_gone', $operation) && is_object($operation['time_gone'])) {
            $end = $operation['time_gone']->getTimestamp();
        }

        $params['start'] = $start;
        $params['end'] = $end;
        $params['certainty'] = (int)$operation['certainty'];
        $params['time'] = time();

        return $params;

    }

    public function doPost($url, $data)
    {

        $content = json_encode($data);

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/json',
                'content' => $content
            )
        );

        $context  = stream_context_create($opts);

        $result = file_get_contents($url, false, $context);
        return $result;

    }

}
