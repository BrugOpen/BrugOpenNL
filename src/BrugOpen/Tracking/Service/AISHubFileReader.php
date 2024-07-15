<?php

namespace BrugOpen\Tracking\Service;

use BrugOpen\Geo\Model\LatLng;
use BrugOpen\Tracking\Model\AISRecord;
use BrugOpen\Tracking\Model\VesselDimensions;

class AISHubFileReader
{

    /**
     * @var resource
     */
    private $fp;

    /**
     * @var string[]
     */
    private $headers;

    /**
     * @var string
     */
    private $line;

    /**
     * @var AISRecord
     */
    private $currentData;

    /**
     * @param string $file
     */
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
            $this->nextRecord();

        }

    }

    /**
     * @return AISRecord|null
     */
    public function current()
    {

        return $this->currentData;

    }

    /**
     * @return AISRecord|null
     */
    public function nextRecord()
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

                    $record = new AISRecord();
                    $record->setMmsi($values['MMSI']);
                    $record->setTimestamp((int)$values['TSTAMP']);
                    $record->setLocation(new LatLng($values['LATITUDE'] / 600000, $values['LONGITUDE'] / 600000));

                    $record->setHeading(($values['HEADING'] != '511') ? (int)($values['HEADING']) : null);
                    $record->setSpeed(($values['SOG'] != 1024) ? ($values['SOG']) / 10 : null);
                    $record->setCourse(($values['COG'] != 3600) ? ($values['COG']) / 10 : null);

                    $record->setName($values['NAME']);
                    $record->setCallsign($values['CALLSIGN']);

                    $record->setDimensions(new VesselDimensions($values['A'], $values['B'], $values['C'], $values['D']));
                    $record->setType(($values['TYPE'] != 0) ? (int)$values['TYPE'] : null);

                    $record->setDestination($values['DEST']);
                    $record->setEta(($values['ETA'] != 0) ? $values['ETA'] : null);

                    $this->currentData = $record;

                }

            }

        }

        $this->line++;

        return $this->currentData;

    }

    /**
     * @return void
     */
    public function rewind()
    {

        $this->line = -1;

    }

    /**
     * @return void
     */
    function __destruct()
    {
        $this->close();
    }

    /**
     * @return void
     */
    function close()
    {

        if ($this->fp) {

            @fclose($this->fp);

        }

    }

}
