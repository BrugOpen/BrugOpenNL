<?php
namespace BrugOpen\Datex\Model;

class LogicalModel
{

    /**
     *
     * @var Exchange
     */
    private $exchange;

    /**
     *
     * @var PayloadPublication
     */
    private $payloadPublication;

    /**
     *
     * @return \BrugOpen\Datex\Model\Exchange
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\Exchange $exchange
     */
    public function setExchange($exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     *
     * @return \BrugOpen\Datex\Model\PayloadPublication
     */
    public function getPayloadPublication()
    {
        return $this->payloadPublication;
    }

    /**
     *
     * @param \BrugOpen\Datex\Model\PayloadPublication $payloadPublication
     */
    public function setPayloadPublication($payloadPublication)
    {
        $this->payloadPublication = $payloadPublication;
    }
}
