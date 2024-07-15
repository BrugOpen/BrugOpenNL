<?php
namespace BrugOpen\Controller\Admin;

use BrugOpen\Core\Context;
use BrugOpen\Core\ContextAware;
use BrugOpen\Geo\Model\LatLngBounds;
use BrugOpen\Tracking\Service\WaterwayService;

class EditSegmentsController implements ContextAware
{

    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    public function execute()
    {

        $context = $this->context;
        $siteRoot = $context->getAppRoot();

        $waterwayService = new WaterwayService();
        $waterwayService->initialize($context);

        if ($_SERVER['REQUEST_METHOD'] == "POST") {

            $res = false;

            $id = array_key_exists('id', $_POST) ? $_POST['id'] : null;
            $title = $_POST['title'];
            $coordinates = $_POST['coordinates'];
            $routePoints = array();

            $values = array('title' => $title, 'coordinates' => $coordinates);

            // determine updated area bounding box

            $updatedAreaBounds = null;

            if ($id != '') {

                // add current area to bounds

                $allSegments = $waterwayService->loadWaterwaySegments();

                $segment = $allSegments[$id];

                $updatedAreaBounds = new LatLngBounds($segment->getPolygon()->getPath());

                // update record in database

                $keys = array('id' => $id);

                $context->getDataStore()->updateTable('bo_waterway_segment', $keys, $values);

            } else {

                $id = $context->getDataStore()->insertRecord('bo_waterway_segment', $values);

            }

            if ($id) {

                $allSegments = $waterwayService->loadWaterwaySegments();

                $segment = $allSegments[$id];

                // determine current route points

                $routePointsText = '';

                $routePoints = $waterwayService->determineSegmentsRoutePoints($segment);

                $segment->setRoutePoints($routePoints);

                if ($routePoints) {

                    $routePointsParts = array();

                    foreach ($routePoints as $routePoint) {

                        $routePointsParts[] = $routePoint->toString();

                    }

                    $routePointsText = implode("\n", $routePointsParts);

                }

                // update route points in database if needed

                $tableManager = $waterwayService->getTableManager();

                if ($tableManager) {

                    $keys = array('id' => $id);
                    $record = $tableManager->findRecord('bo_waterway_segment', $keys);

                    if ($record) {

                        if ($record['route_points'] != $routePointsText) {

                            $values = array('route_points' => $routePointsText);
                            $keys = array('id' => $segment->getId());

                            $tableManager->updateRecords('bo_waterway_segment', $values, $keys);

                        }

                    }

                }

                if ($updatedAreaBounds != null) {

                    // extend existing bounds

                    foreach ($segment->getPolygon()->getPath() as $latLng) {

                        $updatedAreaBounds->extend($latLng);

                    }

                } else {

                    $updatedAreaBounds = new LatLngBounds($segment->getPolygon()->getPath());

                }

                // collect all segments in updated area bounds

                $segmentsInUpdatedArea = array();

                foreach ($allSegments as $tmpSegment) {

                    $bounds = new LatLngBounds($tmpSegment->getPolygon()->getPath());

                    if ($bounds->overlaps($updatedAreaBounds)) {

                        $segmentsInUpdatedArea[] = $tmpSegment;

                    }

                }

                foreach ($segmentsInUpdatedArea as $segmentInUpdatedArea) {

                    $connectedSegments = $waterwayService->determineConnectedWaterwaySegments($segmentInUpdatedArea, $allSegments);

                    $existingConnectedSegments = $segmentInUpdatedArea->getConnectedSegmentIds();

                    $connectedSegmentsText = implode(',', $connectedSegments);
                    $existingConnectedSegmentsText = implode(',', $existingConnectedSegments);

                    if ($connectedSegmentsText != $existingConnectedSegmentsText) {

                        // update connected segments in database

                        $updatedSegmentId = $segmentInUpdatedArea->getId();

                        $values = array('connected_segments' => $connectedSegmentsText);
                        $keys = array('id' => $updatedSegmentId);

                        $tableManager->updateRecords('bo_waterway_segment', $values, $keys);

                        // update segment object

                        $segmentInUpdatedArea->setConnectedSegmentIds($connectedSegments);

                    }

                }

                // TODO check connected segment for bridges in updated area bounds




                // prepare response data

                foreach ($segment->getPolygon()->getPath() as $latLng) {

                    $lat = $latLng->getLat();
                    $lng = $latLng->getLng();

                    $coords[] = array('lat' => $lat, 'lng' => $lng);

                }

                $routePoints = array();

                foreach ($segment->getRoutePoints() as $routePoint) {

                    $lat = $routePoint->getLat();
                    $lng = $routePoint->getLng();

                    $routePoints[] = array('lat' => $lat, 'lng' => $lng);

                }

                $res = array();
                $res['id'] = $id;
                $res['title'] = $title;
                $res['coords'] = $coords;
                $res['routePoints'] = $routePoints;

            }

            header('Content-type: application/json');

            echo json_encode($res);
            exit;

        }

        $segments = $waterwayService->loadWaterwaySegments();

        $maxLat = null;
        $minLat = null;
        $maxLng = null;
        $minLng = null;

        $segmentPolygons = array();

        if (is_array($segments)) {

            foreach ($segments as $id => $segment) {

                $coords = array();

                foreach ($segment->getPolygon()->getPath() as $latLng) {

                    $lat = $latLng->getLat();
                    $lng = $latLng->getLng();

                    if (($maxLat == null) || ($lat > $maxLat)) {
                        $maxLat = $lat;
                    }

                    if (($minLat == null) || ($lat < $minLat)) {
                        $minLat = $lat;
                    }

                    if (($maxLng == null) || ($lng > $maxLng)) {
                        $maxLng = $lng;
                    }

                    if (($minLng == null) || ($lng < $minLng)) {
                        $minLng = $lng;
                    }

                    $coords[] = array('lat' => $lat, 'lng' => $lng);

                }

                $routePoints = array();

                foreach ($segment->getRoutePoints() as $routePoint) {

                    $lat = $routePoint->getLat();
                    $lng = $routePoint->getLng();

                    $routePoints[] = array('lat' => $lat, 'lng' => $lng);

                }

                $segmentPolygons[$id] = array();
                $segmentPolygons[$id]['id'] = $segment->getId();
                $segmentPolygons[$id]['title'] = $segment->getTitle();
                $segmentPolygons[$id]['coords'] = $coords;
                $segmentPolygons[$id]['routePoints'] = $routePoints;

            }

        }

        $contentType = 'edit_waterway_segments';

        $templatesDir = $siteRoot . 'templates' . DIRECTORY_SEPARATOR;

        $loader = new \Twig_Loader_Filesystem($templatesDir);

        $cacheDir = $siteRoot . 'cache' . DIRECTORY_SEPARATOR . 'twig';

        $twig = new \Twig_Environment($loader, [
            'cache' => $cacheDir,
            'auto_reload' => true
        ]);

        $template = $twig->load('admin/start.twig');

        $renderData = array();
        $renderData['contentType'] = $contentType;

        $renderData['pageTitle'] = 'Waterweg-segmenten bewerken | BrugOpen';

        $renderData['segmentPolygons'] = $segmentPolygons;

        $renderData['maxLat'] = $maxLat;
        $renderData['minLat'] = $minLat;
        $renderData['maxLng'] = $maxLng;
        $renderData['minLng'] = $minLng;

        echo $template->render($renderData);

    }

}
