<?php

namespace BrugOpen\Controller;

use BrugOpen\Core\Context;
use BrugOpen\Core\ContextAware;
use BrugOpen\Db\Service\TableManager;

class PushWebhookController implements ContextAware
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
     * @param Context $context
     */
    public function setContext($context)
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
     * @return TableManager
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

    public function execute()
    {

        $messageId = array_key_exists('id', $_REQUEST) ? $_REQUEST['id'] : null;
        $subscriptionGuid = array_key_exists('guid', $_REQUEST) ? $_REQUEST['guid'] : null;
        $data = null;

        $json = file_get_contents('php://input');

        if (substr($json, 0, 1) == "{") {

            // Converts it into a PHP object
            $data = json_decode($json, true);
        }

        $log = $this->getLog();

        $log->info('Got webhook call message ID ' . $messageId . ':' . $subscriptionGuid . ':' . $json);

        if (preg_match('/^[1-9]+[0-9]*$/', $messageId) && $subscriptionGuid && $data) {

            $this->processWebhookResponse($messageId, $subscriptionGuid, $data);
        }
    }

    public function processWebhookResponse($messageId, $subscriptionGuid, $data)
    {

        $tableManager = $this->getTableManager();

        if ($tableManager) {

            $pushMessage = null;

            if ($messageId) {

                $pushMessage = $tableManager->findRecord('bo_push_message', array('id' => $messageId));
            }

            if ($pushMessage) {

                $hasResponseCode = false;

                if (array_key_exists('response_code', $pushMessage) && $pushMessage['response_code']) {

                    $hasResponseCode = true;
                }

                if (!$hasResponseCode) {

                    $subscription = null;

                    if ($subscriptionGuid) {

                        $subscription = $tableManager->findRecord('bo_push_subscription', array('guid' => $subscriptionGuid));
                    }

                    if ($subscription) {

                        if ($subscription['id'] == $pushMessage['subscription_id']) {

                            $statusCode = null;

                            if (is_array($data) && array_key_exists('statusCode', $data)) {

                                $statusCode = $data['statusCode'];
                            }

                            if ($statusCode) {

                                $tableManager->updateRecords('bo_push_message', array('response_code' => $statusCode), array('id' => $messageId));
                            }
                        }
                    }
                }
            }
        }
    }
}
