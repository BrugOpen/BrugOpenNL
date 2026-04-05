<?php

namespace BrugOpen\Ndw\Service;

use BrugOpen\Core\Context;
use BrugOpen\Core\EventDispatcher;
use Psr\Log\LoggerInterface;

class NdwQueueService
{

    /**
     *
     * @var Context
     */
    private $context;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     *
     * @param Context $context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }

    public function getLog()
    {
        if ($this->log == null) {

            $this->log = $this->context->getLogRegistry()->getLog($this);
        }

        return $this->log;
    }

    /**
     *
     * @param LoggerInterface $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        if ($this->eventDispatcher == null) {

            if ($this->context) {

                $this->eventDispatcher = $this->context->getEventDispatcher();
            }
        }

        return $this->eventDispatcher;
    }

    /**
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     *
     */
    public function addQueueFile($filename, $contents)
    {
        $datadir = $this->context->getAppRoot() . 'data' . DIRECTORY_SEPARATOR;
        $queueDir = $datadir . 'ndw' . DIRECTORY_SEPARATOR . 'queue' . DIRECTORY_SEPARATOR;

        if (! is_dir($queueDir)) {

            mkdir($queueDir, 0777, true);
        }

        $targetFile = $queueDir . $filename;

        if (file_exists($targetFile)) {

            unlink($targetFile);
        }

        file_put_contents($targetFile, $contents);

        $logger = $this->getLog();

        if ($logger) {

            $logger->info('NDW queue file added: ' . $filename);
        }

        $touchFile = $datadir . 'ndw' . DIRECTORY_SEPARATOR . 'lastpush';
        touch($touchFile);

        $eventDispatcher = $this->getEventDispatcher();

        if ($eventDispatcher) {

            $eventDispatcher->postEvent('Ndw.QueueFileAdded', array('filename' => $filename));
        }
    }
}
