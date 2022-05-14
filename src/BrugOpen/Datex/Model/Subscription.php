<?php
namespace BrugOpen\Datex\Model;

use DateTime;

class Subscription
{

    /**
     *
     * @var boolean
     */
    private $deleteSubscription;

    /**
     *
     * @var float
     */
    private $deliveryInterval;

    /**
     *
     * @var string
     */
    private $operatingMode;

    /**
     *
     * @var DateTime
     */
    private $subscriptionStartTime;

    /**
     *
     * @var string
     */
    private $subscriptionState;

    /**
     *
     * @var DateTime
     */
    private $subscriptionStopTime;

    /**
     *
     * @var string
     */
    private $updateMethod;

    /**
     *
     * @var Target
     */
    private $target;

    /**
     *
     * @var FilterReference
     */
    private $filterReference;

    /**
     *
     * @var CatalogueReference
     */
    private $catalogueReference;

    /**
     *
     * @return boolean
     */
    public function getDeleteSubscription()
    {
        return $this->deleteSubscription;
    }

    /**
     *
     * @param boolean $deleteSubscription
     */
    public function setDeleteSubscription($deleteSubscription)
    {
        $this->deleteSubscription = $deleteSubscription;
    }

    /**
     *
     * @return number
     */
    public function getDeliveryInterval()
    {
        return $this->deliveryInterval;
    }

    /**
     *
     * @param number $deliveryInterval
     */
    public function setDeliveryInterval($deliveryInterval)
    {
        $this->deliveryInterval = $deliveryInterval;
    }

    /**
     *
     * @return string
     */
    public function getOperatingMode()
    {
        return $this->operatingMode;
    }

    /**
     *
     * @param string $operatingMode
     */
    public function setOperatingMode($operatingMode)
    {
        $this->operatingMode = $operatingMode;
    }

    /**
     *
     * @return DateTime
     */
    public function getSubscriptionStartTime()
    {
        return $this->subscriptionStartTime;
    }

    /**
     *
     * @param DateTime $subscriptionStartTime
     */
    public function setSubscriptionStartTime($subscriptionStartTime)
    {
        $this->subscriptionStartTime = $subscriptionStartTime;
    }

    /**
     *
     * @return string
     */
    public function getSubscriptionState()
    {
        return $this->subscriptionState;
    }

    /**
     *
     * @param string $subscriptionState
     */
    public function setSubscriptionState($subscriptionState)
    {
        $this->subscriptionState = $subscriptionState;
    }

    /**
     *
     * @return DateTime
     */
    public function getSubscriptionStopTime()
    {
        return $this->subscriptionStopTime;
    }

    /**
     *
     * @param DateTime $subscriptionStopTime
     */
    public function setSubscriptionStopTime($subscriptionStopTime)
    {
        $this->subscriptionStopTime = $subscriptionStopTime;
    }

    /**
     *
     * @return string
     */
    public function getUpdateMethod()
    {
        return $this->updateMethod;
    }

    /**
     *
     * @param string $updateMethod
     */
    public function setUpdateMethod($updateMethod)
    {
        $this->updateMethod = $updateMethod;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\Target
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\Target $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\FilterReference
     */
    public function getFilterReference()
    {
        return $this->filterReference;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\FilterReference $filterReference
     */
    public function setFilterReference($filterReference)
    {
        $this->filterReference = $filterReference;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\CatalogueReference
     */
    public function getCatalogueReference()
    {
        return $this->catalogueReference;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\CatalogueReference $catalogueReference
     */
    public function setCatalogueReference($catalogueReference)
    {
        $this->catalogueReference = $catalogueReference;
    }
}
