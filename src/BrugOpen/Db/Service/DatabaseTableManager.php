<?php
namespace BrugOpen\Db\Service;

class DatabaseTableManager implements TableManager
{

    /**
     *
     * @var \PDO
     */
    private $connection;

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

        $selectPart = '*';

        if ($fields) {

            $fieldParts = array();

            foreach ($fields as $field) {

                // TODO add db-brand-specific quotes
                $fieldParts[] = $field;
            }

            $selectPart = implode(', ', $fieldParts);
        }

        $sql = 'SELECT ' . $selectPart . ' FROM ' . $table;

        if ($criteria) {

            $whereParts = array();

            $i = 0;

            foreach ($criteria as $name => $value) {

                $whereParts[] = '(' . $name . ' = :c' . $i . ')';

                $i ++;
            }

            $whereClause = implode(' AND ', $whereParts);

            $sql .= ' WHERE ' . $whereClause;
        }

        $stmt = $this->connection->prepare($sql);

        if ($criteria) {

            $i = 0;

            foreach ($criteria as $value) {

                $stmt->bindValue('c' . $i, $value);

                $i ++;
            }
        }

        if ($stmt->execute()) {

            $records = array();

            while ($record = $stmt->fetch(\PDO::FETCH_ASSOC)) {

                $records[] = $record;
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

            $sql = 'UPDATE ' . $table . ' SET ';

            $i = 0;

            foreach (array_keys($values) as $name) {

                if (preg_match('/^[0-9]+$/', $name)) {

                    trigger_error('Unexpected numeric column name \'' . $name . '\' to update ' . $table, E_USER_WARNING);
                    return;
                } else {

                    // TODO escape field name
                    $setParts[] = $name . ' = :v' . $i;
                }

                $i ++;
            }

            $sql .= implode(', ', $setParts);

            if ($criteria) {

                $whereParts = array();

                $i = 0;

                foreach ($criteria as $name => $value) {

                    if (is_array($value)) {

                        $paramNames = array();

                        for ($j = 0; $j < count($value); $j ++) {

                            $paramNames[] = ':c' . $i . '_' . $j;
                        }

                        $whereParts[] = '(' . $name . ' IN (' . implode(',', $paramNames) . '))';
                    } else {

                        $whereParts[] = '(' . $name . ' = :c' . $i . ')';
                    }

                    $i ++;
                }

                $whereClause = implode(' AND ', $whereParts);

                $sql .= ' WHERE ' . $whereClause;
            }

            $stmt = $this->connection->prepare($sql);

            $i = 0;

            foreach ($values as $value) {

                if (is_int($value) || is_float($value)) {

                    $stmt->bindValue('v' . $i, $value, \PDO::PARAM_INT);
                } else {

                    $stmt->bindValue('v' . $i, $value);
                }

                $i ++;
            }

            if ($criteria) {

                $i = 0;

                foreach ($criteria as $value) {

                    $stmt->bindValue('c' . $i, $value);

                    $i ++;
                }
            }

            $res = $stmt->execute();
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

                        for ($j = 0; $j < count($value); $j ++) {

                            $paramNames[] = ':c' . $i . '_' . $j;
                        }

                        $whereParts[] = '(' . $name . ' IN (' . implode(',', $paramNames) . '))';
                    } else {

                        $whereParts[] = '(' . $name . ' = :c' . $i . ')';
                    }

                    $i ++;
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

                            $j ++;
                        }
                    } else if (is_int($value)) {

                        $stmt->bindValue('c' . $i, $value, \PDO::PARAM_INT);
                    } else {

                        $stmt->bindValue('c' . $i, $value);
                    }

                    $i ++;
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

        if ($record) {

            $sql = 'INSERT INTO ' . $table . ' ';

            $names = array_keys($record);
            $values = array_values($record);

            $fieldParts = array();

            foreach ($names as $name) {

                // TODO escape field name
                $fieldParts[] = $name;
            }

            $sql .= '(' . implode(',', $fieldParts) . ') VALUES ';

            $fieldParts = array();

            foreach ($values as $i => $value) {

                $fieldParts[] = ':v' . $i;
            }

            $sql .= '(' . implode(',', $fieldParts) . ')';

            $stmt = $this->connection->prepare($sql);

            if ($record) {

                foreach ($values as $i => $value) {

                    $stmt->bindValue('v' . $i, $value);
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
    {}
}
