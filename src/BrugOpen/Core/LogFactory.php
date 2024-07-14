<?php
namespace BrugOpen\Core;

use Psr\Log\LoggerInterface;

interface LogFactory
{

    /**
     *
     * @param string $name
     * @param Context $context
     * @return LoggerInterface
     */
    public function createLog($name, $context);
}
