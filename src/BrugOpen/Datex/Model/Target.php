<?php
namespace BrugOpen\Datex\Model;

class Target
{

    /**
     *
     * @var string
     */
    private $address;

    /**
     *
     * @var string
     */
    private $protocol;

    /**
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     *
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     *
     * @param string $protocol
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }
}
