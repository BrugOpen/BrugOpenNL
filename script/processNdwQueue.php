<?php
$siteRoot = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
require_once $siteRoot . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
use BrugOpen\Core\DefaultContextFactory;
use BrugOpen\Ndw\Service\NdwQueueProcessor;

$contextFactory = new DefaultContextFactory();
$context = $contextFactory->createContext($siteRoot);

/**
 *
 * @var NdwQueueProcessor $ndwQueueProcessor
 */
$ndwQueueProcessor = $context->getService('BrugOpen.NdwQueueProcessor');
$ndwQueueProcessor->processQueue();
