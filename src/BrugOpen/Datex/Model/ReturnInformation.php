<?php

namespace BrugOpen\Datex\Model;

class ReturnInformation
{

    /**
     * @var string
     */
    private $returnStatus;

    /**
     * @var MultiLingualString
     */
    private $returnStatusReason;

    /**
     * @var string[]
     */
    private $codedInvalidityReason;

    // <xs:element name="returnStatus" type="ex:_ExchangeReturnEnum" minOccurs="1" maxOccurs="1"/>
    // <xs:element name="returnStatusReason" type="com:MultilingualString" minOccurs="0" maxOccurs="1"/>
    // <xs:element name="codedInvalidityReason" type="ex:_InvalidityReasonEnum" minOccurs="0" maxOccurs="unbounded"/>

    /**
     * Get the value of returnStatus
     *
     * @return string
     */
    public function getReturnStatus()
    {
        return $this->returnStatus;
    }

    /**
     * Set the value of returnStatus
     *
     * @param string $returnStatus
     * @return void
     */
    public function setReturnStatus($returnStatus)
    {
        $this->returnStatus = $returnStatus;
    }

    /**
     * Get the value of returnStatusReason
     *
     * @return MultiLingualString
     */
    public function getReturnStatusReason()
    {
        return $this->returnStatusReason;
    }

    /**
     * Set the value of returnStatusReason
     *
     * @param MultiLingualString $returnStatusReason
     * @return void
     */
    public function setReturnStatusReason($returnStatusReason)
    {
        $this->returnStatusReason = $returnStatusReason;
    }

    /**
     * Get the value of codedInvalidityReason
     *
     * @return string[]
     */
    public function getCodedInvalidityReason()
    {
        return $this->codedInvalidityReason;
    }

    /**
     * Set the value of codedInvalidityReason
     *
     * @param string[] $codedInvalidityReason
     * @return void
     */
    public function setCodedInvalidityReason($codedInvalidityReason)
    {
        $this->codedInvalidityReason = $codedInvalidityReason;
    }
}
