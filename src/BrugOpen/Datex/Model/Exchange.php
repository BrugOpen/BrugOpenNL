<?php
namespace BrugOpen\Datex\Model;

class Exchange
{

    /*
     * <deliveryBreak>true</deliveryBreak>
     * <keepAlive>true</keepAlive>
     * <requestType>subscription</requestType>
     * <supplierIdentification>
     * <country>nl</country>
     * <nationalIdentifier>NLNDW</nationalIdentifier>
     * </supplierIdentification>
     */

    /**
     *
     * @var boolean
     */
    private $deliveryBreak;

    /**
     *
     * @var boolean
     */
    private $keepAlive;

    /**
     *
     * @var string
     */
    private $requestType;

    /**
     *
     * @var InternationalIdentifier
     */
    private $supplierIdentification;

    /**
     *
     * @var Subscription
     */
    private $subscription;

    /**
     *
     * @return boolean
     */
    public function getDeliveryBreak()
    {
        return $this->deliveryBreak;
    }

    /**
     *
     * @param boolean $deliveryBreak
     */
    public function setDeliveryBreak($deliveryBreak)
    {
        $this->deliveryBreak = $deliveryBreak;
    }

    /**
     *
     * @return boolean
     */
    public function getKeepAlive()
    {
        return $this->keepAlive;
    }

    /**
     *
     * @param boolean $keepAlive
     */
    public function setKeepAlive($keepAlive)
    {
        $this->keepAlive = $keepAlive;
    }

    /**
     *
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     *
     * @param string $requestType
     */
    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\InternationalIdentifier
     */
    public function getSupplierIdentification()
    {
        return $this->supplierIdentification;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\InternationalIdentifier $supplierIdentification
     */
    public function setSupplierIdentification($supplierIdentification)
    {
        $this->supplierIdentification = $supplierIdentification;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\Subscription $subscription
     */
    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;
    }
}
