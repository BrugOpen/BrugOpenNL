<?php

namespace BrugOpen\Model;

class WebPushSubscription
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $guid;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $expirationTime;

    /**
     * @var string
     */
    private $authPublickey;

    /**
     * @var string
     */
    private $authToken;

    /**
     * @var string
     */
    private $contentEncoding;

    /**
     * @var \DateTime
     */
    private $datetimeCreated;

    /**
     * @var \DateTime
     */
    private $datetimeModified;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @return string
     */
    public function getExpirationTime()
    {
        return $this->expirationTime;
    }

    /**
     * @param string $expirationTime
     */
    public function setExpirationTime($expirationTime)
    {
        $this->expirationTime = $expirationTime;
    }

    /**
     * @return string
     */
    public function getAuthPublickey()
    {
        return $this->authPublickey;
    }

    /**
     * @param string $authPublickey
     */
    public function setAuthPublickey($authPublickey)
    {
        $this->authPublickey = $authPublickey;
    }

    /**
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * @param string $authToken
     */
    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;
    }

    /**
     * @return string
     */
    public function getContentEncoding()
    {
        return $this->contentEncoding;
    }

    /**
     * @param string $contentEncoding
     */
    public function setContentEncoding($contentEncoding)
    {
        $this->contentEncoding = $contentEncoding;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeCreated()
    {
        return $this->datetimeCreated;
    }

    /**
     * @param \DateTime $datetimeCreated
     */
    public function setDatetimeCreated($datetimeCreated)
    {
        $this->datetimeCreated = $datetimeCreated;
    }

    /**
     * @return \DateTime
     */
    public function getDatetimeModified()
    {
        return $this->datetimeModified;
    }

    /**
     * @param \DateTime $datetimeModified
     */
    public function setDatetimeModified($datetimeModified)
    {
        $this->datetimeModified = $datetimeModified;
    }
}
