<?php
namespace BrugOpen\Core;

class Config
{

    private $env;

    private $params;

    const ENVIRONMENT_DEFAULT = 'default';

    public function __construct($env, $params)
    {
        $this->env = $env;
        $this->params = $params;
    }

    public function getParam($name)
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }

        return null;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function getEnvironment()
    {
        return $this->env;
    }

    public function setEnvironment($env)
    {
        $this->env = $env;
    }
}
