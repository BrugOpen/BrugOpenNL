<?php
namespace BrugOpen\Datex\Model;

class LifeCycleManagement
{

    /**
     *
     * @var boolean
     */
    private $cancel;

    /**
     *
     * @var boolean
     */
    private $end;

    /**
     *
     * @return boolean
     */
    public function getCancel()
    {
        return $this->cancel;
    }

    /**
     *
     * @param boolean $cancel
     */
    public function setCancel($cancel)
    {
        $this->cancel = $cancel;
    }

    /**
     *
     * @return boolean
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     *
     * @param boolean $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }
}
