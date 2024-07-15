<?php

namespace BrugOpen\Db\Model;

class Criterium
{

    const OPERATOR_IN = 1;
    const OPERATOR_NOT_IN = 129;
    const OPERATOR_IS = 2;
    const OPERATOR_EQUALS = 2;
    const OPERATOR_IS_NOT = 130;
    const OPERATOR_NOT_EQUALS = 130;
    const OPERATOR_LIKE = 3;
    const OPERATOR_NOT_LIKE = 131;
    const OPERATOR_BETWEEN = 4;
    const OPERATOR_NOT_BETWEEN = 132;
    const OPERATOR_NOT = 128;
    const OPERATOR_LT = 5;
    const OPERATOR_LE = 7;
    const OPERATOR_GT = 6;
    const OPERATOR_GE = 8;

    /**
     * Returns the operator part to use in the where clause
     * @access public
     * @param int $operator The operator to create a where clause part for
     * @return string The operator part of the where clause
     * @example 'IN'
     * @example '='
     * @example 'NOT BETWEEN'
     */
    public static function getOperatorWhereClausePart($operator)
    {
        switch ($operator) {
            case Criterium::OPERATOR_IN:
                return 'IN';
            case Criterium::OPERATOR_IN + Criterium::OPERATOR_NOT:
                return 'NOT IN';
            case Criterium::OPERATOR_LIKE:
                return 'LIKE';
            case Criterium::OPERATOR_LIKE + Criterium::OPERATOR_NOT:
                return 'NOT LIKE';
            case Criterium::OPERATOR_IS:
                return '=';
            case Criterium::OPERATOR_IS + Criterium::OPERATOR_NOT:
                return '<>';
            case Criterium::OPERATOR_BETWEEN:
                return 'BETWEEN';
            case Criterium::OPERATOR_BETWEEN + Criterium::OPERATOR_NOT:
                return 'NOT BETWEEN';
			case Criterium::OPERATOR_LT:
				return '<';
            case Criterium::OPERATOR_GT:
            	return '>';
            case Criterium::OPERATOR_LE:
				return '<=';
            case Criterium::OPERATOR_GE:
            	return '>=';
        }

    }

}
