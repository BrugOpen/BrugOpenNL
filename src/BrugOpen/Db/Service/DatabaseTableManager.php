<?php

namespace BrugOpen\Db\Service;

use BrugOpen\Db\Model\Criterium;
use BrugOpen\Db\Model\CriteriumFieldComparison;

class DatabaseTableManager implements TableManager
{

    const COLUMN_INT = 1;

    const COLUMN_STR = 2;

    const COLUMN_DATE = 4;

    const COLUMN_TIME = 8;

    const COLUMN_BOOL = 16;

    const COLUMN_BLOB = 64;

    const COLUMN_NOTNULL = 128;

    /**
     *
     * @var \PDO
     */
    private $connection;

    /**
     *
     */
    private $columnDefinitions = array();

    private $dialect = 'mysql';

    /**
     *
     * @param \PDO $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\RecordFinder::findRecords()
     */
    public function findRecords($table, $criteria = null, $fields = null, $orders = null, $maxResults = null, $offset = null)
    {
        $records = null;

        $statementParameters = $this->createSelectStatementParameters($table, $criteria, $fields, $orders, $maxResults, $offset);

        if ($table == 'bo_operation') {

            // var_dump($statementParameters);exit;
        }

        if ($table == 'bo_bridge_passage') {

            // var_dump($statementParameters);exit;

        }

        if ($statementParameters) {

            $sql = $statementParameters[0];
            $bindParams = $statementParameters[1];

            $stmt = $this->connection->prepare($sql);

            if ($bindParams) {

                foreach ($bindParams as $key => $value) {

                    if (is_array($value)) {

                        $stmt->bindValue($key, $value[0], $value[1]);
                    } else {

                        $stmt->bindValue($key, $value);
                    }
                }
            }

            if ($stmt->execute()) {

                $records = array();

                while ($record = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                    $values = $record;

                    if (array_key_exists($table, $this->columnDefinitions)) {

                        foreach ($this->columnDefinitions[$table] as $column => $columnDefinition) {

                            if ($columnDefinition & self::COLUMN_DATE) {

                                if (array_key_exists($column, $record)) {

                                    if (preg_match('/^[1-9]+[0-9]*$/', $record[$column])) {

                                        $values[$column] = new \DateTime('@' . $record[$column]);
                                    }
                                }
                            }
                        }
                    }

                    $records[] = $values;
                }
            }
        }

        return $records;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\TableManager::insertRecords()
     */
    public function insertRecords($table, $records)
    {

        // TODO combine into single query
        foreach ($records as $record) {

            $this->insertRecord($table, $record);
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\TableManager::updateRecords()
     */
    public function updateRecords($table, $values, $criteria = null)
    {
        $res = null;

        if ($table && $values) {

            $statementParameters = $this->createUpdateStatementParameters($table, $values, $criteria);

            if ($statementParameters) {

                $sql = $statementParameters[0];
                $bindParams = $statementParameters[1];

                $stmt = $this->connection->prepare($sql);

                if ($bindParams) {

                    foreach ($bindParams as $key => $value) {

                        if (is_array($value)) {

                            $stmt->bindValue($key, $value[0], $value[1]);
                        } else {

                            $stmt->bindValue($key, $value);
                        }
                    }
                }

                $res = $stmt->execute();
            }
        }

        return $res;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\TableManager::removeRecords()
     */
    public function deleteRecords($table, $criteria = null, $limit = null)
    {
        $res = null;

        if ($table) {

            $sql = 'DELETE FROM ' . $table;

            if ($criteria) {

                $whereParts = array();

                $i = 0;

                foreach ($criteria as $name => $value) {

                    if (is_array($value)) {

                        $paramNames = array();

                        for ($j = 0; $j < count($value); $j++) {

                            $paramNames[] = ':c' . $i . '_' . $j;
                        }

                        $whereParts[] = '(' . $name . ' IN (' . implode(',', $paramNames) . '))';
                    } else {

                        $whereParts[] = '(' . $name . ' = :c' . $i . ')';
                    }

                    $i++;
                }

                $whereClause = implode(' AND ', $whereParts);

                $sql .= ' WHERE ' . $whereClause;
            }

            $stmt = $this->connection->prepare($sql);

            if ($criteria) {

                $i = 0;

                foreach ($criteria as &$value) {

                    if (is_array($value)) {

                        $j = 0;
                        foreach ($value as $val) {

                            if (is_int($val)) {

                                $stmt->bindValue('c' . $i . '_' . $j, $val, \PDO::PARAM_INT);
                            } else {

                                $stmt->bindValue('c' . $i . '_' . $j, $val);
                            }

                            $j++;
                        }
                    } else if (is_int($value)) {

                        $stmt->bindValue('c' . $i, $value, \PDO::PARAM_INT);
                    } else {

                        $stmt->bindValue('c' . $i, $value);
                    }

                    $i++;
                }
            }

            $res = $stmt->execute();
        }

        return $res;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\TableManager::insertRecord()
     */
    public function insertRecord($table, $record)
    {

        $res = null;

        $fields = array_keys($record);

        $insertStatementParameters = $this->createInsertStatementParameters($table, $fields, array($record));

        if ($insertStatementParameters) {

            $sql = $insertStatementParameters[0];
            $bindParams = $insertStatementParameters[1];

            $stmt = $this->connection->prepare($sql);

            if ($bindParams) {

                foreach ($bindParams as $key => $value) {

                    $stmt->bindValue($key, $value);
                }
            }

            if ($stmt->execute()) {

                $lastInsertId = $this->connection->lastInsertId();

                if ($lastInsertId) {

                    $res = $lastInsertId;
                } else {

                    $res = true;
                }
            } else {

                $res = false;
            }
        }

        return $res;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\RecordFinder::findRecord()
     */
    public function findRecord($table, $parameters, $fields = null)
    {
        $record = array();

        if ($records = $this->findRecords($table, $parameters, $fields)) {

            $record = array_shift($records);
        }

        return $record;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\RecordFinder::countRecords()
     */
    public function countRecords($table, $parameters = null)
    {

        // TODO implement this

    }

    /**
     * @param string $table
     * @param array $columnDefinitions
     */
    public function setColumnDefinitions($table, $columnDefinitions)
    {

        $this->columnDefinitions[$table] = $columnDefinitions;
    }

    public function createSelectStatementParameters($table, $criteria = null, $fields = null, $orders = null, $maxResults = null, $offset = null)
    {

        $parameters = array();

        $bindParams = array();

        if (array_key_exists($table, $this->columnDefinitions)) {

            $fieldParts = array();

            foreach ($this->columnDefinitions[$table] as $column => $columnDefinition) {

                // TODO add db-brand-specific quotes

                if ($columnDefinition & self::COLUMN_DATE) {

                    $fieldParts[] = 'UNIX_TIMESTAMP(' . $column . ') AS ' . $column;
                } else {

                    $fieldParts[] = $column;
                }
            }

            $selectPart = implode(', ', $fieldParts);
        } else {

            $selectPart = '*';

            if ($fields) {

                $fieldParts = array();

                foreach ($fields as $field) {

                    // TODO add db-brand-specific quotes
                    $fieldParts[] = $field;
                }

                $selectPart = implode(', ', $fieldParts);
            }
        }

        $sql = 'SELECT ' . $selectPart . ' FROM ' . $table;

        if ($criteria) {

            $whereParts = array();

            $i = 0;

            foreach ($criteria as $name => $value) {

                $field = $name;
                $operator = '=';
                $valueExpression = ':c' . $i;

                if (is_object($value) && ($value instanceof CriteriumFieldComparison)) {

                    $field = $value->getField();
                    $operator = Criterium::getOperatorWhereClausePart($value->getOperator());

                    $expression = $value->getExpression();
                    if (is_object($expression) && ($expression instanceof \DateTime)) {

                        $valueExpression = 'FROM_UNIXTIME(:c' . $i . ')';
                    }
                } else if (is_object($value) && ($value instanceof \DateTime)) {

                    $valueExpression = 'FROM_UNIXTIME(:c' . $i . ')';
                } else if (is_array($value)) {

                    $operator = 'IN';

                    $valueExpressionParts = array();

                    for ($j = 0; $j < count($value); $j++) {

                        $valueExpressionParts[] = ':c' . $i;

                        if ($j != (count($value) - 1)) {
                            $i++;
                        }
                    }

                    $valueExpression = '(' . implode(',', $valueExpressionParts) . ')';
                }

                $whereParts[] = '(' . $field . ' ' . $operator . ' ' . $valueExpression . ')';

                $i++;
            }

            $whereClause = implode(' AND ', $whereParts);

            $sql .= ' WHERE ' . $whereClause;
        }

        if ($orders) {

            $orderByParts = array();

            foreach ($orders as $order) {

                if (is_string($order)) {

                    $orderByParts[] = $order;
                } else if (is_array($order)) {

                    $field = $order[0];
                    $direction = count($order) > 1 ? $order[1] : '';

                    $orderByParts[] = $field . rtrim(' ' . $direction);
                }
            }

            if ($orderByParts) {

                $sql .= ' ORDER BY ' . implode(', ', $orderByParts);
            }
        }

        if ($maxResults) {

            $sql .= ' LIMIT ' . ($offset ? $offset . ',' : '') . $maxResults;
        }

        if ($criteria) {

            $i = 0;

            foreach ($criteria as $value) {

                if (is_object($value) && ($value instanceof CriteriumFieldComparison)) {

                    $value = $value->getExpression();

                    if (is_object($value) && ($value instanceof \DateTime)) {

                        $value = $value->getTimestamp();
                    }

                    $bindParams['c' . $i] = $value;
                } else if (is_object($value) && ($value instanceof \DateTime)) {

                    $value = $value->getTimestamp();

                    $bindParams['c' . $i] = $value;
                } else if (is_array($value)) {

                    $values = array_values($value);

                    for ($j = 0; $j < count($value); $j++) {

                        $bindParams['c' . $i] = $values[$j];

                        if ($j != (count($value) - 1)) {
                            $i++;
                        }
                    }
                } else {
                    $bindParams['c' . $i] = $value;
                }

                $i++;
            }
        }

        $parameters[] = $sql;
        $parameters[] = $bindParams;

        // if ($table == 'bo_bridge_passage') {
        //     var_dump($parameters);exit;
        // }

        return $parameters;
    }

    public function createInsertStatementParameters($table, $fields, $records)
    {

        $parameters = array();

        $bindParams = array();

        $sql = 'INSERT INTO ' . $table . ' ';

        $fieldParts = array();

        foreach ($fields as $field) {

            // TODO escape field name
            $fieldParts[] = $field;
        }

        $sql .= '(' . implode(', ', $fieldParts) . ') VALUES ';

        $valueParts = array();

        $i = 0;

        foreach ($records as $record) {

            $values = array();

            foreach ($record as $value) {

                if (($value !== null) && is_object($value) && ($value instanceof \DateTime)) {

                    $values[] = 'FROM_UNIXTIME(:v' . $i . ')';
                    $bindParams['v' . $i] = $value->getTimestamp();
                } else {

                    $values[] = ':v' . $i;
                    $bindParams['v' . $i] = $value;
                }

                $i++;
            }

            $valueParts[] = '(' . implode(', ', $values) . ')';
        }

        $sql .= implode(', ', $valueParts);

        $parameters[] = $sql;
        $parameters[] = $bindParams;

        return $parameters;
    }

    /**
     * @param string $table
     * @param mixed[] $values
     * @param mixed[] $criteria
     */
    public function createUpdateStatementParameters($table, $values, $criteria = null)
    {
        $parameters = array();

        $bindParams = array();

        $sql = 'UPDATE ' . $table . ' SET ';

        $i = 0;

        foreach ($values as $name => $value) {

            if (preg_match('/^[0-9]+$/', $name)) {

                trigger_error('Unexpected numeric column name \'' . $name . '\' to update ' . $table, E_USER_WARNING);
                return;
            } else {

                // TODO escape field name

                if (($value !== null) && is_object($value) && ($value instanceof \DateTime)) {

                    $setParts[] = $name . ' = FROM_UNIXTIME(:v' . $i . ')';
                } else {

                    $setParts[] = $name . ' = :v' . $i;
                }
            }

            $i++;
        }

        $sql .= implode(', ', $setParts);

        if ($criteria) {

            $whereParts = array();

            $i = 0;

            foreach ($criteria as $name => $value) {

                if (is_array($value)) {

                    $paramNames = array();

                    for ($j = 0; $j < count($value); $j++) {

                        $paramNames[] = ':c' . $i . '_' . $j;
                    }

                    $whereParts[] = '(' . $name . ' IN (' . implode(',', $paramNames) . '))';
                } else {

                    $whereParts[] = '(' . $name . ' = :c' . $i . ')';
                }

                $i++;
            }

            $whereClause = implode(' AND ', $whereParts);

            $sql .= ' WHERE ' . $whereClause;
        }

        $i = 0;

        foreach ($values as $value) {

            if (is_int($value) || is_float($value)) {

                $bindParams['v' . $i] = array($value, \PDO::PARAM_INT);
            } else if (($value !== null) && is_object($value) && ($value instanceof \DateTime)) {

                $bindParams['v' . $i] = $value->getTimestamp();
            } else {

                $bindParams['v' . $i] = $value;
            }

            $i++;
        }

        if ($criteria) {

            $i = 0;

            foreach ($criteria as $value) {

                if (is_int($value) || is_float($value)) {

                    $bindParams['c' . $i] = array($value, \PDO::PARAM_INT);
                } else {

                    $bindParams['c' . $i] = $value;
                }

                $i++;
            }
        }

        $parameters[] = $sql;
        $parameters[] = $bindParams;

        return $parameters;
    }
}
