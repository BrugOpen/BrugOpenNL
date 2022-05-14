<?php
namespace BrugOpen\Datex\Model;

class MultiLingualString
{

    /**
     *
     * @var string[]
     */
    private $valuesByLang;

    /**
     *
     * @return multitype:string
     */
    public function getValuesByLang()
    {
        return $this->valuesByLang;
    }

    /**
     *
     * @param multitype:string $valuesByLang
     */
    public function setValuesByLang($valuesByLang)
    {
        $this->valuesByLang = $valuesByLang;
    }
}
