<?php
namespace BrugOpen\Ndw\Service;

use BrugOpen\Core\Context;
use BrugOpen\Datex\Service\DatexFileParser;

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
     * @param Context $context
     */
    public function __construct($context)
    {
        $this->context = $context;
        $this->log = $context->getLogRegistry()->getLog($this);
    }

    /**
     *
     * @return SituationProcessor
     */
    public function getSituationProcessor()
    {
        $situationProcessor = new SituationProcessor($this->context);
        return $situationProcessor;
    }

    public function processQueue()
    {
        $datadir = $this->context->getAppRoot() . 'data' . DIRECTORY_SEPARATOR;
        $queueDir = $datadir . 'ndw' . DIRECTORY_SEPARATOR . 'queue' . DIRECTORY_SEPARATOR;

        if (! is_dir($queueDir)) {

            $this->log->warning('NDW Queue dir ' . $queueDir . ' does not exist');
            return;
        }

        $this->log->info('Processing NDW queue');

        $situationProcessor = $this->getSituationProcessor();

        if ($handle = opendir($queueDir)) {

            $files = array();

            while (false !== ($entry = readdir($handle))) {
                if ($entry == "." && $entry == "..") {
                    continue;
                }

                $filename = $queueDir . $entry;

                if (is_file($filename)) {

                    $files[] = $entry;
                }
            }

            closedir($handle);

            if (sizeof($files) > 0) {

                sort($files);

                $this->log->info('Processing ' . sizeof($files) . ' NDW files');

                $fileParser = new DatexFileParser();

                foreach ($files as $file) {

                    $queueFile = $queueDir . $file;

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

                        if ($fileData->getExchange()) {

                            if ($fileData->getExchange()->getSubscription()) {

                                if ($fileData->getExchange()
                                    ->getSubscription()
                                    ->getUpdateMethod() == 'snapshot') {

                                    if ($publicationTime != null) {
                                        $situationProcessor->checkUnfinishedOperations($publicationTime);
                                    }
                                }
                            }
                        }
                    } else {

                        $this->log->error('Could not parse ' . basename($file));
                    }
                }

                $situationProcessor->markUncertainSituationsIgnored();
            }
        }
    }
}
