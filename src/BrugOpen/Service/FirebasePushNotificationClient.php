<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;

class FirebasePushNotificationClient
{

    /**
     * @var Context
     */
    private $context;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @var string
     */
    private $endpointUrl;

    /**
     * @var string
     */
    private $bearerToken;

    /**
     * @param Context $context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog()
    {
        if ($this->log == null) {

            if ($this->context != null) {

                $this->log = $this->context->getLogRegistry()->getLog($this);
            }
        }

        return $this->log;
    }

    /**
     * @param \Psr\Log\LoggerInterface $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    /**
     * @return string
     */
    public function getEndpointUrl()
    {
        if ($this->endpointUrl == null) {

            $endpointUrl = null;

            if ($this->context) {

                $config = $this->context->getConfig();

                if ($config) {

                    $configParam = trim((string)$config->getParam('firebasepushdispatcher.url'));

                    if ($configParam != '') {

                        $endpointUrl = $configParam;
                    }
                }
            }

            $this->endpointUrl = $endpointUrl;
        }

        return $this->endpointUrl;
    }

    /**
     * @param string $endpointUrl
     */
    public function setEndpointUrl($endpointUrl)
    {
        $this->endpointUrl = $endpointUrl;
    }

    /**
     * @return string
     */
    public function getBearerToken()
    {
        if ($this->bearerToken == null) {

            $bearerToken = '';

            if ($this->context) {

                $config = $this->context->getConfig();

                if ($config) {

                    $configParam = trim((string)$config->getParam('firebasepushdispatcher.token'));

                    if ($configParam != '') {

                        $bearerToken = $configParam;
                    }
                }
            }

            $this->bearerToken = $bearerToken;
        }

        return $this->bearerToken;
    }

    /**
     * @param string $bearerToken
     */
    public function setBearerToken($bearerToken)
    {
        $this->bearerToken = $bearerToken;
    }

    /**
     * @param array $messages
     * @param int $batchSize
     */
    public function dispatchMessages($messages, $batchSize = 100)
    {
        $batches = array();
        $batch = array();

        foreach ($messages as $message) {

            $batch[] = $message;

            if (count($batch) == $batchSize) {

                $batches[] = $batch;
                $batch = array();
            }
        }

        if (count($batch) > 0) {

            $batches[] = $batch;
        }

        $endpointUrl = $this->getEndpointUrl();
        $bearerToken = $this->getBearerToken();
        $log = $this->getLog();

        if ($endpointUrl) {

            foreach ($batches as $batch) {

                $log->info('Dispatching ' . count($batch) . ' firebase push message' . (count($batch) != 1 ? 's' : ''));

                $postdata = json_encode(array('messages' => $batch));

                $headers = array('Content-Type: application/json');

                if ($bearerToken != '') {
                    $headers[] = 'Authorization: Bearer ' . $bearerToken;
                }

                $opts = array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => implode("\r\n", $headers),
                        'content' => $postdata
                    )
                );

                $context = stream_context_create($opts);

                file_get_contents($endpointUrl, false, $context);
            }
        } else {
            $log->error('No firebasepushdispatcher.url set in config');
        }
    }
}
