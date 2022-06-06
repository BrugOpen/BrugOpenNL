<?php
namespace BrugOpen\Db\Service;

class MemoryTableManager implements TableManager
{

    /**
     *
     * @var array[]
     */
    private $recordsByTable = array();

    /**
     *
     * @var int[]
     */
    private $autoIncrementByTable = array();

    /**
     * Enable auto increment for given table
     *
     * @param string $tableName
     * @param string $idColumn
     * @param string $nextId
     *            The next auto increment value for this class
     */
    public function setAutoIncrement($tableName, $idColumn, $nextId)
    {
        $autoIncrement = array();
        $autoIncrement[] = $idColumn;
        $autoIncrement[] = $nextId;
        $this->autoIncrementByTable[$tableName] = $autoIncrement;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\RecordFinder::findRecords()
     */
    public function findRecords($table, $criteria = null, $fields = null, $orders = null, $maxResults = null, $offset = null)
    {
        $records = $this->findMatchingItems($table, $criteria, $fields, $orders, $maxResults, $offset);

        return $records;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\TableManager::insertRecords()
     */
    public function insertRecords($table, $records)
    {
        $res = null;

        foreach ($records as $record) {

            $res = $this->insertRecord($table, $record);
        }

        return $res;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\TableManager::updateRecords()
     */
    public function updateRecords($table, $values, $criteria = null)
    {
        if (array_key_exists($table, $this->recordsByTable)) {

            foreach ($this->recordsByTable[$table] as $i => $record) {

                $itemMatches = true;

                if (is_array($criteria) && (sizeof($criteria) > 0)) {

                    foreach ($criteria as $criteriumName => $criteriumValue) {

                        if (array_key_exists($criteriumName, $record)) {

                            if (is_array($criteriumValue)) {

                                if (! in_array($record[$criteriumName], $criteriumValue)) {

                                    $itemMatches = false;
                                    break;
                                }
                            } else {

                                if ($criteriumValue === null) {

                                    if ($record[$criteriumName] !== null) {

                                        $itemMatches = false;
                                        break;
                                    }
                                } else if (! ($record[$criteriumName] == $criteriumValue)) {

                                    $itemMatches = false;
                                    break;
                                }
                            }
                        } else {

                            if ($criteriumValue !== null) {

                                $itemMatches = false;
                            }
                        }
                    }
                }

                if ($itemMatches) {

                    foreach ($values as $key => $value) {

                        $this->recordsByTable[$table][$i][$key] = $value;
                    }
                }
            }
        }
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\TableManager::deleteRecords()
     */
    public function deleteRecords($table, $criteria = null, $limit = null)
    {}

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\TableManager::insertRecord()
     */
    public function insertRecord($table, $record)
    {
        $res = null;

        if (array_key_exists($table, $this->autoIncrementByTable)) {

            $keyField = $this->autoIncrementByTable[$table][0];
            $nextId = $this->autoIncrementByTable[$table][1];

            if (array_key_exists($keyField, $record) && ($record[$keyField] > 0)) {

                $res = true;

                if ($record[$keyField] >= $nextId) {

                    $this->autoIncrementByTable[$table][1] = $record[$keyField] + 1;
                }
            } else {

                $record[$keyField] = $nextId;
                $this->autoIncrementByTable[$table][1] ++;
                $res = $nextId;
            }
        } else {

            $res = true;
        }

        $this->recordsByTable[$table][] = $record;

        return $res;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\RecordFinder::findRecord()
     */
    public function findRecord($table, $criteria, $fields = null)
    {
        $record = null;

        $records = $this->findRecords($table, $criteria, $fields, null, 1);

        if ($records) {

            $record = array_shift($records);
        }

        return $record;
    }

    /**
     *
     * {@inheritdoc}
     * @see \BrugOpen\Db\Service\RecordFinder::countRecords()
     */
    public function countRecords($table, $criteria = null)
    {
        $records = $this->findRecords($table, $criteria);
        $numRecords = count($records);
        return $numRecords;
    }

    /**
     *
     * @param string $table
     * @param array $parameters
     * @param string[] $fields
     * @param array $orders
     * @param int|NULL $maxItems
     * @param int|NULL $offset
     * @return array[]|NULL
     */
    public function findMatchingItems($table, $criteria = null, $fields = null, $orders = null, $maxItems = null, $offset = null)
    {
        $matchingItems = array();

        if (array_key_exists($table, $this->recordsByTable)) {

            foreach ($this->recordsByTable[$table] as $record) {

                $itemMatches = true;

                if (is_array($criteria) && (sizeof($criteria) > 0)) {

                    foreach ($criteria as $criteriumName => $criteriumValue) {

                        if (array_key_exists($criteriumName, $record)) {

                            if (is_array($criteriumValue)) {

                                if (! in_array($record[$criteriumName], $criteriumValue)) {

                                    $itemMatches = false;
                                    break;
                                }
                            } else {

                                if ($criteriumValue === null) {

                                    if ($record[$criteriumName] !== null) {

                                        $itemMatches = false;
                                        break;
                                    }
                                } else if (! ($record[$criteriumName] == $criteriumValue)) {

                                    $itemMatches = false;
                                    break;
                                }
                            }
                        } else {

                            if ($criteriumValue !== null) {

                                $itemMatches = false;
                            }
                        }
                    }
                }

                if ($itemMatches) {

                    $matchingItems[] = $record;

                    if (($maxItems != null) && (empty($orders))) {

                        if (sizeof($matchingItems) >= $maxItems) {
                            break;
                        }
                    }
                }
            }

            if (is_array($orders) && (sizeof($orders) > 0) && is_array($matchingItems) && (sizeof($matchingItems) > 1)) {

                $matchingItems = $this->getOrderedItems($matchingItems, $orders);

                if ($maxItems != null) {

                    // reduce items

                    $limitedItems = array();

                    $i = 0;

                    foreach ($matchingItems as $matchingItem) {

                        if (($offset == null) || ($i >= $offset)) {
                            $limitedItems[] = $matchingItem;
                        }

                        if (count($limitedItems) == $maxItems) {
                            break;
                        }

                        $i ++;
                    }

                    $matchingItems = $limitedItems;
                }
            }
        }

        return $matchingItems;
    }

    public function getOrderedItems($items, $orders)
    {
        $sortedItems = array();

        if (is_array($items) && ! empty($orders)) {
            $sortedItems = $items;

            $revOrders = array_reverse($orders);

            foreach ($revOrders as $order) {

                if (is_array($order) && (sizeof($order) > 0)) {
                    $fieldName = $order[0];

                    $asc = (isset($order[1]) && (strtoupper($order[1]) == 'DESC')) ? false : true;

                    $sortedItems = $this->getSortedItemsByField($sortedItems, $fieldName, $asc);
                } else if (is_string($order)) {
                    $fieldName = $order;

                    $asc = true;

                    $sortedItems = $this->getSortedItemsByField($sortedItems, $fieldName, $asc);
                }
            }
        }

        return $sortedItems;
    }

    public function getSortedItemsByField($items, $field, $asc = true)
    {
        $itemsByValue = array();
        $itemsWithoutValue = array();

        foreach ($items as $item) {

            $value = null;

            if (array_key_exists($field, $item)) {

                $value = $item[$field];

                if (is_object($value) && is_a('\\DateTime', $value)) {

                    $value = $value->getTimestamp();
                }
            }

            if (($value !== null) && ! is_object($value)) {

                $itemsByValue[$value][] = $item;
            } else {

                $itemsWithoutValue[] = $item;
            }
        }

        $sortedItems = array();

        if ($asc !== true) {

            krsort($itemsByValue);
        } else {

            ksort($itemsByValue);
        }

        if ($asc === false) {

            // items without value first

            $sortedItems = $itemsWithoutValue;
        }

        foreach (array_keys($itemsByValue) as $value) {

            foreach ($itemsByValue[$value] as $item) {

                $sortedItems[] = $item;
            }
        }

        if ($asc === true) {

            // items without value last

            foreach ($itemsWithoutValue as $item) {

                $sortedItems[] = $item;
            }
        }

        return $sortedItems;
    }
}
