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
     * @return string[]
     */
    public function getValuesByLang()
    {
        return $this->valuesByLang;
    }

    /**
     *
     * @param string[] $valuesByLang
     */
    public function setValuesByLang($valuesByLang)
    {
        $this->valuesByLang = $valuesByLang;
    }
}
