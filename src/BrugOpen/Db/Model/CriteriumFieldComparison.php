<?php

namespace BrugOpen\Db\Model;

class CriteriumFieldComparison extends Criterium
{

    /**
     * @var string
     */
    private $field;

    /**
     * @var int
     */
    private $operator;

    /**
     * @var mixed
     */
    private $expression;

    public function __construct($field, $operator, $expression)
    {
        $this->field = $field;
        $this->operator = $operator;
        $this->expression = $expression;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return int
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getExpression()
    {
        return $this->expression;
    }

}
