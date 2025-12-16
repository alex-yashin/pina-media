<?php

namespace PinaMedia;

use Pina\Config;
use Pina\Router\DispatcherInterface;

class Dispatcher implements DispatcherInterface
{

    public function dispatch(string $resource): ?string
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
