<?php

namespace PinaMedia;

use Pina\Config;

class Dispatcher
{

    public function dispatch($resource)
    {
        if (empty($resource)) {
            return $resource;
        }

        $media = Config::get('media');
        foreach ($media as $k => $v) {
            if (empty($v['controller'])) {
                continue;
            }

            $controller = trim($v['controller'], '/');
            if (empty($controller)) {
                continue;
            }

            $prefix = $controller . '/';
            if (strncmp($resource, $prefix, strlen($prefix)) === 0) {
                return $controller;
            }
        }
        return null;
    }

}
