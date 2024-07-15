<?php

namespace BrugOpen\Rws\Service;

class FairwayServiceClient
{

    public function getRestApiEndpoint($version = '1.3')
    {

        $endpoint = 'https://www.vaarweginformatie.nl/wfswms/dataservice/' . $version . '/';
        return $endpoint;
    }

    /**
     * @return array|null
     */
    public function getCurrentGeoGeneration()
    {
        $url = $this->getRestApiEndpoint() . 'geogeneration';

        $response = $this->doCall($url);
        return $response;
    }

    public function listBridges($geoGeneration, $batchSize = 100, $offset = 0)
    {

        $url = $this->getRestApiEndpoint('1.3') . $geoGeneration . '/bridge/?count=' . urlencode($batchSize) . '&offset=' . urlencode($offset);

        // https://www.vaarweginformatie.nl/wfswms/dataservice/1.3/3158/bridge/?count=100&offset=100

        $response = $this->doCall($url);
        return $response;
    }

    /**
     * @param int $geoGeneration
     * @param string $geoType
     * @param int $id
     * @return array|null
     */
    public function getObject($geoGeneration, $geoType, $id)
    {
        $url = $this->getRestApiEndpoint('1.3') . $geoGeneration . '/' . $geoType . '/' . $id;
        $response = $this->doCall($url);
        return $response;
    }

    /**
     * @param int $geoGeneration
     * @param string $geoType
     * @param int $id
     * @param string $relation
     * @return array|null
     */
    public function getObjectRelation($geoGeneration, $geoType, $id, $relation)
    {
        $url = $this->getRestApiEndpoint('1.3') . $geoGeneration . '/' . $geoType . '/' . $id . '/' . $relation;
        $response = $this->doCall($url);
        return $response;
    }

    /**
     * @param string $url
     * @return array|null
     */
    public function doCall($url)
    {
        $response = null;

        $json = file_get_contents($url);

        if ($json) {

            if ($parsedJson = json_decode($json, true)) {

                $response = $parsedJson;
            }
        }

        return $response;
    }
}
