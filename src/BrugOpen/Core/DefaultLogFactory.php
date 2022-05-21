<?php
namespace BrugOpen\Core;

class DefaultLogFactory implements LogFactory
{

    public function createLog($name)
    {
        // create a log channel
        $log = new \Monolog\Logger($name);
        $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::INFO));
        return $log;
    }
}
