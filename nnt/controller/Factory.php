<?php

namespace Nnt\Controller;

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->setShared('url', function () {
            return null;
        });

        $this->setShared('view', function () {
            $view = new View();
            $view->setDI($this);
            $view->registerEngines([
                ".volt" => "Phalcon\Mvc\View\Engine\Volt"
            ]);
            return $view;
        });
    }
}
