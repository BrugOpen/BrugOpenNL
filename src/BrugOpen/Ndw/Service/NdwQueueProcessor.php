<?php
namespace BrugOpen\Ndw\Service;

use BrugOpen\Core\Context;
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
     * 
     */
    public function processQueue()
    {
        $datadir = $this->context->getAppRoot() . 'data' . DIRECTORY_SEPARATOR;
        $queueDir = $datadir . 'ndw' . DIRECTORY_SEPARATOR . 'queue' . DIRECTORY_SEPARATOR;

        if (! is_dir($queueDir)) {

            $this->log->warning('NDW Queue dir ' . $queueDir . ' does not exist');
            return;
        }

        $this->log->info('Processing NDW queue');

        $files = $this->getQueueFiles($queueDir);

        if (sizeof($files) > 0) {

            $this->processQueueFiles($files);
        }
    }

    /**
     * 
     * @param string[] $queueFiles
     */
    public function processQueueFiles($queueFiles)
    {
        if (sizeof($queueFiles) > 0) {

            $this->log->info('Processing ' . sizeof($queueFiles) . ' NDW files');

            $situationProcessor = $this->getSituationProcessor();

            foreach ($queueFiles as $queueFile) {

                $this->processQueueFile($queueFile);

            }

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

        $this->log->info('Processing ' . $file);

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
                            $situationProcessor->checkUnfinishedGoneOperations($publicationTime);
                        }
                    }
                }
            }

            // TODO check for deliveryBreak = true, then send reqisterRequest

        } else {

            $this->log->error('Could not parse ' . $file);
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

            if (substr($queueDir, - 1) != DIRECTORY_SEPARATOR) {

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
}
