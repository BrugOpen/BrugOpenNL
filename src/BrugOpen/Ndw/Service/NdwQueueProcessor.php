<?php

namespace BrugOpen\Ndw\Service;

use BrugOpen\Core\Context;
use BrugOpen\Core\EventDispatcher;
use BrugOpen\Datex\Service\DatexFileParser;
use Psr\Log\LoggerInterface;

class NdwQueueProcessor
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
     *
     * @var SituationProcessor
     */
    private $situationProcessor;

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
     *
     * @return SituationProcessor
     */
    public function getSituationProcessor()
    {
        if ($this->situationProcessor == null) {

            $situationProcessor = new SituationProcessor($this->context);
            $this->situationProcessor = $situationProcessor;
        }

        return $this->situationProcessor;
    }

    /**
     *
     * @param SituationProcessor $situationProcessor
     */
    public function setSituationProcessor($situationProcessor)
    {
        $this->situationProcessor = $situationProcessor;
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
    public function processQueue()
    {
        $datadir = $this->context->getAppRoot() . 'data' . DIRECTORY_SEPARATOR;
        $queueDir = $datadir . 'ndw' . DIRECTORY_SEPARATOR . 'queue' . DIRECTORY_SEPARATOR;

        $log = $this->getLog();

        if (! is_dir($queueDir)) {

            $log->warning('NDW Queue dir ' . $queueDir . ' does not exist');
            return;
        }

        $log->info('Processing NDW queue');

        $files = $this->getQueueFiles($queueDir);

        if (sizeof($files) > 0) {

            $this->processQueueFiles($files);
        }
    }

    /**
     *
     * @param string[] $queueFiles
     * @param boolean $archiveFiles
     */
    public function processQueueFiles($queueFiles, $archiveFiles = true)
    {
        if (sizeof($queueFiles) > 0) {

            $log = $this->getLog();

            $log->info('Processing ' . sizeof($queueFiles) . ' NDW files');

            $situationProcessor = $this->getSituationProcessor();

            $numProcessed = 0;

            foreach ($queueFiles as $queueFile) {

                $this->processQueueFile($queueFile);

                if ($archiveFiles) {

                    // archive processed file

                    $archiveRoot = $this->getArchiveRoot();

                    $archiveDir = $this->getArchiveDir($archiveRoot, time());

                    if (!is_dir($archiveDir)) {

                        mkdir($archiveDir, 0755, true);
                    }

                    $fileArchived = false;

                    if (is_dir($archiveDir)) {

                        $archiveFile = $archiveDir . basename($queueFile);

                        $fileArchived = rename($queueFile, $archiveFile);
                    }

                    if ($fileArchived) {

                        $log->info('Archived ' . basename($queueFile));
                    }
                }

                $numProcessed++;

                if ($numProcessed % 100 == 0) {

                    $log->info('Processed ' . $numProcessed . ' NDW files');
                }
            }

            $log->info('Processed ' . $numProcessed . ' NDW files');

            $situationProcessor->markUncertainSituationsIgnored();
        }
    }

    /**
     *
     * @param string $queueFile
     */
    public function processQueueFile($queueFile)
    {
        $situationProcessor = $this->getSituationProcessor();

        $fileParser = new DatexFileParser();

        $file = basename($queueFile);

        $log = $this->getLog();

        $log->info('Processing ' . $file);

        $fileData = $fileParser->parseFile($queueFile);

        if ($fileData) {

            /**
             *
             * @var \DateTime $publicationTime
             */
            $publicationTime = null;

            if ($fileData->getPayloadPublication()) {

                $publicationTime = $fileData->getPayloadPublication()->getPublicationTime();

                $situations = $fileData->getPayloadPublication()->getSituations();

                if ($situations) {

                    foreach ($situations as $situation) {

                        $situationProcessor->processSituation($situation, $publicationTime);
                    }
                }
            }

            $exchange = $fileData->getExchange();

            if ($exchange) {

                $subscription = $fileData->getExchange()->getSubscription();

                if ($subscription) {

                    $updateMethod = $subscription->getUpdateMethod();

                    if ($updateMethod == 'snapshot') {

                        if ($publicationTime != null) {

                            $log->debug('Checking unfunished gone operations for publication time ' . $publicationTime->format('Y-m-d H:i:s'));

                            $situationProcessor->checkUnfinishedGoneOperations($publicationTime);
                        }
                    }
                }

                if ($exchange->getKeepAlive() == 'true') {

                    if ($exchange->getDeliveryBreak() == 'true') {

                        $log->info("Received deliveryBreak message");

                        // dispatch deliveryBreak event
                        $eventDispatcher = $this->getEventDispatcher();

                        if ($eventDispatcher) {

                            $eventDispatcher->postEvent('Ndw.DeliveryBreak', array());
                        }
                    }
                }
            }
        } else {

            $log->error('Could not parse ' . $file);
        }
    }

    /**
     *
     * @param string $queueDir
     * @return string[]
     */
    public function getQueueFiles($queueDir)
    {
        $files = array();

        if (is_dir($queueDir)) {

            if (substr($queueDir, -1) != DIRECTORY_SEPARATOR) {

                $queueDir .= DIRECTORY_SEPARATOR;
            }

            if ($handle = opendir($queueDir)) {

                while (false !== ($entry = readdir($handle))) {
                    if ($entry == "." && $entry == "..") {
                        continue;
                    }

                    $filename = $queueDir . $entry;

                    if (is_file($filename)) {

                        $files[] = $queueDir . $entry;
                    }
                }

                closedir($handle);

                sort($files);
            }
        }

        return $files;
    }

    /**
     * @return string
     */
    public function getArchiveRoot()
    {

        $datadir = $this->context->getAppRoot() . 'data' . DIRECTORY_SEPARATOR;
        $archiveRoot = $datadir . 'ndw' . DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR;

        return $archiveRoot;
    }

    /**
     * @param string $archiveRoot
     * @param int $time
     * @return string
     */
    public function getArchiveDir($archiveRoot, $time)
    {

        $date = date('Y-m-d', $time);

        if (substr($archiveRoot, -1) != DIRECTORY_SEPARATOR) {

            $archiveRoot .= DIRECTORY_SEPARATOR;
        }

        $yearDir = $archiveRoot . date('Y', $time) . DIRECTORY_SEPARATOR;

        $archiveDir = $yearDir . $date . DIRECTORY_SEPARATOR;

        return $archiveDir;
    }
}
