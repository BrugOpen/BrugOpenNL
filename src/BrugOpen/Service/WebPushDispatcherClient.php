<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;

class WebPushDispatcherClient
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
     * @var string
     */
    private $endpointUrl;

    /**
     * @param Context $context
     */
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
     * @return string
     */
    public function getEndpointUrl()
    {

        if ($this->endpointUrl == null) {

            $endpointUrl = null;

            if ($this->context) {

                $config = $this->context->getConfig();

                if ($config) {

                    $configParam = trim($config->getParam('webpushdispatcher.url'));

                    if ($configParam) {

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
     * @param array $messages
     * @param int $batchSize
     *
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

            $batch = array();
        }

        $endpointUrl = $this->getEndpointUrl();

        $log = $this->getLog();

        if ($endpointUrl) {

            foreach ($batches as $batch) {

                $log->info("Dispatching " . count($batch) . ' push message' . (count($batch) != 1 ? 's' : ''));

                $postdata = json_encode(array('messages' => $batch));

                $opts = array(
                    'http' =>
                    array(
                        'method'  => 'POST',
                        'header'  => 'Content-Type: application/json',
                        'content' => $postdata
                    )
                );

                $context = stream_context_create($opts);

                file_get_contents($endpointUrl, false, $context);
            }
        } else {
            $log->error('No webpushdispatcher.url set in config');
        }
    }
}
