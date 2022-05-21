<?php
namespace BrugOpen\Datex\Model;

class FilterExitManagement
{

    /**
     *
     * @var boolean
     */
    private $filterEnd;

    /**
     *
     * @var boolean
     */
    private $filterOutOfRange;

    /**
     *
     * @return boolean
     */
    public function getFilterEnd()
    {
        return $this->filterEnd;
    }

    /**
     *
     * @param boolean $filterEnd
     */
    public function setFilterEnd($filterEnd)
    {
        $this->filterEnd = $filterEnd;
    }

    /**
     *
     * @return boolean
     */
    public function getFilterOutOfRange()
    {
        return $this->filterOutOfRange;
    }

    /**
     *
     * @param boolean $filterOutOfRange
     */
    public function setFilterOutOfRange($filterOutOfRange)
    {
        $this->filterOutOfRange = $filterOutOfRange;
    }
}
