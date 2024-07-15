<?php

namespace BrugOpen\Service;

class AISHub_FileReader implements \Iterator
{

    private $file;

    private $fp;

    private $headers;

    private $line;

    private $currentData;

    public function __construct($file)
    {

        if (substr($file, -3) == '.gz') {

            if ($fp = gzopen($file, 'r')) {

                $this->fp = $fp;

            }

        } else {

            if ($fp = fopen($file, 'r')) {

                $this->fp = $fp;

            }

        }

        if ($this->fp) {

            if ($headers = fgetcsv($this->fp, 4096)) {

                $this->headers = $headers;

            }

            $this->rewind();
            $this->next();

        }

    }

    public function current()
    {

        return $this->currentData;

    }

    public function key()
    {

        return $this->line;

    }

    public function next()
    {

        $this->currentData = null;

        if (($this->fp) && ($this->headers)) {

            $headers = $this->headers;

            if (!feof($this->fp)) {

                if ($fields = fgetcsv($this->fp, 4096)) {

                    $values = array();

                    foreach ($headers as $j => $fieldname) {

                        $value = null;
                        if (array_key_exists($j, $fields)) {
                            $value = $fields[$j];
                        }

                        $values[$fieldname] = $value;
                    }

                    $record = array();
                    $record['mmsi'] = $values['MMSI'];
                    $record['timestamp'] = $values['TSTAMP'];
                    $record['lat'] = $values['LATITUDE'] / 600000;
                    $record['lon'] = $values['LONGITUDE'] / 600000;

                    $record['heading'] = ($values['HEADING'] != '511') ? ($values['HEADING']) : null;
                    $record['speed'] = ($values['SOG'] != 1024) ? ($values['SOG']) / 10 : null;
                    $record['course'] = ($values['COG'] != 3600) ? ($values['COG']) / 10 : null;

                    $record['name'] = $values['NAME'];
                    $record['callsign'] = $values['CALLSIGN'];

                    $record['dimensions'] = $values['A'] . ',' . $values['B'] . ',' . $values['C'] . ',' . $values['D'];
                    $record['type'] = ($values['TYPE'] != 0) ? $values['TYPE'] : null;

                    $record['dest'] = $values['DEST'];
                    $record['eta'] = ($values['ETA'] != 0) ? $values['ETA'] : null;

                    $this->currentData = $record;

                }

            }

        }

        $this->line++;

    }

    public function rewind()
    {

        $this->line = -1;

    }

    public function valid()
    {

        return ($this->currentData != null);

    }

    function __destruct()
    {
        $this->close();
    }

    function close()
    {

        if ($this->fp) {

            @fclose($this->fp);

        }

    }

}
