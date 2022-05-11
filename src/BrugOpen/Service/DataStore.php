<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Context;

class DataStore
{

    private $db;

    private $queryTimes;

    /**
     * @param Context $context
     */
    public function initialize($context)
    {

        $configParams = $context->getConfig()->getParams();

        $host = $configParams['database.host'];
        $user = $configParams['database.user'];
        $pass = $configParams['database.pass'];
        $name = $configParams['database.name'];

        $mysqli = new \mysqli($host, $user, $pass, $name);

        $this->db = $mysqli;

        // make sure we're talking utf-8 to the (mysql) database
        $query = 'SET CHARACTER SET utf8';
        $this->db->query($query);

        $query = 'SET NAMES utf8';
        $this->db->query($query);

        $this->queryTimes = array();

    }

    public function getLastError()
    {
        return $this->db->error;
    }

    public function insertRecord($table, $values)
    {

        if (sizeof($values)) {

            $valueparts = array();
            foreach ($values as $value) {
                if (is_int($value)) {
                    $valueparts[] = $value;
                } else if (is_a($value, 'DateTime')) {
                    $valueparts[] = "FROM_UNIXTIME(" . $value->getTimestamp() . ")";
                } else if (is_null($value)) {
                    $valueparts[] = "NULL";
                } else {
                    $valueparts[] = "'" . addslashes($value) . "'";
                }
            }

            $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', array_keys($values)) . ') VALUES (' . implode(', ', $valueparts) . ')';

            if ($stmt = $this->db->prepare($sql)) {

                // TODO add timer
                if ($stmt->execute()) {
                	if ($id = $this->db->insert_id) {
                		return $id;
                	} else {
                		return true;
                	}
                } else {
                	return false;
                }

            }

        }

    }

    public function updateTable($table, $keys, $values)
    {

        if (sizeof($values)) {

            $updateparts = array();
            foreach ($values as $field => $value) {

                if (is_int($value)) {
                    $updateparts[] = $field . " = " . $value;
                } else if (is_a($value, 'DateTime')) {
                    $updateparts[] = $field . " = FROM_UNIXTIME(" . $value->getTimestamp() . ")";
                } else {
                    $updateparts[] = $field . " = '" . addslashes($value) . "'";
                }

            }

            $keyparts = array();
            foreach ($keys as $field => $value) {
                $keypart = $field . ' = ';

                if (is_numeric($value)) {
                    $keypart .= $value;
                } else {
                    $keypart .= "'" . addslashes($value) . "'";
                }

                $keyparts[] = $keypart;
            }

            $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $updateparts);

            if (sizeof($keyparts) > 0) {

                $sql .= ' WHERE (' . implode(') AND (', $keyparts) . ')';

            }

            if ($stmt = $this->db->prepare($sql)) {

                return $stmt->execute();
            }

        }

    }


    public function findRecord($table, $keys)
    {

        $sql = 'SELECT * FROM `' . $table . '`';

        if ($keys) {
            $keyparts = array();
            foreach ($keys as $field => $value) {
                $keypart = $field . ' = ';

                if (is_numeric($value)) {
                    $keypart .= $value;
                } else {
                    $keypart .= "'" . addslashes($value) . "'";
                }

                $keyparts[] = $keypart;
            }

            if (sizeof($keyparts) > 0) {

                $sql .= ' WHERE (' . implode(') AND (', $keyparts) . ')';

            }
        }

        if ($res = $this->executeQuery($sql)) {
            if ($row = $res->fetch_assoc()) {
                return $row;
            }
        }

    }

    public function findRecords($table, $keys, $orders = null)
    {
        $rows = array();

        $sql = 'SELECT * FROM `' . $table . '`';

        if ($keys) {
            $keyparts = array();
            foreach ($keys as $field => $value) {
                $keypart = $field . ' = ';

                if (is_numeric($value)) {
                    $keypart .= $value;
                } else {
                    $keypart .= "'" . addslashes($value) . "'";
                }

                $keyparts[] = $keypart;
            }

            if (sizeof($keyparts) > 0) {

                $sql .= ' WHERE (' . implode(') AND (', $keyparts) . ')';

            }
        }

        if ($orders) {
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }

        if ($result = $this->db->query($sql)) {
            while($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;

    }

    public function prepareStatement($sql)
    {
        if ($stmt = $this->db->prepare($sql)) {
            return $stmt;
        }
    }

    public function executeQuery($sql)
    {
        $timeStart = microtime(true);

        if ($result = $this->db->query($sql)) {

            $timeStop = microtime(true);

            $this->queryTimes[] = ($timeStop - $timeStart);

            return $result;
        }

    }

    public function datetimeToTimestamp($str)
    {
        $timestamp = null;

        $sql = "SELECT UNIX_TIMESTAMP('" . addslashes($str) . "') AS timestamp";

        if ($res = $this->executeQuery($sql)) {
            if ($row = $res->fetch_assoc()) {
                $timestamp = $row['timestamp'];
            }
        }

        return $timestamp;

    }

    public function getQueryCount()
    {
        return sizeof($this->queryTimes);
    }

    public function getQueryTime()
    {
        return array_sum($this->queryTimes);
    }

}
