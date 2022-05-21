<?php
namespace BrugOpen\Datex\Model;

class Management
{

    /**
     *
     * @var LifeCycleManagement
     */
    private $lifeCycleManagement;

    /**
     *
     * @var FilterExitManagement
     */
    private $filterExitManagement;

    /**
     *
     * @return \BrugOpen\Datex\Model\LifeCycleManagement
     */
    public function getLifeCycleManagement()
    {
        return $this->lifeCycleManagement;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\LifeCycleManagement $lifeCycleManagement
     */
    public function setLifeCycleManagement($lifeCycleManagement)
    {
        $this->lifeCycleManagement = $lifeCycleManagement;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\FilterExitManagement
     */
    public function getFilterExitManagement()
    {
        return $this->filterExitManagement;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\FilterExitManagement $filterExitManagement
     */
    public function setFilterExitManagement($filterExitManagement)
    {
        $this->filterExitManagement = $filterExitManagement;
    }
}
