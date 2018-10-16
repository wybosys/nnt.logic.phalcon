<?php

namespace Nnt\Controller;

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Router;

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

        $this->setShared('router', function () {
            $router = new Router();
            if (defined('LOGIC_ROUTER')) {
                $router->setDefaults([
                    'controller' => LOGIC_ROUTER,
                    'action' => LOGIC_ACTION
                ]);
            }
            return $router;
        });
    }
}
