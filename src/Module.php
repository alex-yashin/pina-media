<?php

namespace PinaMedia;

use Pina\Access;
use Pina\App;
use Pina\Config;

use Pina\Language;
use Pina\ModuleInterface;
use Pina\DispatcherRegistry;

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

    public function http()
    {
        Access::permit('resize', 'public');
        DispatcherRegistry::register(new Dispatcher());
        return $this->initRouter();
    }

    public function initRouter()
    {
        $media = Config::get('media');
        foreach ($media as $v) {
            if (empty($v['resize'])) {
                continue;
            }
            App::router()->register($v['resize'], Endpoints\ResizeEndpoint::class);
        }
        return [];
    }

    public function cli()
    {
        return [];
    }

}

function __($string)
{
    return Language::translate($string, __NAMESPACE__);
}
