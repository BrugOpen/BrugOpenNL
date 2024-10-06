<?php

namespace BrugOpen\Controller;

use BrugOpen\Core\Context;
use BrugOpen\Core\ContextAware;
use BrugOpen\Db\Service\TableManager;
use BrugOpen\Service\RenderDataService;

class FrontEndController implements ContextAware
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

        $context = $this->context;
        $siteRoot = $context->getAppRoot();

        $requestUri = $_SERVER['REQUEST_URI'];

        if (substr($requestUri, 0, 8) == '/assets/') {

            $assetFile = $this->getAssetFile($requestUri);

            if ($assetFile) {

                $contentType = $this->getContentType($assetFile);

                if ($contentType) {

                    header('Content-type: ' . $contentType);
                }

                header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (3600 * 24 * 365))); // 1 year

                echo file_get_contents($assetFile);
                return;
            }
        }

        $templatesDir = $siteRoot . 'templates' . DIRECTORY_SEPARATOR;

        $loader = new \Twig_Loader_Filesystem($templatesDir);

        $cacheDir = $siteRoot . 'cache' . DIRECTORY_SEPARATOR . 'twig';

        $twig = new \Twig_Environment($loader, [
            'cache' => $cacheDir,
            'auto_reload' => true
        ]);

        $template = $twig->load('start.twig');

        $localRenderDataServiceClass = 'BrugOpen\Service\LocalRenderDataService';

        if (class_exists($localRenderDataServiceClass)) {
            $renderDataService = new $localRenderDataServiceClass();
        } else {
            $renderDataService = new RenderDataService();
        }

        $renderDataService->initialize($context);

        $renderData = $renderDataService->getRenderData($requestUri);

        // $renderData['segmentPolygons'] = $segmentPolygons;

        // $renderData['maxLat'] = $maxLat;
        // $renderData['minLat'] = $minLat;
        // $renderData['maxLng'] = $maxLng;
        // $renderData['minLng'] = $minLng;

        header('Content-type: text/html; charset=UTF-8');

        $template->display($renderData);
    }

    public function getAssetFile($requestUri)
    {

        $assetFile = null;

        if (substr($requestUri, 0, 8) == '/assets/') {

            $assetUri = substr($requestUri, 8);

            if (strpos($assetUri, '?') !== false) {

                $assetUri = substr($assetUri, 0, strpos($assetUri, '?'));
            }

            $urlParts = explode('/', $assetUri);

            if (count($urlParts) == 2) {

                if (!in_array('..', $urlParts)) {

                    $assetDir = $this->context->getAppRoot() . 'html' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
                    $tmpFile = $assetDir . $urlParts[0] . DIRECTORY_SEPARATOR . $urlParts[1];

                    if (is_file($tmpFile)) {

                        $assetFile = $tmpFile;
                    }
                }
            }
        }

        return $assetFile;
    }

    public function getContentType($file)
    {

        $contentType = null;

        if (substr($file, -4) == '.svg') {

            $contentType = 'image/svg+xml';
        } else if (substr($file, -4) == '.png') {

            $contentType = 'image/png';
        } else if (substr($file, -3) == '.js') {

            $contentType = 'text/javascript';
        }

        return $contentType;
    }
}
