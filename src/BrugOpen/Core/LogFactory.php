<?php
namespace BrugOpen\Core;

use Psr\Log\LoggerInterface;

interface LogFactory
{

    /**
     *
     * @param string $name
     * @return LoggerInterface
     */
    public function createLog($name);
}
