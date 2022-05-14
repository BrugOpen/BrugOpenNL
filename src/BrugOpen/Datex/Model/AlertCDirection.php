<?php
namespace BrugOpen\Datex\Model;

class AlertCDirection
{

    /**
     *
     * @var string
     */
    private $alertCDirectionCoded;

    /**
     *
     * @var MultiLingualString
     */
    private $alertCDirectionNamed;

    /**
     *
     * @var boolean
     */
    private $alertCDirectionSense;

    /**
     *
     * @return string
     */
    public function getAlertCDirectionCoded()
    {
        return $this->alertCDirectionCoded;
    }

    /**
     *
     * @param string $alertCDirectionCoded
     */
    public function setAlertCDirectionCoded($alertCDirectionCoded)
    {
        $this->alertCDirectionCoded = $alertCDirectionCoded;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\MultiLingualString
     */
    public function getAlertCDirectionNamed()
    {
        return $this->alertCDirectionNamed;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\MultiLingualString $alertCDirectionNamed
     */
    public function setAlertCDirectionNamed($alertCDirectionNamed)
    {
        $this->alertCDirectionNamed = $alertCDirectionNamed;
    }

    /**
     *
     * @return boolean
     */
    public function getAlertCDirectionSense()
    {
        return $this->alertCDirectionSense;
    }

    /**
     *
     * @param boolean $alertCDirectionSense
     */
    public function setAlertCDirectionSense($alertCDirectionSense)
    {
        $this->alertCDirectionSense = $alertCDirectionSense;
    }
}