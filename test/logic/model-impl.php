<?php

use \Nnt\Model\Logic;
use \Nnt\Controller\Config;
use \Nnt\Controller\Application;

class Model extends Logic
{
    function __construct()
    {
        parent::__construct();

        $cfg = Application::$shared->config("logic");
        $this->host = $cfg["HOST"];
    }

    public $domain;

    function requestUrl(): string
    {
        return Config::Use($this->host . "?action=" . $this->action,
            $this->host . $this->domain . "/?action=" . $this->action,
            $this->host . $this->domain . "/?action=" . $this->action);
    }
}
