<?php

namespace BrugOpen\Controller;

use BrugOpen\Core\ContextAware;
use BrugOpen\Service\WebPushSubscriptionService;

class SaveSubscriptionController implements ContextAware
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

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $subscriptionId = null;
            $pushSubscription = null;
            $notificationSchedules = null;
            $oldEndpoint = null;

            $entityBody = file_get_contents('php://input');

            if ($entityBody != '') {

                $request = @json_decode($entityBody, true);

                if (is_array($request)) {

                    if (array_key_exists('id', $request)) {

                        $subscriptionId = $request['id'];
                    }

                    if (array_key_exists('endpoint', $request)) {

                        $oldEndpoint = $request['endpoint'];
                    }

                    if (array_key_exists('pushSubscription', $request)) {

                        $pushSubscription = $request['pushSubscription'];
                    }

                    if (array_key_exists('schedules', $request)) {

                        $notificationSchedules = $request['schedules'];
                    }
                }
            }

            $context = $this->context;

            $pushSubscriptionService = new WebPushSubscriptionService();
            $pushSubscriptionService->initialize($context);

            if ($subscriptionId != '') {

                if (is_array($notificationSchedules)) {

                    $pushSubscriptionService->updateNotificationSchedules($subscriptionId, $notificationSchedules);
                }

                if ($pushSubscription !== null) {

                    $pushSubscriptionService->updatePushSubscription($subscriptionId, $pushSubscription);
                }
            } else {

                if ($oldEndpoint) {

                    $subscriptions = $pushSubscriptionService->findSubscriptionsByEndpoint($oldEndpoint);

                    if ($subscriptions) {

                        foreach ($subscriptions as $subscription) {

                            $subscriptionId = $subscription['guid'];

                            if ($pushSubscription !== null) {

                                $pushSubscriptionService->updatePushSubscription($subscriptionId, $pushSubscription);
                            }
                        }
                    }
                }
            }
        }

        header("Content-type: application/json");

        $res = 'ok';

        echo json_encode($res);
    }
}
