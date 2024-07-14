<?php

namespace BrugOpen\Core;

class DefaultLogFactory implements LogFactory
{

    public function createLog($name, $context)
    {

        $dottedName = str_replace('\\', '.', $name);
        $lowerLogName = strtolower($dottedName);

        // create a log channel
        $log = new \Monolog\Logger($dottedName);

        $level = \Monolog\Logger::INFO;

        $levelsByPath = array();

        $configParams = $context->getConfig()->getParams();

        foreach ($configParams as $paramName => $paramValue) {

            if ($paramName == 'logger.level') {

                if (is_array($paramValue)) {

                    foreach ($paramValue as $path => $level) {

                        $levelsByPath[$path] = $level;
                    }
                }
            }
        }

        $configuredLevel = null;

        if (array_key_exists($lowerLogName, $levelsByPath)) {

            $configuredLevel = $levelsByPath[$lowerLogName];
        }

        if ($configuredLevel == null) {

            $testLogName = $lowerLogName;

            while (strpos($testLogName, '.') !== false) {

                $testLogName = substr($testLogName, 0, strrpos($testLogName, '.'));

                if (array_key_exists($testLogName, $levelsByPath)) {

                    $configuredLevel = $levelsByPath[$testLogName];
                    break;
                }
            }
        }

        if ($configuredLevel) {

            if ($configuredLevel == 'debug') {

                $level = \Monolog\Logger::DEBUG;
            } else if ($configuredLevel == 'info') {

                $level = \Monolog\Logger::INFO;
            } else if ($configuredLevel == 'notice') {

                $level = \Monolog\Logger::NOTICE;
            } else if ($configuredLevel == 'warning') {

                $level = \Monolog\Logger::WARNING;
            } else if ($configuredLevel == 'error') {

                $level = \Monolog\Logger::ERROR;
            } else if ($configuredLevel == 'critical') {

                $level = \Monolog\Logger::CRITICAL;
            } else if ($configuredLevel == 'alert') {

                $level = \Monolog\Logger::ALERT;
            } else if ($configuredLevel == 'emergency') {

                $level = \Monolog\Logger::EMERGENCY;
            }
        }

        if (substr(PHP_SAPI, 0, 3) == 'cli') {

            // always use stderr
            $log->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', $level));
        } else {

            $stream = null;

            $logFile = $context->getConfig()->getParam('logfile');

            if ($logFile) {
                if (is_file($logFile)) {
                    $stream = fopen($logFile, 'a');
                }
            }

            if (!$stream) {
                $stream = 'php://stderr';
            }

            $log->pushHandler(new \Monolog\Handler\StreamHandler($stream, $level));
        }

        return $log;
    }
}
