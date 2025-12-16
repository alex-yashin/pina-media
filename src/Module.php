<?php

namespace PinaMedia;

use Pina\App;
use Pina\Config;

use Pina\ModuleInterface;
use Pina\Router;

class Module implements ModuleInterface
{

    public function getPath()
    {
        return __DIR__;
    }

    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    public function getTitle()
    {
        return 'Media';
    }

    public function __construct()
    {
        App::onLoad(Router::class, function(Router $router) {
            $router->registerDispatcher(new Dispatcher());

            $media = Config::get('media');
            foreach ($media as $v) {
                if (empty($v['driver']) || empty($v['controller']) || $v['driver'] != 'resize') {
                    continue;
                }
                $pattern = trim($v['controller'], '/');
                if (empty($pattern)) {
                    continue;
                }
                $router->register($pattern, Endpoints\ResizeEndpoint::class)->permit('public');
            }
        });
    }

}