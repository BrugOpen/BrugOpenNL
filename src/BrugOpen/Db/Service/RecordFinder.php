<?php

namespace Grip\Vwm\Db\Service;

interface RecordFinder
{

    /**
     *
     * @param string $table
     * @param array|NULL $criteria
     * @param array|NULL $fields
     * @return mixed[]|NULL
     */
    public function findRecord($table, $criteria, $fields = null);

    /**
     *
     * @param string $table
     * @param array|NULL $criteria
     * @param array|NULL $fields
     * @param array|NULL $orders
     * @param int|NULL $maxResults
     * @param int|NULL $offset
     * @return object[]|array[]|NULL
     */
    public function findRecords($table, $criteria = null, $fields = null, $orders = null, $maxResults = null, $offset = null);

    /**
     *
     * @param string $table
     * @param array|NULL $criteria
     * @return int
     */
    public function countRecords($table, $criteria = null);

}
