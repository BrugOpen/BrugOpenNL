<?php

namespace BrugOpen\Service;

use BrugOpen\Core\Config;

class ConfigLoader
{

    public function loadConfig($iniFile)
    {
        $config = null;

        if (is_file($iniFile)) {

            $parsedFile = parse_ini_file($iniFile);

            if (is_array($parsedFile)) {

                $env = Config::ENVIRONMENT_DEFAULT;

                $config = new Config($env, $parsedFile);

            }

        }

        return $config;

    }

}
