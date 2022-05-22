<?php

namespace BrugOpen\Db\Service;

interface TableManager extends RecordFinder
{

    /**
     *
     * @param string $table
     * @param array $record
     */
    public function insertRecord($table, $record);

    /**
     *
     * @param string $table
     * @param array $records
     */
    public function insertRecords($table, $records);

    /**
     *
     * @param string $table
     * @param mixed[] $values
     * @param mixed[] $criteria
     */
    public function updateRecords($table, $values, $criteria);

    /**
     *
     * @param string $table
     * @param mixed[] $criteria
     */
    public function deleteRecords($table, $criteria = null, $limit = null);

}
