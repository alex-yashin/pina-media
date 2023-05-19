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
        foreach ($media as $v) {
            if (empty($v['resize'])) {
                continue;
            }
            $prefix = $v['resize'] . '/';
            if (strncmp($resource, $prefix, strlen($prefix)) === 0) {
                return $v['resize'];
            }
        }
        return null;
    }

}
