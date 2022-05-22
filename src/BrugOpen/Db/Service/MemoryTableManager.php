<?php

namespace BrugOpen\Db\Service;

class MemoryTableManager implements TableManager
{

    /**
     *
     * @var int[]
     */
    private $autoIncrementByTable;

    /**
     * Enable auto increment for given table
     *
     * @param string $tableName
     * @param string $idColumn
     * @param string $autoIncrement
     *            The next auto increment value for this class
     */
    public function setAutoIncrement($tableName, $idColumn, $autoIncrement)
    {
        $this->$autoIncrementByTable[$tableName] = array($idColumn, $autoIncrement);
    }

    /**
     *
     * {@inheritDoc}
     * @see \BrugOpen\Db\Service\RecordFinder::findRecords()
     */
    public function findRecords($table, $criteria = null, $fields = null, $orders = null, $maxResults = null, $offset = null)
    {}

    /**
     *
     * {@inheritDoc}
     * @see \BrugOpen\Db\Service\TableManager::insertRecords()
     */
    public function insertRecords($table, $records)
    {}

    /**
     *
     * {@inheritDoc}
     * @see \BrugOpen\Db\Service\TableManager::updateRecords()
     */
    public function updateRecords($table, $values, $criteria)
    {}

    /**
     *
     * {@inheritDoc}
     * @see \BrugOpen\Db\Service\TableManager::deleteRecords()
     */
    public function deleteRecords($table, $criteria = null, $limit = null)
    {}

    /**
     *
     * {@inheritDoc}
     * @see \BrugOpen\Db\Service\TableManager::insertRecord()
     */
    public function insertRecord($table, $record)
    {}

    /**
     *
     * {@inheritDoc}
     * @see \BrugOpen\Db\Service\RecordFinder::findRecord()
     */
     public function findRecord($table, $criteria, $fields = null)
    {}

    /**
     *
     * {@inheritDoc}
     * @see \BrugOpen\Db\Service\RecordFinder::countRecords()
     */
    public function countRecords($table, $criteria = null)
    {}

}
